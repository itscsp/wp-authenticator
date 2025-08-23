<?php
/**
 * Registration Verify OTP Endpoint - Step 2
 * Handles the second step of registration: verifying OTP
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Auth_Register_Verify_OTP_Endpoint {
    
    public static function handle($request) {
        // Get parameters
        $session_token = sanitize_text_field($request->get_param('session_token'));
        $otp = sanitize_text_field($request->get_param('otp'));

        // Validate required fields
        if (empty($session_token)) {
            return new WP_Error(
                'missing_session_token', 
                __('Session token is required.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        if (empty($otp)) {
            return new WP_Error(
                'missing_otp', 
                __('OTP code is required.', 'wp-authenticator'), 
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
        if ($session_data['step'] !== 1) {
            return new WP_Error(
                'invalid_step', 
                __('Invalid registration step.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        $email = $session_data['email'];

        // Verify OTP
        $otp_handler = new WP_Auth_OTP_Handler();
        $verification_result = $otp_handler->verify_otp($email, $otp, 'registration_step1');

        if (is_wp_error($verification_result)) {
            return $verification_result;
        }

        // OTP verified successfully - update session to step 2
        $session_data['step'] = 2;
        $session_data['otp_verified_at'] = time();
        $session_data['email_verified'] = true;

        // Extend session for completing registration
        set_transient('wp_auth_reg_session_' . $session_token, $session_data, 1800); // 30 minutes

        return array(
            'success' => true,
            'message' => __('Email verified successfully. Please complete your registration by setting a username and password.', 'wp-authenticator'),
            'data' => array(
                'session_token' => $session_token,
                'email' => $email,
                'step' => 2,
                'next_step' => 'complete_registration',
                'email_verified' => true,
                'session_expires_in' => 1800 // 30 minutes
            )
        );
    }
}
