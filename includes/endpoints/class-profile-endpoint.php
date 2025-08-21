<?php
if (!defined('ABSPATH')) { exit; }
class WP_Auth_Profile_Endpoint {
    public static function get($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', __('User not found.', 'wp-authenticator'), array('status' => 404));
        }
        return array('success' => true, 'data' => array('user_id' => $user->ID, 'username' => $user->user_login, 'email' => $user->user_email, 'first_name' => $user->first_name, 'last_name' => $user->last_name, 'display_name' => $user->display_name, 'description' => $user->description, 'registered' => $user->user_registered, 'roles' => $user->roles));
    }
    public static function update($request) {
        $user_id = get_current_user_id();
        $user_data = array('ID' => $user_id);
        $fields = array('first_name', 'last_name', 'email', 'description');
        foreach ($fields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                if ($field === 'email') {
                    if (!is_email($value)) {
                        return new WP_Error('invalid_email', __('Invalid email address.', 'wp-authenticator'), array('status' => 400));
                    }
                    if (email_exists($value) && email_exists($value) !== $user_id) {
                        return new WP_Error('email_exists', __('Email address already exists.', 'wp-authenticator'), array('status' => 400));
                    }
                    $user_data['user_email'] = $value;
                } else {
                    $user_data[$field] = $value;
                }
            }
        }
        $result = wp_update_user($user_data);
        if (is_wp_error($result)) {
            return new WP_Error('update_failed', $result->get_error_message(), array('status' => 400));
        }
        $updated_user = get_userdata($user_id);
        return array('success' => true, 'message' => __('Profile updated successfully', 'wp-authenticator'), 'data' => array('user_id' => $updated_user->ID, 'username' => $updated_user->user_login, 'email' => $updated_user->user_email, 'first_name' => $updated_user->first_name, 'last_name' => $updated_user->last_name, 'display_name' => $updated_user->display_name, 'description' => $updated_user->description));
    }
}
