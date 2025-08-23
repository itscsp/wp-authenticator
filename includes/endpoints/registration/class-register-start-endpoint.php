<?php
/**
 * Registration Start Endpoint - Step 1
 * Handles the first step of registration: collecting name and email, sending OTP
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Auth_Register_Start_Endpoint {
    
    public static function handle($request) {
        // Check if user registration is allowed
        if (!get_option('users_can_register')) {
            return new WP_Error(
                'registration_disabled', 
                __('User registration is currently not allowed.', 'wp-authenticator'), 
                array('status' => 403)
            );
        }

        // Get parameters
        $email = sanitize_email($request->get_param('email'));
        $first_name = sanitize_text_field($request->get_param('first_name'));
        $last_name = sanitize_text_field($request->get_param('last_name'));

        // Validate email
        if (!is_email($email)) {
            return new WP_Error(
                'invalid_email', 
                __('Please provide a valid email address.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        // Check if email already exists
        if (email_exists($email)) {
            return new WP_Error(
                'email_exists', 
                __('An account with this email address already exists.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        // Validate required fields
        if (empty($first_name)) {
            return new WP_Error(
                'missing_first_name', 
                __('First name is required.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        // Prepare registration data for step 1
        $registration_data = array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'step' => 1,
            'started_at' => time()
        );

        // Generate and send OTP
        $otp_handler = new WP_Auth_OTP_Handler();
        $otp_result = $otp_handler->generate_otp($email, $registration_data, 'registration_step1');

        if (is_wp_error($otp_result)) {
            return $otp_result;
        }

        if (!$otp_result['success']) {
            return new WP_Error(
                'otp_send_failed', 
                __('Failed to send verification email. Please try again.', 'wp-authenticator'), 
                array('status' => 500)
            );
        }

        // Generate session token for this registration process
        $session_token = wp_generate_password(32, false);
        
        // Store session data temporarily
        $session_data = array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'step' => 1,
            'started_at' => time(),
            'otp_sent_at' => time()
        );
        
        set_transient('wp_auth_reg_session_' . $session_token, $session_data, 1800); // 30 minutes

        return array(
            'success' => true,
            'message' => __('Registration started successfully. Please check your email for the verification code.', 'wp-authenticator'),
            'data' => array(
                'session_token' => $session_token,
                'email' => $email,
                'step' => 1,
                'next_step' => 'verify_otp',
                'otp_expires_in' => 300, // 5 minutes
                'session_expires_in' => 1800 // 30 minutes
            )
        );
    }
}
