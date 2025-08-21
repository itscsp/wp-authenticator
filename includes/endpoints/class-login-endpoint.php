<?php
if (!defined('ABSPATH')) { exit; }
class WP_Auth_Login_Endpoint {
    public static function handle($request) {
        $username = $request->get_param('username');
        $password = $request->get_param('password');
        $remember = $request->get_param('remember');
        $security_handler = new WP_Auth_Security_Handler();
        if ($security_handler->is_ip_blocked()) {
            return new WP_Error('ip_blocked', __('Your IP address has been temporarily blocked due to too many failed login attempts.', 'wp-authenticator'), array('status' => 429));
        }
        $creds = array('user_login' => $username, 'user_password' => $password, 'remember' => $remember);
        $user = wp_signon($creds, false);
        if (is_wp_error($user)) {
            $security_handler->handle_failed_login($_SERVER['REMOTE_ADDR'], $username);
            return new WP_Error('login_failed', $user->get_error_message(), array('status' => 401));
        }
        $jwt_handler = new WP_Auth_JWT_Handler();
        $token_data = $jwt_handler->generate_token($user->ID);
        return array('success' => true, 'message' => __('Login successful', 'wp-authenticator'), 'data' => array('user_id' => $user->ID, 'username' => $user->user_login, 'email' => $user->user_email, 'display_name' => $user->display_name, 'token' => $token_data['token'], 'refresh_token' => $token_data['refresh_token'], 'expires' => $token_data['expires']));
    }
}
