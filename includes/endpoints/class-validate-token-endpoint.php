<?php
if (!defined('ABSPATH')) { exit; }
class WP_Auth_Validate_Token_Endpoint {
    public static function handle($request) {
        $token = $request->get_param('token');
        if (!$token) {
            return new WP_Error('missing_token', __('Token is required.', 'wp-authenticator'), array('status' => 400));
        }
        $jwt_handler = new WP_Auth_JWT_Handler();
        $decoded = $jwt_handler->validate_token($token);
        if (is_wp_error($decoded)) {
            return $decoded;
        }
        return array('success' => true, 'message' => __('Token is valid', 'wp-authenticator'), 'data' => array('user_id' => $decoded['user_id'], 'expires' => $decoded['exp']));
    }
}
