<?php
/**
 * API Endpoints Class
 * 
 * Handles REST API endpoints for authentication
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Auth_API_Endpoints {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_additional_routes'));
        add_filter('rest_authentication_errors', array($this, 'check_token_authentication'));
    }
    
    /**
     * Register additional REST API routes
     */
    public function register_additional_routes() {
        // Password reset request endpoint
        register_rest_route('wp-auth/v1', '/password-reset-request', array(
            'methods' => 'POST',
            'callback' => array($this, 'password_reset_request'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
            ),
        ));
        
        // Password reset endpoint
        register_rest_route('wp-auth/v1', '/password-reset', array(
            'methods' => 'POST',
            'callback' => array($this, 'password_reset'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
                'otp' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'new_password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));
        
        // Change password request endpoint (sends OTP)
        register_rest_route('wp-auth/v1', '/change-password-request', array(
            'methods' => 'POST',
            'callback' => array($this, 'change_password_request'),
            'permission_callback' => array('WP_Auth_JWT_Permission', 'permission_check'),
        ));
        
        // Change password endpoint
        register_rest_route('wp-auth/v1', '/change-password', array(
            'methods' => 'POST',
            'callback' => array($this, 'change_password'),
            'permission_callback' => array('WP_Auth_JWT_Permission', 'permission_check'),
            'args' => array(
                'otp' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'current_password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'new_password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));
        
        // Refresh token endpoint
        register_rest_route('wp-auth/v1', '/refresh-token', array(
            'methods' => 'POST',
            'callback' => array($this, 'refresh_token'),
            'permission_callback' => '__return_true',
        ));
        
        // User roles endpoint
        register_rest_route('wp-auth/v1', '/user/roles', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_roles'),
            'permission_callback' => array('WP_Auth_JWT_Permission', 'permission_check'),
        ));
        
        // Check username availability
        register_rest_route('wp-auth/v1', '/check-username', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_username_availability'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Check email availability
        register_rest_route('wp-auth/v1', '/check-email', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_email_availability'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
            ),
        ));
    }
    
    /**
     * Password reset request endpoint - sends OTP for verification
     */
    public function password_reset_request($request) {
        $email = $request->get_param('email');
        
        if (!is_email($email)) {
            return new WP_Error(
                'invalid_email',
                __('Invalid email address.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        $user = get_user_by('email', $email);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('No user found with this email address.', 'wp-authenticator'),
                array('status' => 404)
            );
        }
        
        // Use OTP handler to send OTP for password reset
        if (!class_exists('WP_Auth_OTP_Handler')) {
            require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-otp-handler.php';
        }
        
        $otp_handler = new WP_Auth_OTP_Handler();
        $result = $otp_handler->send_otp($email, 'password_reset');
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return array(
            'success' => true,
            'message' => __('OTP sent to your email for password reset verification.', 'wp-authenticator'),
            'data' => array(
                'email' => $email,
                'expires_in' => 300 // 5 minutes
            )
        );
    }
    
    /**
     * Password reset endpoint
     */
    public function password_reset($request) {
        $email = $request->get_param('email');
        $otp = $request->get_param('otp');
        $new_password = $request->get_param('new_password');
        
        $user = get_user_by('email', $email);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('No user found with this email address.', 'wp-authenticator'),
                array('status' => 404)
            );
        }
        
        // Verify OTP
        if (!class_exists('WP_Auth_OTP_Handler')) {
            require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-otp-handler.php';
        }
        
        $otp_handler = new WP_Auth_OTP_Handler();
        $otp_verification = $otp_handler->verify_otp($email, $otp, 'password_reset');
        
        if (is_wp_error($otp_verification)) {
            return $otp_verification;
        }
        
        if (!$otp_verification) {
            return new WP_Error(
                'invalid_otp',
                __('Invalid or expired OTP code.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        if (strlen($new_password) < 6) {
            return new WP_Error(
                'weak_password',
                __('Password must be at least 6 characters long.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        // Reset password
        wp_set_password($new_password, $user->ID);
        
        // Clear any existing auth tokens to force re-login
        delete_user_meta($user->ID, 'wp_auth_token');
        delete_user_meta($user->ID, 'wp_auth_token_expires');
        
        return array(
            'success' => true,
            'message' => __('Password reset successfully.', 'wp-authenticator')
        );
    }
    
    /**
     * Change password request endpoint - sends OTP for verification
     */
    public function change_password_request($request) {
        $jwt_handler = new WP_Auth_JWT_Handler();
        $token = $jwt_handler->get_token_from_header();
        if (!$token) {
            return new WP_Error('no_token', __('No token provided.', 'wp-authenticator'), array('status' => 401));
        }
        $payload = $jwt_handler->validate_token($token);
        if (is_wp_error($payload)) {
            return $payload;
        }
        $user_id = $payload['user_id'];
        
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'wp-authenticator'),
                array('status' => 404)
            );
        }
        
        // Send OTP to user's email
        if (!class_exists('WP_Auth_OTP_Handler')) {
            require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-otp-handler.php';
        }
        
        $otp_handler = new WP_Auth_OTP_Handler();
        $result = $otp_handler->send_otp($user->user_email, 'change_password');
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return array(
            'success' => true,
            'message' => __('OTP sent to your email for password change verification.', 'wp-authenticator'),
            'data' => array(
                'email' => $user->user_email,
                'expires_in' => 300 // 5 minutes
            )
        );
    }

    /**
     * Change password endpoint
     */
    public function change_password($request) {
        $jwt_handler = new WP_Auth_JWT_Handler();
        $token = $jwt_handler->get_token_from_header();
        if (!$token) {
            return new WP_Error('no_token', __('No token provided.', 'wp-authenticator'), array('status' => 401));
        }
        $payload = $jwt_handler->validate_token($token);
        if (is_wp_error($payload)) {
            return $payload;
        }
        $user_id = $payload['user_id'];
        $otp = $request->get_param('otp');
        $current_password = $request->get_param('current_password');
        $new_password = $request->get_param('new_password');
        
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'wp-authenticator'),
                array('status' => 404)
            );
        }
        
        // Verify current password
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            return new WP_Error(
                'incorrect_password',
                __('Current password is incorrect.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        // Verify OTP
        if (!class_exists('WP_Auth_OTP_Handler')) {
            require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-otp-handler.php';
        }
        
        $otp_handler = new WP_Auth_OTP_Handler();
        $otp_verification = $otp_handler->verify_otp($user->user_email, $otp, 'change_password');
        
        if (is_wp_error($otp_verification)) {
            return $otp_verification;
        }
        
        if (!$otp_verification) {
            return new WP_Error(
                'invalid_otp',
                __('Invalid or expired OTP code.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        if (strlen($new_password) < 6) {
            return new WP_Error(
                'weak_password',
                __('New password must be at least 6 characters long.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        wp_set_password($new_password, $user_id);
        
        // Clear authentication token to force re-login
        delete_user_meta($user_id, 'wp_auth_token');
        delete_user_meta($user_id, 'wp_auth_token_expires');
        
        return array(
            'success' => true,
            'message' => __('Password changed successfully. Please login again.', 'wp-authenticator')
        );
    }
    
    /**
     * Refresh token endpoint
     */
    public function refresh_token($request) {
        $refresh_token = $request->get_param('refresh_token');
        
        if (!$refresh_token) {
            return new WP_Error(
                'missing_refresh_token',
                __('Refresh token is required.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        $jwt_handler = new WP_Auth_JWT_Handler();
        $result = $jwt_handler->refresh_token($refresh_token);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return array(
            'success' => true,
            'message' => __('Token refreshed successfully.', 'wp-authenticator'),
            'data' => $result
        );
    }
    
    /**
     * Get user roles endpoint
     */
    public function get_user_roles($request) {
        $jwt_handler = new WP_Auth_JWT_Handler();
        $token = $jwt_handler->get_token_from_header();
        if (!$token) {
            return new WP_Error('no_token', __('No token provided.', 'wp-authenticator'), array('status' => 401));
        }
        $payload = $jwt_handler->validate_token($token);
        if (is_wp_error($payload)) {
            return $payload;
        }
        $user_id = $payload['user_id'];
        $user = get_userdata($user_id);
        
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'wp-authenticator'),
                array('status' => 404)
            );
        }
        
        return array(
            'success' => true,
            'data' => array(
                'roles' => $user->roles,
                'capabilities' => array_keys($user->allcaps)
            )
        );
    }
    
    /**
     * Check username availability
     */
    public function check_username_availability($request) {
        $username = $request->get_param('username');
        
        if (empty($username)) {
            return new WP_Error(
                'empty_username',
                __('Username cannot be empty.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        if (!validate_username($username)) {
            return array(
                'available' => false,
                'message' => __('Username contains invalid characters.', 'wp-authenticator')
            );
        }
        
        $exists = username_exists($username);
        
        return array(
            'available' => !$exists,
            'message' => $exists ? __('Username is already taken.', 'wp-authenticator') : __('Username is available.', 'wp-authenticator')
        );
    }
    
    /**
     * Check email availability
     */
    public function check_email_availability($request) {
        $email = $request->get_param('email');
        
        if (empty($email)) {
            return new WP_Error(
                'empty_email',
                __('Email cannot be empty.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        if (!is_email($email)) {
            return array(
                'available' => false,
                'message' => __('Invalid email format.', 'wp-authenticator')
            );
        }
        
        $exists = email_exists($email);
        
        return array(
            'available' => !$exists,
            'message' => $exists ? __('Email is already registered.', 'wp-authenticator') : __('Email is available.', 'wp-authenticator')
        );
    }
    
    /**
     * Check token authentication for REST API
     */
    public function check_token_authentication($result) {
        // Skip if already authenticated
        if (!empty($result)) {
            return $result;
        }
        
        // Check for token in Authorization header
        $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        
        if (empty($auth_header)) {
            return $result;
        }
        
        // Extract token from "Bearer TOKEN" format
        if (strpos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
        } else {
            return $result;
        }
        
        // Validate JWT token
        $jwt_handler = new WP_Auth_JWT_Handler();
        $decoded = $jwt_handler->validate_token($token);
        
        if (is_wp_error($decoded)) {
            return $decoded;
        }
        
        // Set current user
        wp_set_current_user($decoded['user_id']);
        
        return true;
    }
}
