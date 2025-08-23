<?php
if (!defined('ABSPATH')) { exit; }
class WP_Auth_Register_Endpoint {
    public static function handle($request) {
        if (!get_option('users_can_register')) {
            return new WP_Error('registration_disabled', __('User registration is currently not allowed.', 'wp-authenticator'), array('status' => 403));
        }
        $username = $request->get_param('username');
        $email = $request->get_param('email');
        $password = $request->get_param('password');
        $first_name = $request->get_param('first_name');
        $last_name = $request->get_param('last_name');
        if (username_exists($username)) {
            return new WP_Error('username_exists', __('Username already exists.', 'wp-authenticator'), array('status' => 400));
        }
        if (email_exists($email)) {
            return new WP_Error('email_exists', __('Email address already exists.', 'wp-authenticator'), array('status' => 400));
        }
        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Invalid email address.', 'wp-authenticator'), array('status' => 400));
        }
        if (strlen($password) < 6) {
            return new WP_Error('weak_password', __('Password must be at least 6 characters long.', 'wp-authenticator'), array('status' => 400));
        }
        $user_data = array('username' => $username, 'email' => $email, 'password' => $password, 'first_name' => $first_name, 'last_name' => $last_name);
        $otp_handler = new WP_Auth_OTP_Handler();
        $otp_result = $otp_handler->generate_otp($email, $user_data);
        if ($otp_result['success']) {
            return array('success' => true, 'message' => __('Registration initiated. Please check your email for the OTP verification code.', 'wp-authenticator'), 'data' => array('email' => $email, 'otp_expires' => $otp_result['expires'], 'requires_verification' => true, 'next_step' => 'Please call /wp-json/wp-auth/v1/verify-otp with your email and OTP code'));
        } else {
            return new WP_Error('otp_send_failed', __('Failed to send verification email. Please try again.', 'wp-authenticator'), array('status' => 500));
        }
    }
}
