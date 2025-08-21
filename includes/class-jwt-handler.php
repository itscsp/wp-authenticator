<?php
/**
 * JWT Handler Class using Firebase JWT Library
 * 
 * This implementation uses the well-maintained Firebase JWT library
 * for production-ready JWT token handling.
 * 
 * Requires: firebase/php-jwt via Composer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

class WP_Auth_JWT_Handler {
    
    private $secret_key;
    private $algorithm = 'HS256';
    private $expiration_time = 3600; // 1 hour
    private $refresh_expiration_time = 604800; // 7 days
    private $issuer;
    private $audience;
    
    public function __construct() {
        // Get or generate secret key
        $secret = get_option('wp_auth_jwt_secret');
        
        if (!$secret) {
            // Generate a cryptographically secure secret
            $secret = wp_generate_password(64, true, true);
            update_option('wp_auth_jwt_secret', $secret);
        }
        
        $this->secret_key = $secret;
        $this->issuer = get_site_url();
        $this->audience = get_site_url();
        
        // SECURITY WARNING: Display in admin if not using HTTPS
        if (!is_ssl() && is_admin()) {
            add_action('admin_notices', array($this, 'https_warning'));
        }
    }
    
    /**
     * Display HTTPS warning in admin
     */
    public function https_warning() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>WP Authenticator Security Warning:</strong> ';
        echo 'JWT tokens should only be used over HTTPS. Please enable SSL/TLS for your site.';
        echo '</p></div>';
    }
    
    /**
     * Generate JWT token for user using Firebase JWT
     */
    public function generate_token($user_id, $additional_claims = array()) {
        $issued_at = time();
        $expiration = $issued_at + $this->expiration_time;
        
        $payload = array(
            'iss' => $this->issuer, // Issuer
            'aud' => $this->audience, // Audience
            'iat' => $issued_at, // Issued at
            'exp' => $expiration, // Expiration
            'user_id' => $user_id,
            'user_login' => get_userdata($user_id)->user_login
        );
        
        // Add any additional claims
        $payload = array_merge($payload, $additional_claims);
        
        // Generate access token
        $access_token = JWT::encode($payload, $this->secret_key, $this->algorithm);
        
        // Generate refresh token
        $refresh_payload = array(
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $issued_at,
            'exp' => $issued_at + $this->refresh_expiration_time,
            'user_id' => $user_id,
            'type' => 'refresh'
        );
        
        $refresh_token = JWT::encode($refresh_payload, $this->secret_key, $this->algorithm);
        
        return array(
            'token' => $access_token,
            'refresh_token' => $refresh_token,
            'expires' => $expiration
        );
    }
    
    /**
     * Validate JWT token with comprehensive security checks using Firebase JWT
     */
    public function validate_token($token) {
        try {
            // Check if token is blacklisted first
            if ($this->is_token_blacklisted($token)) {
                return new WP_Error('token_blacklisted', 'Token has been revoked', array('status' => 401));
            }
            
            // Decode and validate token using Firebase JWT
            $decoded = JWT::decode($token, new Key($this->secret_key, $this->algorithm));
            $payload = (array) $decoded;
            
            // Additional validation checks
            if (!isset($payload['user_id'])) {
                return new WP_Error('invalid_token', 'Token missing user ID', array('status' => 400));
            }
            
            // Check if user still exists
            $user = get_userdata($payload['user_id']);
            if (!$user) {
                return new WP_Error('user_not_found', 'User no longer exists', array('status' => 404));
            }
            
            return $payload;
            
        } catch (ExpiredException $e) {
            return new WP_Error('token_expired', 'Token has expired', array('status' => 401));
        } catch (SignatureInvalidException $e) {
            return new WP_Error('invalid_signature', 'Token signature is invalid', array('status' => 401));
        } catch (BeforeValidException $e) {
            return new WP_Error('token_not_valid_yet', 'Token is not valid yet', array('status' => 401));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid token: ' . $e->getMessage(), array('status' => 400));
        }
    }
    
    /**
     * Refresh token using Firebase JWT
     */
    public function refresh_token($refresh_token) {
        try {
            // Validate the refresh token
            $decoded = JWT::decode($refresh_token, new Key($this->secret_key, $this->algorithm));
            $payload = (array) $decoded;
            
            // Check if it's actually a refresh token
            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                return new WP_Error('invalid_refresh_token', 'Not a valid refresh token');
            }
            
            // Check if user still exists
            $user = get_userdata($payload['user_id']);
            if (!$user) {
                return new WP_Error('user_not_found', 'User no longer exists');
            }
            
            // Generate new tokens
            return $this->generate_token($payload['user_id']);
            
        } catch (ExpiredException $e) {
            return new WP_Error('refresh_token_expired', 'Refresh token has expired');
        } catch (Exception $e) {
            return new WP_Error('invalid_refresh_token', 'Invalid refresh token: ' . $e->getMessage());
        }
    }
    
    /**
     * Get token from Authorization header
     */
    public function get_token_from_header() {
        $auth_header = null;
        
        // Check for Authorization header
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                $auth_header = $headers['Authorization'];
            }
        }
        
        if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Get user ID from token
     */
    public function get_user_id_from_token($token = null) {
        if (!$token) {
            $token = $this->get_token_from_header();
        }
        
        if (!$token) {
            return false;
        }
        
        $payload = $this->validate_token($token);
        
        if (is_wp_error($payload)) {
            return false;
        }
        
        return isset($payload['user_id']) ? $payload['user_id'] : false;
    }
    
    /**
     * Check if current request is authenticated
     */
    public function is_authenticated() {
        $token = $this->get_token_from_header();
        
        if (!$token) {
            return false;
        }
        
        $payload = $this->validate_token($token);
        return !is_wp_error($payload);
    }
    
    /**
     * Set expiration time for access tokens
     */
    public function set_expiration_time($seconds) {
        $this->expiration_time = $seconds;
    }
    
    /**
     * Get expiration time
     */
    public function get_expiration_time() {
        return $this->expiration_time;
    }
    
    /**
     * Blacklist a token (for logout functionality)
     */
    public function blacklist_token($token) {
        try {
            // Decode token to get expiration time
            $decoded = JWT::decode($token, new Key($this->secret_key, $this->algorithm));
            $payload = (array) $decoded;
            if (isset($payload['exp'])) {
                $expiration = $payload['exp'];
                $current_time = time();
                // Only blacklist if token hasn't expired yet
                if ($expiration > $current_time) {
                    $remaining_time = $expiration - $current_time;
                    $token_hash = hash('sha256', $token);
                    set_transient('wp_auth_blacklist_' . $token_hash, true, $remaining_time);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[JWT] Blacklisted token: ' . $token_hash . ' for ' . $remaining_time . ' seconds');
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[JWT] Token expired, not blacklisted: ' . $token);
                    }
                }
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[JWT] Blacklist error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Check if a token is blacklisted
     */
    public function is_token_blacklisted($token) {
        $token_hash = hash('sha256', $token);
        $is_blacklisted = get_transient('wp_auth_blacklist_' . $token_hash) !== false;
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[JWT] Check blacklist for token: ' . $token_hash . ' = ' . ($is_blacklisted ? 'true' : 'false'));
        }
        return $is_blacklisted;
    }
}
