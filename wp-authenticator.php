<?php
/**
 * Plugin Name: WP Authenticator
 * Plugin URI: https://example.com/wp-authenticator
 * Description: Enhanced user login and authentication functionality for WordPress with custom login forms, security features, and user management.
 * Version: 1.0.0
 * Author: Chethan Spoojary
 * License: GPL v2 or later
 * Text Domain: wp-authenticator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WP_AUTHENTICATOR_VERSION', '1.0.0');
define('WP_AUTHENTICATOR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WP_AUTHENTICATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader for Firebase JWT
if (file_exists(WP_AUTHENTICATOR_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'vendor/autoload.php';
}

// Main plugin class
class WP_Authenticator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        
        // Plugin activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Include required files
        $this->include_files();
        
        // Initialize components
        $this->init_components();
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('wp-authenticator', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Security enhancements
        add_action('wp_login_failed', array($this, 'login_failed_handler'));
        add_filter('authenticate', array($this, 'block_failed_login_attempts'), 30, 3);
    }
    
    private function include_files() {
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-jwt-handler.php';
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-api-endpoints.php';
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-security-handler.php';
        require_once WP_AUTHENTICATOR_PLUGIN_PATH . 'includes/class-admin-settings.php';
    }
    
    private function init_components() {
        new WP_Auth_API_Endpoints();
        new WP_Auth_Security_Handler();
        
        if (is_admin()) {
            new WP_Auth_Admin_Settings();
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Login endpoint
        register_rest_route('wp-auth/v1', '/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_login'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'remember' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                ),
            ),
        ));
        
        // Register endpoint
        register_rest_route('wp-auth/v1', '/register', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_register'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
                'password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'first_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'last_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
                // Logout endpoint
        register_rest_route('wp-auth/v1', '/logout', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_logout'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));
        
        // User profile endpoint
        register_rest_route('wp-auth/v1', '/profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_profile'),
            'permission_callback' => 'is_user_logged_in',
        ));
        
        // Update profile endpoint
        register_rest_route('wp-auth/v1', '/profile', array(
            'methods' => 'PUT',
            'callback' => array($this, 'api_update_profile'),
            'permission_callback' => 'is_user_logged_in',
            'args' => array(
                'first_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'last_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
                'description' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));
        
        // Validate token endpoint
        register_rest_route('wp-auth/v1', '/validate-token', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_validate_token'),
            'permission_callback' => 'is_user_logged_in',
        ));
        
        // Security stats endpoint
        register_rest_route('wp-auth/v1', '/security/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_security_stats'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
    }
    
    /**
     * API Login endpoint
     */
    public function api_login($request) {
        $username = $request->get_param('username');
        $password = $request->get_param('password');
        $remember = $request->get_param('remember');
        
        // Check for blocked IP
        $security_handler = new WP_Auth_Security_Handler();
        if ($security_handler->is_ip_blocked()) {
            return new WP_Error(
                'ip_blocked',
                __('Your IP address has been temporarily blocked due to too many failed login attempts.', 'wp-authenticator'),
                array('status' => 429)
            );
        }
        
        $creds = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        );
        
        $user = wp_signon($creds, false);
        
        if (is_wp_error($user)) {
            // Log failed login attempt
            $security_handler->handle_failed_login($_SERVER['REMOTE_ADDR'], $username);
            
            return new WP_Error(
                'login_failed',
                $user->get_error_message(),
                array('status' => 401)
            );
        }
        
        // Generate JWT token
        $jwt_handler = new WP_Auth_JWT_Handler();
        $token_data = $jwt_handler->generate_token($user->ID);
        
        return array(
            'success' => true,
            'message' => __('Login successful', 'wp-authenticator'),
            'data' => array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'token' => $token_data['token'],
                'refresh_token' => $token_data['refresh_token'],
                'expires' => $token_data['expires']
            )
        );
    }
    
    /**
     * API Register endpoint
     */
    public function api_register($request) {
        if (!get_option('users_can_register')) {
            return new WP_Error(
                'registration_disabled',
                __('User registration is currently not allowed.', 'wp-authenticator'),
                array('status' => 403)
            );
        }
        
        $username = $request->get_param('username');
        $email = $request->get_param('email');
        $password = $request->get_param('password');
        $first_name = $request->get_param('first_name');
        $last_name = $request->get_param('last_name');
        
        // Validation
        if (username_exists($username)) {
            return new WP_Error(
                'username_exists',
                __('Username already exists.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        if (email_exists($email)) {
            return new WP_Error(
                'email_exists',
                __('Email address already exists.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        if (!is_email($email)) {
            return new WP_Error(
                'invalid_email',
                __('Invalid email address.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        if (strlen($password) < 6) {
            return new WP_Error(
                'weak_password',
                __('Password must be at least 6 characters long.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
            'role' => get_option('default_role', 'subscriber')
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return new WP_Error(
                'registration_failed',
                $user_id->get_error_message(),
                array('status' => 400)
            );
        }
        
        // Send notification email
        wp_new_user_notification($user_id, null, 'both');
        
        // Auto-login if enabled
        if (get_option('wp_auth_auto_login_after_register', 'yes') === 'yes') {
            $creds = array(
                'user_login' => $username,
                'user_password' => $password,
                'remember' => true
            );
            
            $user = wp_signon($creds, false);
            
            if (!is_wp_error($user)) {
                // Generate JWT token using the same handler as login
                $jwt_handler = new WP_Auth_JWT_Handler();
                $token_data = $jwt_handler->generate_token($user->ID);
                
                return array(
                    'success' => true,
                    'message' => __('Registration and login successful', 'wp-authenticator'),
                    'data' => array(
                        'user_id' => $user->ID,
                        'username' => $user->user_login,
                        'email' => $user->user_email,
                        'display_name' => $user->display_name,
                        'token' => $token_data['token'],
                        'refresh_token' => $token_data['refresh_token'],
                        'expires' => $token_data['expires']
                    )
                );
            }
        }
        
        return array(
            'success' => true,
            'message' => __('Registration successful', 'wp-authenticator'),
            'data' => array(
                'user_id' => $user_id,
                'username' => $username,
                'email' => $email
            )
        );
    }
    
    /**
     * API Logout endpoint - Blacklists JWT token
     */
    public function api_logout($request) {
        // Get the JWT token from the Authorization header
        $jwt_handler = new WP_Auth_JWT_Handler();
        $token = $jwt_handler->get_token_from_header();
        
        if ($token) {
            // Blacklist the current token
            $jwt_handler->blacklist_token($token);
        }
        
        // Also check for token in request body (optional)
        $body_token = $request->get_param('token');
        if ($body_token && $body_token !== $token) {
            $jwt_handler->blacklist_token($body_token);
        }
        
        // Blacklist refresh token if provided
        $refresh_token = $request->get_param('refresh_token');
        if ($refresh_token) {
            $jwt_handler->blacklist_token($refresh_token);
        }
        
        // Clear WordPress user session
        wp_logout();
        
        return array(
            'success' => true,
            'message' => __('Logout successful. Token has been revoked.', 'wp-authenticator')
        );
    }
    
    /**
     * API Get Profile endpoint
     */
    public function api_get_profile($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'wp-authenticator'),
                array('status' => 404)
            );
        }
        
        return array(
            'success' => true,
            'data' => array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'display_name' => $user->display_name,
                'description' => $user->description,
                'registered' => $user->user_registered,
                'roles' => $user->roles
            )
        );
    }
    
    /**
     * API Update Profile endpoint
     */
    public function api_update_profile($request) {
        $user_id = get_current_user_id();
        $user_data = array('ID' => $user_id);
        
        $fields = array('first_name', 'last_name', 'email', 'description');
        
        foreach ($fields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                if ($field === 'email') {
                    if (!is_email($value)) {
                        return new WP_Error(
                            'invalid_email',
                            __('Invalid email address.', 'wp-authenticator'),
                            array('status' => 400)
                        );
                    }
                    
                    if (email_exists($value) && email_exists($value) !== $user_id) {
                        return new WP_Error(
                            'email_exists',
                            __('Email address already exists.', 'wp-authenticator'),
                            array('status' => 400)
                        );
                    }
                    
                    $user_data['user_email'] = $value;
                } else {
                    $user_data[$field] = $value;
                }
            }
        }
        
        $result = wp_update_user($user_data);
        
        if (is_wp_error($result)) {
            return new WP_Error(
                'update_failed',
                $result->get_error_message(),
                array('status' => 400)
            );
        }
        
        $updated_user = get_userdata($user_id);
        
        return array(
            'success' => true,
            'message' => __('Profile updated successfully', 'wp-authenticator'),
            'data' => array(
                'user_id' => $updated_user->ID,
                'username' => $updated_user->user_login,
                'email' => $updated_user->user_email,
                'first_name' => $updated_user->first_name,
                'last_name' => $updated_user->last_name,
                'display_name' => $updated_user->display_name,
                'description' => $updated_user->description
            )
        );
    }
    
    /**
     * API Validate Token endpoint
     */
    public function api_validate_token($request) {
        $token = $request->get_param('token');
        
        if (!$token) {
            return new WP_Error(
                'missing_token',
                __('Token is required.', 'wp-authenticator'),
                array('status' => 400)
            );
        }
        
        $jwt_handler = new WP_Auth_JWT_Handler();
        $decoded = $jwt_handler->validate_token($token);
        
        if (is_wp_error($decoded)) {
            return $decoded;
        }
        
        return array(
            'success' => true,
            'message' => __('Token is valid', 'wp-authenticator'),
            'data' => array(
                'user_id' => $decoded['user_id'],
                'expires' => $decoded['exp']
            )
        );
    }
    
    /**
     * API Security Stats endpoint
     */
    public function api_security_stats($request) {
        $security_handler = new WP_Auth_Security_Handler();
        $stats = $security_handler->get_security_stats();
        
        return array(
            'success' => true,
            'data' => $stats
        );
    }
    
    /**
     * Check admin permission
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
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
    
    public function activate() {
        // Create tables and set default options
        $this->create_tables();
        $this->set_default_options();
        
        // Flush rewrite rules to ensure REST API routes work
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('wp_auth_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wp_auth_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            action varchar(50) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        add_option('wp_auth_login_redirect', '');
        add_option('wp_auth_logout_redirect', '');
        add_option('wp_auth_enable_security', 'yes');
        add_option('wp_auth_max_login_attempts', 5);
        add_option('wp_auth_lockout_duration', 15);
    }
}

// Initialize the plugin
new WP_Authenticator();
