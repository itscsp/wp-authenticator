<?php
/**
 * Registration Complete Endpoint - Step 3
 * Handles the final step of registration: setting username and password, creating user account
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Auth_Register_Complete_Endpoint {
    
    public static function handle($request) {
        // Get parameters
        $session_token = sanitize_text_field($request->get_param('session_token'));
        $username = sanitize_text_field($request->get_param('username'));
        $password = $request->get_param('password');

        // Validate required fields
        if (empty($session_token)) {
            return new WP_Error(
                'missing_session_token', 
                __('Session token is required.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        if (empty($username)) {
            return new WP_Error(
                'missing_username', 
                __('Username is required.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        if (empty($password)) {
            return new WP_Error(
                'missing_password', 
                __('Password is required.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        // Get session data
        $session_data = get_transient('wp_auth_reg_session_' . $session_token);
        if (!$session_data) {
            return new WP_Error(
                'invalid_session', 
                __('Invalid or expired registration session.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        // Check if we're on the correct step
        if ($session_data['step'] !== 2 || !$session_data['email_verified']) {
            return new WP_Error(
                'invalid_step', 
                __('You must verify your email first before completing registration.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        // Validate username
        if (username_exists($username)) {
            return new WP_Error(
                'username_exists', 
                __('Username already exists. Please choose a different username.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        if (!validate_username($username)) {
            return new WP_Error(
                'invalid_username', 
                __('Username contains invalid characters. Please use only letters, numbers, periods, hyphens, and underscores.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        // Validate password
        if (strlen($password) < 6) {
            return new WP_Error(
                'weak_password', 
                __('Password must be at least 6 characters long.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        // Double-check email doesn't exist (race condition protection)
        if (email_exists($session_data['email'])) {
            return new WP_Error(
                'email_exists', 
                __('An account with this email address already exists.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        // Create user account
        $user_data = array(
            'user_login' => $username,
            'user_email' => $session_data['email'],
            'user_pass' => $password,
            'first_name' => $session_data['first_name'],
            'last_name' => $session_data['last_name'],
            'display_name' => $session_data['first_name'] . ' ' . $session_data['last_name']
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            return new WP_Error(
                'user_creation_failed', 
                __('Failed to create user account: ', 'wp-authenticator') . $user_id->get_error_message(), 
                array('status' => 500)
            );
        }

        // Clean up session data
        delete_transient('wp_auth_reg_session_' . $session_token);

        // Generate JWT token for immediate login
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-jwt-handler.php';
        $jwt_handler = new WP_Auth_JWT_Handler();
        $token_data = $jwt_handler->generate_token($user_id);

        if (is_wp_error($token_data)) {
            // User created but token generation failed - still a success
            return array(
                'success' => true,
                'message' => __('Registration completed successfully! Please login to continue.', 'wp-authenticator'),
                'data' => array(
                    'user_id' => $user_id,
                    'username' => $username,
                    'email' => $session_data['email'],
                    'registration_completed_at' => time(),
                    'token_generation_failed' => true
                )
            );
        }

        return array(
            'success' => true,
            'message' => __('Registration completed successfully! You are now logged in.', 'wp-authenticator'),
            'data' => array(
                'user_id' => $user_id,
                'username' => $username,
                'email' => $session_data['email'],
                'token' => $token_data['token'],
                'token_expires' => $token_data['expires'],
                'user' => array(
                    'ID' => $user_id,
                    'username' => $username,
                    'email' => $session_data['email'],
                    'first_name' => $session_data['first_name'],
                    'last_name' => $session_data['last_name'],
                    'display_name' => $session_data['first_name'] . ' ' . $session_data['last_name']
                ),
                'registration_completed_at' => time()
            )
        );
    }
}
