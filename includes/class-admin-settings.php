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
                    
                    <div class="wp-auth-api-docs">
                        <div class="api-endpoint">
                            <h3><?php _e('Login Endpoint', 'wp-authenticator'); ?></h3>
                            <code>POST /wp-json/wp-auth/v1/login</code>
                            <p><?php _e('Authenticate user and receive access token.', 'wp-authenticator'); ?></p>
                            <h4><?php _e('Parameters:', 'wp-authenticator'); ?></h4>
                            <ul>
                                <li><strong>username</strong> (required): Username or email</li>
                                <li><strong>password</strong> (required): User password</li>
                                <li><strong>remember</strong> (optional): Boolean, remember user session</li>
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
                            <h3><?php _e('Authentication', 'wp-authenticator'); ?></h3>
                            <p><?php _e('After login, include the token in the Authorization header:', 'wp-authenticator'); ?></p>
                            <code>Authorization: Bearer your_token_here</code>
                        </div>
                        
                        <p><strong><?php _e('For complete API documentation with examples, see the README.md file.', 'wp-authenticator'); ?></strong></p>
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
}
