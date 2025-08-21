<?php
if (!defined('ABSPATH')) { exit; }
class WP_Auth_JWT_Permission {
    public static function permission_check($request) {
        $jwt_handler = new WP_Auth_JWT_Handler();
        $token = $jwt_handler->get_token_from_header();
        if (!$token) {
            $token = $request->get_param('token');
        }
        if ($token && $jwt_handler->validate_token($token)) {
            return true;
        }
        return false;
    }
}
