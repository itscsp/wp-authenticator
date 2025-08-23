<?php
if (!defined('ABSPATH')) { exit; }
class WP_Auth_Resend_OTP_Endpoint {
    public static function handle($request) {
        $email = $request->get_param('email');
        $otp_handler = new WP_Auth_OTP_Handler();
        $result = $otp_handler->resend_otp($email);
        if ($result['success']) {
            return array('success' => true, 'message' => __('OTP has been resent to your email address.', 'wp-authenticator'), 'data' => array('email' => $email, 'otp_expires' => $result['expires']));
        } else {
            return new WP_Error('otp_resend_failed', $result['message'], array('status' => 400));
        }
    }
}
