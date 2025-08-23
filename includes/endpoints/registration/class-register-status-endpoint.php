<?php
/**
 * Registration Status Endpoint
 * Allows checking the current status of a registration session
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Auth_Register_Status_Endpoint {
    
    public static function handle($request) {
        // Get parameters
        $session_token = sanitize_text_field($request->get_param('session_token'));

        // Validate required fields
        if (empty($session_token)) {
            return new WP_Error(
                'missing_session_token', 
                __('Session token is required.', 'wp-authenticator'), 
                array('status' => 400)
            );
        }

        // Get session data
        $session_data = get_transient('wp_auth_reg_session_' . $session_token);
        if (!$session_data) {
            return new WP_Error(
                'invalid_session', 
                __('Invalid or expired registration session.', 'wp-authenticator'), 
                array('status' => 404)
            );
        }

        // Calculate time remaining
        $current_time = time();
        $session_duration = 1800; // 30 minutes
        $session_started = $session_data['started_at'];
        $expires_at = $session_started + $session_duration;
        $time_remaining = max(0, $expires_at - $current_time);

        // Determine next action based on current step
        $next_action = '';
        switch ($session_data['step']) {
            case 1:
                $next_action = $session_data['email_verified'] ? 'complete_registration' : 'verify_otp';
                break;
            case 2:
                $next_action = 'complete_registration';
                break;
            default:
                $next_action = 'start_registration';
        }

        return array(
            'success' => true,
            'data' => array(
                'session_token' => $session_token,
                'email' => $session_data['email'],
                'first_name' => $session_data['first_name'],
                'last_name' => $session_data['last_name'],
                'current_step' => $session_data['step'],
                'email_verified' => isset($session_data['email_verified']) ? $session_data['email_verified'] : false,
                'next_action' => $next_action,
                'session_expires_in' => $time_remaining,
                'session_expires_at' => $expires_at,
                'started_at' => $session_started,
                'otp_sent_at' => isset($session_data['otp_sent_at']) ? $session_data['otp_sent_at'] : null,
                'otp_verified_at' => isset($session_data['otp_verified_at']) ? $session_data['otp_verified_at'] : null
            )
        );
    }
}
