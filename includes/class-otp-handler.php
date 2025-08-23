<?php
/**
 * OTP Handler Class for WP Authenticator
 * 
 * Handles OTP generation, verification, and email sending for user registration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Auth_OTP_Handler {
    
    private $otp_length = 6;
    private $otp_expiry = 300; // 5 minutes in seconds
    private $max_attempts = 3;
    
    public function __construct() {
        // Add hooks for cleanup
        add_action('wp_auth_cleanup_expired_otps', array($this, 'cleanup_expired_otps'));
        
        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('wp_auth_cleanup_expired_otps')) {
            wp_schedule_event(time(), 'hourly', 'wp_auth_cleanup_expired_otps');
        }
    }
    
    /**
     * Generate and send OTP for various purposes
     */
    public function send_otp($email, $purpose = 'registration', $user_data = array()) {
        return $this->generate_otp($email, $user_data, $purpose);
    }
    
    /**
     * Generate OTP for user registration or other purposes
     */
    public function generate_otp($email, $user_data = array(), $purpose = 'registration') {
        // Generate random 6-digit OTP
        $otp = sprintf('%06d', mt_rand(100000, 999999));
        $expires = time() + $this->otp_expiry;
        
        // Store OTP data in database
        $otp_data = array(
            'otp' => $otp,
            'email' => sanitize_email($email),
            'user_data' => $user_data,
            'purpose' => $purpose,
            'expires' => $expires,
            'attempts' => 0,
            'created' => time()
        );
        
        // Store in transients (WordPress cache) - use purpose-specific key
        $transient_key = 'wp_auth_otp_' . $purpose . '_' . md5($email);
        set_transient($transient_key, $otp_data, $this->otp_expiry);
        
        // Also store in options as backup
        $option_key = 'wp_auth_otp_' . $purpose . '_' . md5($email);
        update_option($option_key, $otp_data);
        
        // Send OTP via email
        $email_sent = $this->send_otp_email($email, $otp, $purpose);
        
        if (!$email_sent) {
            return new WP_Error(
                'email_failed',
                __('Failed to send OTP email.', 'wp-authenticator'),
                array('status' => 500)
            );
        }
        
        return array(
            'success' => $email_sent,
            'otp' => $otp, // Remove this in production - only for testing
            'expires' => $expires,
            'email' => $email,
            'purpose' => $purpose
        );
    }
    
    /**
     * Verify OTP and complete user registration or other operations
     */
    public function verify_otp($email, $provided_otp, $purpose = 'registration') {
        $email = sanitize_email($email);
        $transient_key = 'wp_auth_otp_' . $purpose . '_' . md5($email);
        $option_key = 'wp_auth_otp_' . $purpose . '_' . md5($email);
        
        // Get OTP data
        $otp_data = get_transient($transient_key);
        if (!$otp_data) {
            $otp_data = get_option($option_key);
        }
        
        if (!$otp_data) {
            return new WP_Error(
                'otp_not_found',
                __('OTP not found or expired.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        // Check if OTP has expired
        if (time() > $otp_data['expires']) {
            // Clean up expired OTP
            delete_transient($transient_key);
            delete_option($option_key);
            return new WP_Error(
                'otp_expired',
                __('OTP has expired.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        // Check if max attempts exceeded
        if ($otp_data['attempts'] >= $this->max_attempts) {
            // Clean up OTP after max attempts
            delete_transient($transient_key);
            delete_option($option_key);
            return new WP_Error(
                'max_attempts_exceeded',
                __('Maximum OTP verification attempts exceeded.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        // Verify OTP
        if ($otp_data['otp'] !== $provided_otp) {
            // Increment attempts
            $otp_data['attempts']++;
            set_transient($transient_key, $otp_data, $this->otp_expiry);
            update_option($option_key, $otp_data);
            
            return new WP_Error(
                'invalid_otp',
                __('Invalid OTP code.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        // OTP is valid - clean up
        delete_transient($transient_key);
        delete_option($option_key);
        
        // For registration, create user account
        if ($purpose === 'registration' && !empty($otp_data['user_data'])) {
            $user_id = $this->create_user_account($otp_data['user_data']);
            if (is_wp_error($user_id)) {
                return $user_id;
            }
            return array(
                'success' => true,
                'user_id' => $user_id,
                'purpose' => $purpose
            );
        }
        
        // For other purposes (password reset, change password), just return success
        return true;
    }
    
    /**
     * Resend OTP
     */
    public function resend_otp($email, $purpose = 'registration') {
        $email = sanitize_email($email);
        $transient_key = 'wp_auth_otp_' . $purpose . '_' . md5($email);
        $option_key = 'wp_auth_otp_' . $purpose . '_' . md5($email);
        
        // Get existing OTP data
        $otp_data = get_transient($transient_key);
        if (!$otp_data) {
            $otp_data = get_option($option_key);
        }
        
        if (!$otp_data) {
            return new WP_Error(
                'no_pending_otp',
                __('No pending OTP verification found.', 'wp-authenticator'),
                array('status' => 404)
            );
        }
        
        // Generate new OTP
        return $this->generate_otp($email, $otp_data['user_data'], $purpose);
    }
    
    /**
     * Send OTP via email
     */
    private function send_otp_email($email, $otp, $purpose = 'registration') {
        // Customize subject and message based on purpose
        switch ($purpose) {
            case 'password_reset':
                $subject = 'Password Reset - Your OTP Code';
                $title = 'Password Reset Request';
                $description = 'You have requested to reset your password. Please use the following OTP to proceed:';
                break;
            case 'change_password':
                $subject = 'Password Change - Your OTP Code';
                $title = 'Password Change Verification';
                $description = 'You have requested to change your password. Please use the following OTP to verify:';
                break;
            default:
                $subject = 'Email Verification - Your OTP Code';
                $title = 'Email Verification Required';
                $description = 'Thank you for registering! Please use the following OTP to complete your registration:';
                break;
        }
        
        $message = "
        <html>
        <head>
            <style>
                .otp-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }
                .otp-code { font-size: 32px; font-weight: bold; color: #2271b1; text-align: center; 
                           background: #f0f6fc; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .warning { color: #d63638; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='otp-container'>
                <h2>{$title}</h2>
                <p>{$description}</p>
                
                <div class='otp-code'>{$otp}</div>
                
                <p><strong>Important:</strong></p>
                <ul>
                    <li>This OTP is valid for 5 minutes only</li>
                    <li>You have 3 attempts to enter the correct OTP</li>
                    <li>Do not share this code with anyone</li>
                </ul>
                
                <p class='warning'>If you didn't request this action, please ignore this email.</p>
                
                <hr>
                <p><small>This is an automated message from " . get_bloginfo('name') . "</small></p>
            </div>
        </body>
        </html>";
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Create user after OTP verification
     */
    private function create_user_account($user_data) {
        // Validate required fields
        if (empty($user_data['username']) || empty($user_data['email']) || empty($user_data['password'])) {
            return array(
                'success' => false,
                'message' => 'Missing required user data.'
            );
        }
        
        // Check if username/email still available
        if (username_exists($user_data['username'])) {
            return array(
                'success' => false,
                'message' => 'Username is no longer available.'
            );
        }
        
        if (email_exists($user_data['email'])) {
            return array(
                'success' => false,
                'message' => 'Email address is no longer available.'
            );
        }
        
        // Create user
        $wp_user_data = array(
            'user_login' => $user_data['username'],
            'user_email' => $user_data['email'],
            'user_pass' => $user_data['password'],
            'first_name' => $user_data['first_name'] ?? '',
            'last_name' => $user_data['last_name'] ?? '',
            'display_name' => trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')),
            'role' => get_option('default_role', 'subscriber')
        );
        
        $user_id = wp_insert_user($wp_user_data);
        
        if (is_wp_error($user_id)) {
            return array(
                'success' => false,
                'message' => $user_id->get_error_message()
            );
        }
        
        // Mark email as verified
        update_user_meta($user_id, 'wp_auth_email_verified', true);
        update_user_meta($user_id, 'wp_auth_email_verified_date', current_time('mysql'));
        
        // Send welcome notification
        wp_new_user_notification($user_id, null, 'both');
        
        // Auto-login if enabled
        if (get_option('wp_auth_auto_login_after_register', 'yes') === 'yes') {
            $user = get_user_by('id', $user_id);
            
            // Generate JWT token
            $jwt_handler = new WP_Auth_JWT_Handler();
            $token_data = $jwt_handler->generate_token($user->ID);
            
            return array(
                'success' => true,
                'message' => 'Email verified and registration completed successfully!',
                'data' => array(
                    'user_id' => $user->ID,
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'email_verified' => true,
                    'token' => $token_data['token'],
                    'refresh_token' => $token_data['refresh_token'],
                    'expires' => $token_data['expires']
                )
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Email verified and registration completed successfully!',
            'data' => array(
                'user_id' => $user_id,
                'username' => $user_data['username'],
                'email' => $user_data['email'],
                'email_verified' => true
            )
        );
    }
    
    /**
     * Clean up OTP data
     */
    private function cleanup_otp($email) {
        $transient_key = 'wp_auth_otp_' . md5($email);
        $option_key = 'wp_auth_otp_' . md5($email);
        
        delete_transient($transient_key);
        delete_option($option_key);
    }
    
    /**
     * Clean up expired OTPs (scheduled task)
     */
    public function cleanup_expired_otps() {
        global $wpdb;
        
        // Clean up options that start with wp_auth_otp_
        $expired_otps = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'wp_auth_otp_%'"
        );
        
        foreach ($expired_otps as $otp_option) {
            $otp_data = get_option($otp_option->option_name);
            if ($otp_data && isset($otp_data['expires']) && time() > $otp_data['expires']) {
                delete_option($otp_option->option_name);
            }
        }
    }
    
    /**
     * Get OTP status for email
     */
    public function get_otp_status($email) {
        $email = sanitize_email($email);
        $transient_key = 'wp_auth_otp_' . md5($email);
        $option_key = 'wp_auth_otp_' . md5($email);
        
        $otp_data = get_transient($transient_key);
        if (!$otp_data) {
            $otp_data = get_option($option_key);
        }
        
        if (!$otp_data) {
            return array(
                'has_pending_otp' => false,
                'message' => 'No pending OTP verification.'
            );
        }
        
        if (time() > $otp_data['expires']) {
            $this->cleanup_otp($email);
            return array(
                'has_pending_otp' => false,
                'message' => 'OTP has expired.'
            );
        }
        
        return array(
            'has_pending_otp' => true,
            'expires' => $otp_data['expires'],
            'attempts_remaining' => $this->max_attempts - $otp_data['attempts'],
            'email' => $email
        );
    }
}
