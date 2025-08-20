<?php
/**
 * Admin Settings Class
 * 
 * Handles admin settings page and configuration options
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Auth_Admin_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_init', array($this, 'process_jwt_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('WP Authenticator Settings', 'wp-authenticator'),
            __('WP Authenticator', 'wp-authenticator'),
            'manage_options',
            'wp-authenticator',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('wp_auth_settings', 'wp_auth_login_redirect');
        register_setting('wp_auth_settings', 'wp_auth_logout_redirect');
        register_setting('wp_auth_settings', 'wp_auth_enable_security');
        register_setting('wp_auth_settings', 'wp_auth_max_login_attempts');
        register_setting('wp_auth_settings', 'wp_auth_lockout_duration');
        register_setting('wp_auth_settings', 'wp_auth_notify_admin_blocks');
        register_setting('wp_auth_settings', 'wp_auth_notify_admin_registration');
        register_setting('wp_auth_settings', 'wp_auth_auto_login_after_register');
        register_setting('wp_auth_settings', 'wp_auth_block_suspicious');
        
        // JWT Settings
        register_setting('wp_auth_settings', 'wp_auth_jwt_secret');
        register_setting('wp_auth_settings', 'wp_auth_jwt_expiry');
        register_setting('wp_auth_settings', 'wp_auth_jwt_refresh_expiry');
        register_setting('wp_auth_settings', 'wp_auth_jwt_algorithm');
        register_setting('wp_auth_settings', 'wp_auth_regenerate_jwt_secret');
        register_setting('wp_auth_settings', 'wp_auth_custom_jwt_secret');
        
        // General Settings Section
        add_settings_section(
            'wp_auth_general_section',
            __('General Settings', 'wp-authenticator'),
            array($this, 'general_section_callback'),
            'wp_auth_settings'
        );
        
        add_settings_field(
            'wp_auth_login_redirect',
            __('Login Redirect URL', 'wp-authenticator'),
            array($this, 'login_redirect_callback'),
            'wp_auth_settings',
            'wp_auth_general_section'
        );
        
        add_settings_field(
            'wp_auth_logout_redirect',
            __('Logout Redirect URL', 'wp-authenticator'),
            array($this, 'logout_redirect_callback'),
            'wp_auth_settings',
            'wp_auth_general_section'
        );
        
        add_settings_field(
            'wp_auth_auto_login_after_register',
            __('Auto-login After Registration', 'wp-authenticator'),
            array($this, 'auto_login_callback'),
            'wp_auth_settings',
            'wp_auth_general_section'
        );
        
        // JWT Settings Section
        add_settings_section(
            'wp_auth_jwt_section',
            __('JWT Security Settings', 'wp-authenticator'),
            array($this, 'jwt_section_callback'),
            'wp_auth_settings'
        );
        
        add_settings_field(
            'wp_auth_jwt_secret',
            __('JWT Secret Key', 'wp-authenticator'),
            array($this, 'jwt_secret_callback'),
            'wp_auth_settings',
            'wp_auth_jwt_section'
        );
        
        add_settings_field(
            'wp_auth_jwt_expiry',
            __('Access Token Expiry (seconds)', 'wp-authenticator'),
            array($this, 'jwt_expiry_callback'),
            'wp_auth_settings',
            'wp_auth_jwt_section'
        );
        
        add_settings_field(
            'wp_auth_jwt_refresh_expiry',
            __('Refresh Token Expiry (seconds)', 'wp-authenticator'),
            array($this, 'jwt_refresh_expiry_callback'),
            'wp_auth_settings',
            'wp_auth_jwt_section'
        );
        
        add_settings_field(
            'wp_auth_jwt_algorithm',
            __('JWT Algorithm', 'wp-authenticator'),
            array($this, 'jwt_algorithm_callback'),
            'wp_auth_settings',
            'wp_auth_jwt_section'
        );
        
        // Security Settings Section
        add_settings_section(
            'wp_auth_security_section',
            __('Security Settings', 'wp-authenticator'),
            array($this, 'security_section_callback'),
            'wp_auth_settings'
        );
        
        add_settings_field(
            'wp_auth_enable_security',
            __('Enable Security Features', 'wp-authenticator'),
            array($this, 'enable_security_callback'),
            'wp_auth_settings',
            'wp_auth_security_section'
        );
        
        add_settings_field(
            'wp_auth_max_login_attempts',
            __('Max Login Attempts', 'wp-authenticator'),
            array($this, 'max_attempts_callback'),
            'wp_auth_settings',
            'wp_auth_security_section'
        );
        
        add_settings_field(
            'wp_auth_lockout_duration',
            __('Lockout Duration (minutes)', 'wp-authenticator'),
            array($this, 'lockout_duration_callback'),
            'wp_auth_settings',
            'wp_auth_security_section'
        );
        
        add_settings_field(
            'wp_auth_block_suspicious',
            __('Block Suspicious Activity', 'wp-authenticator'),
            array($this, 'block_suspicious_callback'),
            'wp_auth_settings',
            'wp_auth_security_section'
        );
        
        // Notification Settings Section
        add_settings_section(
            'wp_auth_notification_section',
            __('Notification Settings', 'wp-authenticator'),
            array($this, 'notification_section_callback'),
            'wp_auth_settings'
        );
        
        add_settings_field(
            'wp_auth_notify_admin_blocks',
            __('Notify Admin of IP Blocks', 'wp-authenticator'),
            array($this, 'notify_blocks_callback'),
            'wp_auth_settings',
            'wp_auth_notification_section'
        );
        
        add_settings_field(
            'wp_auth_notify_admin_registration',
            __('Notify Admin of New Registrations', 'wp-authenticator'),
            array($this, 'notify_registration_callback'),
            'wp_auth_settings',
            'wp_auth_notification_section'
        );
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wp-auth-admin-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#settings" class="nav-tab nav-tab-active"><?php _e('Settings', 'wp-authenticator'); ?></a>
                    <a href="#api" class="nav-tab"><?php _e('API Documentation', 'wp-authenticator'); ?></a>
                </nav>
                
                <div id="settings" class="tab-content">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('wp_auth_settings');
                        do_settings_sections('wp_auth_settings');
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div id="api" class="tab-content" style="display: none;">
                    <h2><?php _e('API Documentation', 'wp-authenticator'); ?></h2>
                    
                    <div class="notice notice-info">
                        <p><strong><?php _e('JWT Authentication:', 'wp-authenticator'); ?></strong> <?php _e('This plugin uses the production-ready Firebase JWT library (firebase/php-jwt) for secure authentication.', 'wp-authenticator'); ?></p>
                    </div>
                    
                    <div class="wp-auth-api-docs">
                        <div class="api-endpoint">
                            <h3><?php _e('Login Endpoint', 'wp-authenticator'); ?></h3>
                            <code>POST /wp-json/wp-auth/v1/login</code>
                            <p><?php _e('Authenticate user and receive JWT access token and refresh token.', 'wp-authenticator'); ?></p>
                            <h4><?php _e('Parameters:', 'wp-authenticator'); ?></h4>
                            <ul>
                                <li><strong>username</strong> (required): Username or email</li>
                                <li><strong>password</strong> (required): User password</li>
                                <li><strong>remember</strong> (optional): Boolean, remember user session</li>
                            </ul>
                            <h4><?php _e('Response:', 'wp-authenticator'); ?></h4>
                            <ul>
                                <li><strong>token</strong>: JWT access token</li>
                                <li><strong>refresh_token</strong>: JWT refresh token</li>
                                <li><strong>expires</strong>: Token expiration timestamp</li>
                                <li><strong>user</strong>: User information</li>
                            </ul>
                        </div>
                        
                        <div class="api-endpoint">
                            <h3><?php _e('Token Validation', 'wp-authenticator'); ?></h3>
                            <code>POST /wp-json/wp-auth/v1/validate-token</code>
                            <p><?php _e('Validate a JWT token.', 'wp-authenticator'); ?></p>
                            <h4><?php _e('Parameters:', 'wp-authenticator'); ?></h4>
                            <ul>
                                <li><strong>token</strong> (required): JWT token to validate</li>
                            </ul>
                        </div>
                        
                        <div class="api-endpoint">
                            <h3><?php _e('Token Refresh', 'wp-authenticator'); ?></h3>
                            <code>POST /wp-json/wp-auth/v1/refresh-token</code>
                            <p><?php _e('Refresh an expired access token using refresh token.', 'wp-authenticator'); ?></p>
                            <h4><?php _e('Parameters:', 'wp-authenticator'); ?></h4>
                            <ul>
                                <li><strong>refresh_token</strong> (required): Valid refresh token</li>
                            </ul>
                        </div>
                        
                        <div class="api-endpoint">
                            <h3><?php _e('Register Endpoint', 'wp-authenticator'); ?></h3>
                            <code>POST /wp-json/wp-auth/v1/register</code>
                            <p><?php _e('Register new user account.', 'wp-authenticator'); ?></p>
                            <h4><?php _e('Parameters:', 'wp-authenticator'); ?></h4>
                            <ul>
                                <li><strong>username</strong> (required): Desired username</li>
                                <li><strong>email</strong> (required): User email</li>
                                <li><strong>password</strong> (required): User password</li>
                                <li><strong>first_name</strong> (optional): First name</li>
                                <li><strong>last_name</strong> (optional): Last name</li>
                            </ul>
                        </div>
                        
                        <div class="api-endpoint">
                            <h3><?php _e('Profile Endpoints', 'wp-authenticator'); ?></h3>
                            <code>GET /wp-json/wp-auth/v1/profile</code><br>
                            <code>PUT /wp-json/wp-auth/v1/profile</code>
                            <p><?php _e('Get or update user profile information. Requires authentication token.', 'wp-authenticator'); ?></p>
                        </div>
                        
                        <div class="api-endpoint">
                            <h3><?php _e('Password Management', 'wp-authenticator'); ?></h3>
                            <code>POST /wp-json/wp-auth/v1/password-reset-request</code><br>
                            <code>POST /wp-json/wp-auth/v1/password-reset</code><br>
                            <code>POST /wp-json/wp-auth/v1/change-password</code>
                            <p><?php _e('Request password reset, reset password, or change password.', 'wp-authenticator'); ?></p>
                        </div>
                        
                        <div class="api-endpoint">
                            <h3><?php _e('JWT Authentication', 'wp-authenticator'); ?></h3>
                            <p><?php _e('After login, include the JWT token in the Authorization header:', 'wp-authenticator'); ?></p>
                            <code>Authorization: Bearer your_jwt_token_here</code>
                            <h4><?php _e('JWT Features:', 'wp-authenticator'); ?></h4>
                            <ul>
                                <li><?php _e('Self-contained tokens with user information', 'wp-authenticator'); ?></li>
                                <li><?php _e('HMAC-SHA256 signature for security', 'wp-authenticator'); ?></li>
                                <li><?php _e('Automatic expiration handling', 'wp-authenticator'); ?></li>
                                <li><?php _e('Refresh token support', 'wp-authenticator'); ?></li>
                                <li><?php _e('Token blacklisting for logout', 'wp-authenticator'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="api-endpoint">
                            <h3><?php _e('Example Usage', 'wp-authenticator'); ?></h3>
                            <p><?php _e('Check the examples/ folder for complete usage examples in PHP, JavaScript, and cURL.', 'wp-authenticator'); ?></p>
                        </div>
                        
                        <p><strong><?php _e('For complete API documentation with examples, see the examples/jwt-usage-examples.php file.', 'wp-authenticator'); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.nav-tab');
            const contents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('nav-tab-active'));
                    contents.forEach(c => c.style.display = 'none');
                    
                    // Add active class to clicked tab
                    this.classList.add('nav-tab-active');
                    
                    // Show corresponding content
                    const targetId = this.getAttribute('href').substring(1);
                    const targetContent = document.getElementById(targetId);
                    if (targetContent) {
                        targetContent.style.display = 'block';
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        settings_errors('wp_auth_settings');
    }
    
    // Settings callbacks
    public function general_section_callback() {
        echo '<p>' . __('Configure general authentication settings.', 'wp-authenticator') . '</p>';
    }
    
    public function security_section_callback() {
        echo '<p>' . __('Configure security and protection settings.', 'wp-authenticator') . '</p>';
    }
    
    public function notification_section_callback() {
        echo '<p>' . __('Configure email notification settings.', 'wp-authenticator') . '</p>';
    }
    
    public function jwt_section_callback() {
        echo '<p>' . __('Configure JWT token security settings. <strong>Warning:</strong> Changing the secret key will invalidate all existing tokens.', 'wp-authenticator') . '</p>';
        
        // Show current status
        $secret = get_option('wp_auth_jwt_secret');
        if ($secret) {
            echo '<div class="notice notice-info inline"><p><strong>Status:</strong> JWT Secret Key is configured (' . strlen($secret) . ' characters)</p></div>';
        } else {
            echo '<div class="notice notice-warning inline"><p><strong>Warning:</strong> No JWT Secret Key found. One will be auto-generated.</p></div>';
        }
    }
    
    public function login_redirect_callback() {
        $value = get_option('wp_auth_login_redirect', '');
        echo '<input type="url" name="wp_auth_login_redirect" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL to redirect users after successful login. Leave empty for default behavior.', 'wp-authenticator') . '</p>';
    }
    
    public function logout_redirect_callback() {
        $value = get_option('wp_auth_logout_redirect', '');
        echo '<input type="url" name="wp_auth_logout_redirect" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL to redirect users after logout. Leave empty for default behavior.', 'wp-authenticator') . '</p>';
    }
    
    public function auto_login_callback() {
        $value = get_option('wp_auth_auto_login_after_register', 'yes');
        echo '<label><input type="checkbox" name="wp_auth_auto_login_after_register" value="yes" ' . checked($value, 'yes', false) . ' /> ' . __('Automatically log in users after successful registration', 'wp-authenticator') . '</label>';
    }
    
    public function enable_security_callback() {
        $value = get_option('wp_auth_enable_security', 'yes');
        echo '<label><input type="checkbox" name="wp_auth_enable_security" value="yes" ' . checked($value, 'yes', false) . ' /> ' . __('Enable failed login attempt tracking and IP blocking', 'wp-authenticator') . '</label>';
    }
    
    public function max_attempts_callback() {
        $value = get_option('wp_auth_max_login_attempts', 5);
        echo '<input type="number" name="wp_auth_max_login_attempts" value="' . esc_attr($value) . '" min="1" max="20" />';
        echo '<p class="description">' . __('Number of failed login attempts before blocking an IP address.', 'wp-authenticator') . '</p>';
    }
    
    public function lockout_duration_callback() {
        $value = get_option('wp_auth_lockout_duration', 15);
        echo '<input type="number" name="wp_auth_lockout_duration" value="' . esc_attr($value) . '" min="1" max="1440" />';
        echo '<p class="description">' . __('Duration in minutes to block an IP address after too many failed attempts.', 'wp-authenticator') . '</p>';
    }
    
    public function block_suspicious_callback() {
        $value = get_option('wp_auth_block_suspicious', 'no');
        echo '<label><input type="checkbox" name="wp_auth_block_suspicious" value="yes" ' . checked($value, 'yes', false) . ' /> ' . __('Block suspicious user agents and rate limit requests', 'wp-authenticator') . '</label>';
    }
    
    public function notify_blocks_callback() {
        $value = get_option('wp_auth_notify_admin_blocks', 'no');
        echo '<label><input type="checkbox" name="wp_auth_notify_admin_blocks" value="yes" ' . checked($value, 'yes', false) . ' /> ' . __('Send email notification when an IP is blocked', 'wp-authenticator') . '</label>';
    }
    
    public function notify_registration_callback() {
        $value = get_option('wp_auth_notify_admin_registration', 'yes');
        echo '<label><input type="checkbox" name="wp_auth_notify_admin_registration" value="yes" ' . checked($value, 'yes', false) . ' /> ' . __('Send email notification for new user registrations', 'wp-authenticator') . '</label>';
    }
    
    // JWT Settings Callbacks
    public function jwt_secret_callback() {
        $value = get_option('wp_auth_jwt_secret', '');
        echo '<div style="margin-bottom: 10px;">';
        
        if ($value) {
            // Show masked secret with option to regenerate
            $masked = str_repeat('•', 20) . substr($value, -8);
            echo '<p><strong>Current Secret:</strong> <code>' . esc_html($masked) . '</code></p>';
            echo '<label><input type="checkbox" name="wp_auth_regenerate_jwt_secret" value="yes" /> ' . __('Regenerate Secret Key (will invalidate all existing tokens)', 'wp-authenticator') . '</label><br>';
        } else {
            echo '<p><em>' . __('No secret key set. One will be automatically generated.', 'wp-authenticator') . '</em></p>';
        }
        
        echo '<p class="description">' . __('The secret key used to sign JWT tokens. Keep this secure and never share it. Changing this will invalidate all existing tokens.', 'wp-authenticator') . '</p>';
        echo '</div>';
        
        // Option to set custom secret
        echo '<details style="margin-top: 10px;">';
        echo '<summary style="cursor: pointer; font-weight: bold;">' . __('Set Custom Secret Key', 'wp-authenticator') . '</summary>';
        echo '<div style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 4px solid #00a0d2;">';
        echo '<textarea name="wp_auth_custom_jwt_secret" rows="3" cols="80" placeholder="' . __('Enter your custom secret key (minimum 64 characters recommended)', 'wp-authenticator') . '"></textarea>';
        echo '<p class="description"><strong>' . __('Advanced:', 'wp-authenticator') . '</strong> ' . __('Only set a custom secret if you know what you\'re doing. The auto-generated secret is cryptographically secure.', 'wp-authenticator') . '</p>';
        echo '</div>';
        echo '</details>';
    }
    
    public function jwt_expiry_callback() {
        $value = get_option('wp_auth_jwt_expiry', 3600);
        echo '<input type="number" name="wp_auth_jwt_expiry" value="' . esc_attr($value) . '" min="300" max="86400" step="60" />';
        echo '<p class="description">' . __('Access token expiration time in seconds. Default: 3600 (1 hour). Range: 5 minutes to 24 hours.', 'wp-authenticator') . '</p>';
        
        // Show human-readable time
        $hours = floor($value / 3600);
        $minutes = floor(($value % 3600) / 60);
        echo '<p><em>' . sprintf(__('Current setting: %s', 'wp-authenticator'), 
            ($hours > 0 ? $hours . ' hour(s) ' : '') . 
            ($minutes > 0 ? $minutes . ' minute(s)' : '')) . '</em></p>';
    }
    
    public function jwt_refresh_expiry_callback() {
        $value = get_option('wp_auth_jwt_refresh_expiry', 604800);
        echo '<input type="number" name="wp_auth_jwt_refresh_expiry" value="' . esc_attr($value) . '" min="86400" max="2592000" step="3600" />';
        echo '<p class="description">' . __('Refresh token expiration time in seconds. Default: 604800 (7 days). Range: 1 day to 30 days.', 'wp-authenticator') . '</p>';
        
        // Show human-readable time
        $days = floor($value / 86400);
        $hours = floor(($value % 86400) / 3600);
        echo '<p><em>' . sprintf(__('Current setting: %d day(s) %d hour(s)', 'wp-authenticator'), $days, $hours) . '</em></p>';
    }
    
    public function jwt_algorithm_callback() {
        $value = get_option('wp_auth_jwt_algorithm', 'HS256');
        $algorithms = array(
            'HS256' => 'HS256 (HMAC SHA-256) - Recommended',
            'HS384' => 'HS384 (HMAC SHA-384)',
            'HS512' => 'HS512 (HMAC SHA-512)'
        );
        
        echo '<select name="wp_auth_jwt_algorithm">';
        foreach ($algorithms as $algo => $label) {
            echo '<option value="' . esc_attr($algo) . '" ' . selected($value, $algo, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('JWT signing algorithm. HS256 is recommended for most use cases. Changing this will invalidate existing tokens.', 'wp-authenticator') . '</p>';
    }
    
    /**
     * Process JWT settings on save
     */
    public function process_jwt_settings() {
        if (!isset($_POST['submit']) || !current_user_can('manage_options')) {
            return;
        }
        
        // Check nonce for security
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wp_auth_settings-options')) {
            return;
        }
        
        // Handle regenerate secret key
        if (isset($_POST['wp_auth_regenerate_jwt_secret']) && $_POST['wp_auth_regenerate_jwt_secret'] === 'yes') {
            $new_secret = wp_generate_password(64, true, true);
            update_option('wp_auth_jwt_secret', $new_secret);
            
            add_settings_error(
                'wp_auth_settings',
                'jwt_secret_regenerated',
                __('JWT Secret Key has been regenerated. All existing tokens have been invalidated.', 'wp-authenticator'),
                'updated'
            );
        }
        
        // Handle custom secret key
        if (isset($_POST['wp_auth_custom_jwt_secret']) && !empty(trim($_POST['wp_auth_custom_jwt_secret']))) {
            $custom_secret = trim($_POST['wp_auth_custom_jwt_secret']);
            
            // Validate secret key length
            if (strlen($custom_secret) < 32) {
                add_settings_error(
                    'wp_auth_settings',
                    'jwt_secret_too_short',
                    __('Custom JWT Secret Key must be at least 32 characters long for security.', 'wp-authenticator'),
                    'error'
                );
            } else {
                update_option('wp_auth_jwt_secret', $custom_secret);
                
                add_settings_error(
                    'wp_auth_settings',
                    'jwt_secret_updated',
                    __('Custom JWT Secret Key has been set. All existing tokens have been invalidated.', 'wp-authenticator'),
                    'updated'
                );
            }
        }
        
        // Validate JWT expiry settings
        if (isset($_POST['wp_auth_jwt_expiry'])) {
            $expiry = intval($_POST['wp_auth_jwt_expiry']);
            if ($expiry < 300 || $expiry > 86400) {
                add_settings_error(
                    'wp_auth_settings',
                    'jwt_expiry_invalid',
                    __('Access token expiry must be between 5 minutes (300 seconds) and 24 hours (86400 seconds).', 'wp-authenticator'),
                    'error'
                );
            }
        }
        
        if (isset($_POST['wp_auth_jwt_refresh_expiry'])) {
            $refresh_expiry = intval($_POST['wp_auth_jwt_refresh_expiry']);
            if ($refresh_expiry < 86400 || $refresh_expiry > 2592000) {
                add_settings_error(
                    'wp_auth_settings',
                    'jwt_refresh_expiry_invalid',
                    __('Refresh token expiry must be between 1 day (86400 seconds) and 30 days (2592000 seconds).', 'wp-authenticator'),
                    'error'
                );
            }
        }
    }
}
