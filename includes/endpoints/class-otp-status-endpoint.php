<?php
if (!defined('ABSPATH')) { exit; }
class WP_Auth_OTP_Status_Endpoint {
    public static function handle($request) {
        $email = $request->get_param('email');
        $otp_handler = new WP_Auth_OTP_Handler();
        $status = $otp_handler->get_otp_status($email);
        return array('success' => true, 'data' => $status);
    }
}
