<?php
/**
 * Security Handler Class
 * 
 * Handles security features like rate limiting, IP blocking, etc.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Auth_Security_Handler {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_login_failed', array($this, 'handle_failed_login'));
        // add_filter('authenticate', array($this, 'check_ip_block'), 30, 5);
        
        // Schedule cleanup event
        if (!wp_next_scheduled('wp_auth_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'wp_auth_cleanup');
        }
    }
    
    public function init() {
        // Additional security initialization
        $this->maybe_block_suspicious_activity();
    }
    
    /**
     * Handle failed login attempts
     */
    public function handle_failed_login($username) {
        if (get_option('wp_auth_enable_security', 'yes') !== 'yes') {
            return;
        }
        
        $ip = $this->get_client_ip();
        $attempts_key = 'wp_auth_failed_attempts_' . $ip;
        $block_key = 'wp_auth_blocked_' . $ip;
        
        $attempts = get_transient($attempts_key);
        $attempts = $attempts ? $attempts + 1 : 1;
        
        // Store failed attempt with 15-minute expiry
        set_transient($attempts_key, $attempts, 15 * MINUTE_IN_SECONDS);
        
        $max_attempts = get_option('wp_auth_max_login_attempts', 5);
        $lockout_duration = get_option('wp_auth_lockout_duration', 15);
        
        if ($attempts >= $max_attempts) {
            // Block IP for specified duration
            set_transient($block_key, true, $lockout_duration * MINUTE_IN_SECONDS);
            
            // Log the block
            $this->log_security_event('ip_blocked', $ip, array(
                'attempts' => $attempts,
                'username' => $username,
                'lockout_duration' => $lockout_duration
            ));
            
            // Send notification email to admin if enabled
            if (get_option('wp_auth_notify_admin_blocks', 'no') === 'yes') {
                $this->send_block_notification($ip, $attempts, $username);
            }
            
            do_action('wp_auth_ip_blocked', $ip, $attempts, $username);
        }
        
        // Log failed attempt
        $this->log_security_event('login_failed', $ip, array(
            'attempts' => $attempts,
            'username' => $username
        ));
    }
    
    /**
     * Check if IP is blocked
     */
    public function check_ip_block($user, $username, $password) {
        if (get_option('wp_auth_enable_security', 'yes') !== 'yes') {
            return $user;
        }
        
        $ip = $this->get_client_ip();
        $block_key = 'wp_auth_blocked_' . $ip;
        
        if (get_transient($block_key)) {
            $remaining_time = $this->get_remaining_block_time($ip);
            
            return new WP_Error(
                'ip_blocked',
                sprintf(
                    __('Your IP address has been temporarily blocked due to too many failed login attempts. Please try again in %s.', 'wp-authenticator'),
                    $this->format_time_remaining($remaining_time)
                )
            );
        }
        
        return $user;
    }
    
    /**
     * Get remaining block time for an IP
     */
    private function get_remaining_block_time($ip) {
        $block_key = 'wp_auth_blocked_' . $ip;
        $lockout_duration = get_option('wp_auth_lockout_duration', 15);
        
        // Try to get the exact expiry time from the transient
        $transient_timeout = get_option('_transient_timeout_' . $block_key);
        if ($transient_timeout) {
            return max(0, $transient_timeout - time());
        }
        
        // Fallback to default duration
        return $lockout_duration * 60;
    }
    
    /**
     * Format remaining time in human-readable format
     */
    private function format_time_remaining($seconds) {
        if ($seconds <= 0) {
            return __('a few moments', 'wp-authenticator');
        }
        
        $minutes = ceil($seconds / 60);
        
        if ($minutes < 60) {
            return sprintf(_n('%d minute', '%d minutes', $minutes, 'wp-authenticator'), $minutes);
        }
        
        $hours = ceil($minutes / 60);
        return sprintf(_n('%d hour', '%d hours', $hours, 'wp-authenticator'), $hours);
    }
    
    /**
     * Block suspicious activity
     */
    private function maybe_block_suspicious_activity() {
        if (get_option('wp_auth_block_suspicious', 'no') !== 'yes') {
            return;
        }
        
        $ip = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Check for suspicious patterns
        $suspicious_patterns = array(
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scanner/i',
            '/hack/i',
            '/exploit/i'
        );
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                $this->block_ip_temporarily($ip, 'suspicious_user_agent');
                break;
            }
        }
        
        // Check for rapid requests (basic rate limiting)
        $request_key = 'wp_auth_requests_' . $ip;
        $requests = get_transient($request_key);
        $requests = $requests ? $requests + 1 : 1;
        
        set_transient($request_key, $requests, 60); // 1 minute window
        
        if ($requests > 30) { // More than 30 requests per minute
            $this->block_ip_temporarily($ip, 'rate_limit_exceeded');
        }
    }
    
    /**
     * Block IP temporarily
     */
    private function block_ip_temporarily($ip, $reason) {
        $block_key = 'wp_auth_blocked_' . $ip;
        $duration = 10 * MINUTE_IN_SECONDS; // 10 minutes for suspicious activity
        
        set_transient($block_key, true, $duration);
        
        $this->log_security_event('ip_blocked_suspicious', $ip, array(
            'reason' => $reason,
            'duration' => $duration
        ));
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    /**
     * Log security events
     */
    private function log_security_event($event, $ip, $data = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wp_auth_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => 0,
                'action' => $event,
                'ip_address' => $ip,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        // Store additional data in meta table if needed
        if (!empty($data)) {
            $log_id = $wpdb->insert_id;
            foreach ($data as $key => $value) {
                $wpdb->insert(
                    $wpdb->prefix . 'wp_auth_log_meta',
                    array(
                        'log_id' => $log_id,
                        'meta_key' => $key,
                        'meta_value' => maybe_serialize($value)
                    ),
                    array('%d', '%s', '%s')
                );
            }
        }
    }
    
    /**
     * Send block notification email to admin
     */
    private function send_block_notification($ip, $attempts, $username) {
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] IP Address Blocked', 'wp-authenticator'), get_bloginfo('name'));
        
        $message = sprintf(
            __("An IP address has been blocked on your website due to multiple failed login attempts.\n\nDetails:\nIP Address: %s\nFailed Attempts: %d\nLast Username Tried: %s\nTime: %s\n\nThis is an automated security notification from WP Authenticator.", 'wp-authenticator'),
            $ip,
            $attempts,
            $username,
            current_time('mysql')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Cleanup expired blocks and logs
     */
    public function cleanup_expired_blocks() {
        global $wpdb;
        
        // Clean up old log entries (older than 30 days)
        $table_name = $wpdb->prefix . 'wp_auth_logs';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE timestamp < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        // Clean up old log meta entries
        $meta_table = $wpdb->prefix . 'wp_auth_log_meta';
        $wpdb->query("DELETE m FROM {$meta_table} m LEFT JOIN {$table_name} l ON m.log_id = l.id WHERE l.id IS NULL");
        
        do_action('wp_auth_cleanup_completed');
    }
    
    /**
     * Get security statistics
     */
    public function get_security_stats($days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wp_auth_logs';
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = array(
            'failed_logins' => 0,
            'blocked_ips' => 0,
            'successful_logins' => 0,
            'registrations' => 0
        );
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT action, COUNT(*) as count FROM {$table_name} WHERE timestamp >= %s GROUP BY action",
            $date_from
        ));
        
        foreach ($results as $result) {
            switch ($result->action) {
                case 'login_failed':
                    $stats['failed_logins'] = $result->count;
                    break;
                case 'ip_blocked':
                case 'ip_blocked_suspicious':
                    $stats['blocked_ips'] += $result->count;
                    break;
                case 'login_success':
                case 'wp_login':
                    $stats['successful_logins'] += $result->count;
                    break;
                case 'user_register':
                    $stats['registrations'] = $result->count;
                    break;
            }
        }
        
        return $stats;
    }
    
    /**
     * Check if IP is currently blocked
     */
    public function is_ip_blocked($ip = null) {
        if (!$ip) {
            $ip = $this->get_client_ip();
        }
        
        return get_transient('wp_auth_blocked_' . $ip);
    }
    
    /**
     * Manually unblock an IP
     */
    public function unblock_ip($ip) {
        delete_transient('wp_auth_blocked_' . $ip);
        delete_transient('wp_auth_failed_attempts_' . $ip);
        
        $this->log_security_event('ip_unblocked', $ip);
        
        do_action('wp_auth_ip_unblocked', $ip);
    }
}
