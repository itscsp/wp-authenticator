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
                'reset_key' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'new_password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));
        
        // Change password endpoint
        register_rest_route('wp-auth/v1', '/change-password', array(
            'methods' => 'POST',
            'callback' => array($this, 'change_password'),
            'permission_callback' => 'is_user_logged_in',
            'args' => array(
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
            'permission_callback' => 'is_user_logged_in',
        ));
        
        // User roles endpoint
        register_rest_route('wp-auth/v1', '/user/roles', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_roles'),
            'permission_callback' => 'is_user_logged_in',
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
     * Password reset request endpoint
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
        
        // Generate reset key
        $reset_key = get_password_reset_key($user);
        if (is_wp_error($reset_key)) {
            return $reset_key;
        }
        
        // Send reset email
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
        
        $message = sprintf(
            __('Someone has requested a password reset for the following account: %s', 'wp-authenticator'),
            network_home_url()
        ) . "\r\n\r\n";
        $message .= sprintf(__('Username: %s', 'wp-authenticator'), $user->user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'wp-authenticator') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:', 'wp-authenticator') . "\r\n\r\n";
        $message .= $reset_url . "\r\n";
        
        $title = sprintf(__('[%s] Password Reset', 'wp-authenticator'), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES));
        
        if (wp_mail($user->user_email, $title, $message)) {
            return array(
                'success' => true,
                'message' => __('Password reset email sent successfully.', 'wp-authenticator')
            );
        } else {
            return new WP_Error(
                'email_failed',
                __('Failed to send password reset email.', 'wp-authenticator'),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Password reset endpoint
     */
    public function password_reset($request) {
        $email = $request->get_param('email');
        $reset_key = $request->get_param('reset_key');
        $new_password = $request->get_param('new_password');
        
        $user = get_user_by('email', $email);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('Invalid reset key or email.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        $check_key = check_password_reset_key($reset_key, $user->user_login);
        if (is_wp_error($check_key)) {
            return new WP_Error(
                'invalid_key',
                __('Invalid or expired reset key.', 'wp-authenticator'),
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
        
        reset_password($user, $new_password);
        
        return array(
            'success' => true,
            'message' => __('Password reset successfully.', 'wp-authenticator')
        );
    }
    
    /**
     * Change password endpoint
     */
    public function change_password($request) {
        $user_id = get_current_user_id();
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
            'message' => __('Password changed successfully.', 'wp-authenticator')
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
        $user_id = get_current_user_id();
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
