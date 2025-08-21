<?php
/**
 * REST API Routes for WP Authenticator
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WP_Auth_Rest_Routes {
    public static function register() {
        if ( ! function_exists('register_rest_route') ) {
            return;
        }
    // Load JWT permission class
    require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-jwt-permission.php';
    // Login endpoint
    require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/class-login-endpoint.php';
        register_rest_route('wp-auth/v1', '/login', array(
            'methods' => 'POST',
            'callback' => array('WP_Auth_Login_Endpoint', 'handle'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'remember' => array(
                    'required' => false,
                    'type' => 'boolean',
                ),
            ),
        ));

        // Register endpoint
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/class-register-endpoint.php';
        register_rest_route('wp-auth/v1', '/register', array(
            'methods' => 'POST',
            'callback' => array('WP_Auth_Register_Endpoint', 'handle'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
                'password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'first_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'last_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

     
                    // Logout endpoint
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/class-logout-endpoint.php';
        register_rest_route('wp-auth/v1', '/logout', array(
            'methods' => 'POST',
            'callback' => array('WP_Auth_Logout_Endpoint', 'handle'),
                'permission_callback' => array('WP_Auth_JWT_Permission', 'permission_check'),
        ));

        // User profile endpoint
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/class-profile-endpoint.php';
        register_rest_route('wp-auth/v1', '/profile', array(
            'methods' => 'GET',
            'callback' => array('WP_Auth_Profile_Endpoint', 'get'),
            'permission_callback' => 'is_user_logged_in',
        ));

        // Update profile endpoint
        register_rest_route('wp-auth/v1', '/profile', array(
            'methods' => 'PUT',
            'callback' => array('WP_Auth_Profile_Endpoint', 'update'),
            'permission_callback' => 'is_user_logged_in',
            'args' => array(
                'first_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'last_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
                'description' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));

        // Validate token endpoint
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/class-validate-token-endpoint.php';
        register_rest_route('wp-auth/v1', '/validate-token', array(
            'methods' => 'GET',
            'callback' => array('WP_Auth_Validate_Token_Endpoint', 'handle'),
            'permission_callback' => '__return_true',
        ));

        // Security stats endpoint
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/class-security-stats-endpoint.php';
        register_rest_route('wp-auth/v1', '/security/stats', array(
            'methods' => 'GET',
            'callback' => array('WP_Auth_Security_Stats_Endpoint', 'handle'),
            'permission_callback' => function() { return current_user_can('manage_options'); },
        ));

        // OTP endpoints
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/class-verify-otp-endpoint.php';
        register_rest_route('wp-auth/v1', '/verify-otp', array(
            'methods' => 'POST',
            'callback' => array('WP_Auth_Verify_OTP_Endpoint', 'handle'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => function($param) {
                        return function_exists('is_email') ? is_email($param) : true;
                    }
                ),
                'otp' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/class-resend-otp-endpoint.php';
        register_rest_route('wp-auth/v1', '/resend-otp', array(
            'methods' => 'POST',
            'callback' => array('WP_Auth_Resend_OTP_Endpoint', 'handle'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => function($param) {
                        return function_exists('is_email') ? is_email($param) : true;
                    }
                ),
            ),
        ));

        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/class-otp-status-endpoint.php';
        register_rest_route('wp-auth/v1', '/otp-status', array(
            'methods' => 'GET',
            'callback' => array('WP_Auth_OTP_Status_Endpoint', 'handle'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => function($param) {
                        return function_exists('is_email') ? is_email($param) : true;
                    }
                ),
            ),
        ));
    }
}
