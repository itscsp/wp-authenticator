<?php
if (!defined('ABSPATH')) { exit; }

class WP_Authenticator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        // Register REST API routes
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'routes/rest-routes.php';
        add_action('rest_api_init', array('WP_Auth_Rest_Routes', 'register'));
        // Security enhancements
        add_action('wp_login_failed', array($this, 'login_failed_handler'));
        add_filter('authenticate', array($this, 'block_failed_login_attempts'), 30, 3);
        // Always register admin menu
        if (is_admin()) {
            require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-admin-settings.php';
            new WP_Auth_Admin_Settings();
        }
    }
    public function init() {
        load_plugin_textdomain('wp-authenticator', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    public function login_failed_handler($username) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $attempts = get_transient('wp_auth_failed_attempts_' . $ip);
        $attempts = $attempts ? $attempts + 1 : 1;
        set_transient('wp_auth_failed_attempts_' . $ip, $attempts, 15 * MINUTE_IN_SECONDS);
        if ($attempts >= 5) {
            set_transient('wp_auth_blocked_' . $ip, true, 15 * MINUTE_IN_SECONDS);
        }
    }
    public function block_failed_login_attempts($user, $username, $password) {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (get_transient('wp_auth_blocked_' . $ip)) {
            return new WP_Error('blocked', __('Too many failed login attempts. Please try again later.', 'wp-authenticator'));
        }
        return $user;
    }
}
