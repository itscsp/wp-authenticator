<?php
if (!defined('ABSPATH')) { exit; }
class WP_Auth_Logout_Endpoint {
    public static function handle($request) {
        $jwt_handler = new WP_Auth_JWT_Handler();
        $token = $jwt_handler->get_token_from_header();
        if ($token) {
            $jwt_handler->blacklist_token($token);
        }
        $body_token = $request->get_param('token');
        if ($body_token && $body_token !== $token) {
            $jwt_handler->blacklist_token($body_token);
        }
        $refresh_token = $request->get_param('refresh_token');
        if ($refresh_token) {
            $jwt_handler->blacklist_token($refresh_token);
        }
        wp_logout();
        return array('success' => true, 'message' => __('Logout successful. Token has been revoked.', 'wp-authenticator'));
    }
}
