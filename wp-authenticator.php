<?php
/**
 * Plugin Name: WP Authenticator
 * Plugin URI: https://github.com/itscsp/wp-authenticator
 * Description: Enhanced user login and authentication functionality for WordPress with custom login forms, security features, and user management.
 * Version: 1.0.1
 * Author: Chethan S poojary
 * License: GPL v2 or later
 * Text Domain: wp-authenticator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WP_AUTHENTICATOR_VERSION', '1.0.1');
define('WP_AUTHENTICATOR_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Load Composer autoloader for Firebase JWT
if (file_exists(WP_AUTHENTICATOR_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'vendor/autoload.php';
}

// Include main class and dependencies
require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-jwt-handler.php';
require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-api-endpoints.php';
require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-security-handler.php';
require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-admin-settings.php';
require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-otp-handler.php';
require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-wp-authenticator.php';

// Initialize the plugin
new WP_Authenticator();
