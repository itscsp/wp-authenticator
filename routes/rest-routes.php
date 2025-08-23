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
    require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/auth/class-login-endpoint.php';
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

        // Registration endpoints - 3-step process
        
        // Step 1: Start registration (collect name and email, send OTP)
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/registration/class-register-start-endpoint.php';
        register_rest_route('wp-auth/v1', '/register/start', array(
            'methods' => 'POST',
            'callback' => array('WP_Auth_Register_Start_Endpoint', 'handle'),
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
                'first_name' => array(
                    'required' => true,
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

        // Step 2: Verify OTP
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/registration/class-register-verify-otp-endpoint.php';
        register_rest_route('wp-auth/v1', '/register/verify-otp', array(
            'methods' => 'POST',
            'callback' => array('WP_Auth_Register_Verify_OTP_Endpoint', 'handle'),
            'permission_callback' => '__return_true',
            'args' => array(
                'session_token' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'otp' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Step 3: Complete registration (set username and password)
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/registration/class-register-complete-endpoint.php';
        register_rest_route('wp-auth/v1', '/register/complete', array(
            'methods' => 'POST',
            'callback' => array('WP_Auth_Register_Complete_Endpoint', 'handle'),
            'permission_callback' => '__return_true',
            'args' => array(
                'session_token' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'username' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        // Registration status endpoint
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/registration/class-register-status-endpoint.php';
        register_rest_route('wp-auth/v1', '/register/status', array(
            'methods' => 'GET',
            'callback' => array('WP_Auth_Register_Status_Endpoint', 'handle'),
            'permission_callback' => '__return_true',
            'args' => array(
                'session_token' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Legacy single-step registration endpoint (for backward compatibility)
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/registration/class-register-endpoint.php';
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
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/auth/class-logout-endpoint.php';
        register_rest_route('wp-auth/v1', '/logout', array(
            'methods' => 'POST',
            'callback' => array('WP_Auth_Logout_Endpoint', 'handle'),
                'permission_callback' => array('WP_Auth_JWT_Permission', 'permission_check'),
        ));

        // User profile endpoint
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/profile/class-profile-endpoint.php';
        register_rest_route('wp-auth/v1', '/profile', array(
            'methods' => 'GET',
            'callback' => array('WP_Auth_Profile_Endpoint', 'get'),
            'permission_callback' => array('WP_Auth_JWT_Permission', 'permission_check'),
        ));

        // Update profile endpoint
        register_rest_route('wp-auth/v1', '/profile', array(
            'methods' => 'PUT',
            'callback' => array('WP_Auth_Profile_Endpoint', 'update'),
            'permission_callback' => array('WP_Auth_JWT_Permission', 'permission_check'),
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
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/auth/class-validate-token-endpoint.php';
        register_rest_route('wp-auth/v1', '/validate-token', array(
            'methods' => 'GET',
            'callback' => array('WP_Auth_Validate_Token_Endpoint', 'handle'),
            'permission_callback' => '__return_true',
        ));

        // Security stats endpoint
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/security/class-security-stats-endpoint.php';
        register_rest_route('wp-auth/v1', '/security/stats', array(
            'methods' => 'GET',
            'callback' => array('WP_Auth_Security_Stats_Endpoint', 'handle'),
            'permission_callback' => function() { return current_user_can('manage_options'); },
        ));

        // OTP endpoints
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/otp/class-verify-otp-endpoint.php';
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

        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/otp/class-resend-otp-endpoint.php';
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

        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/endpoints/otp/class-otp-status-endpoint.php';
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
