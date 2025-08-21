<?php
if (!defined('ABSPATH')) { exit; }
class WP_Auth_Verify_OTP_Endpoint {
    public static function handle($request) {
        $email = $request->get_param('email');
        $otp = $request->get_param('otp');
        $otp_handler = new WP_Auth_OTP_Handler();
        $result = $otp_handler->verify_otp($email, $otp);
        if ($result['success']) {
            return $result;
        } else {
            return new WP_Error('otp_verification_failed', $result['message'], array('status' => 400));
        }
    }
}
