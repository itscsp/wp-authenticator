<?php
/**
 * JWT Handler Class
 * 
 * Handles JWT token generation, validation, and management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Auth_JWT_Handler {
    
    private $secret_key;
    private $algorithm = 'HS256';
    private $expiration_time = 86400; // 24 hours in seconds
    
    public function __construct() {
        $this->secret_key = $this->get_secret_key();
    }
    
    /**
     * Get or generate secret key for JWT
     */
    private function get_secret_key() {
        $secret = get_option('wp_auth_jwt_secret');
        
        if (empty($secret)) {
            // Generate a secure random secret key
            $secret = wp_generate_password(64, true, true);
            update_option('wp_auth_jwt_secret', $secret);
        }
        
        return $secret;
    }
    
    /**
     * Generate JWT token for user
     */
    public function generate_token($user_id, $additional_claims = array()) {
        $issued_at = time();
        $expiration = $issued_at + $this->expiration_time;
        
        $payload = array(
            'iss' => get_site_url(), // Issuer
            'aud' => get_site_url(), // Audience
            'iat' => $issued_at,     // Issued at
            'exp' => $expiration,    // Expiration
            'user_id' => $user_id,
            'user_login' => get_userdata($user_id)->user_login
        );
        
        // Add any additional claims
        if (!empty($additional_claims)) {
            $payload = array_merge($payload, $additional_claims);
        }
        
        return $this->encode($payload);
    }
    
    /**
     * Validate and decode JWT token
     */
    public function validate_token($token) {
        try {
            $payload = $this->decode($token);
            
            // Check if token is expired
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return new WP_Error('token_expired', 'Token has expired');
            }
            
            // Check if user still exists
            if (isset($payload['user_id'])) {
                $user = get_userdata($payload['user_id']);
                if (!$user) {
                    return new WP_Error('user_not_found', 'User no longer exists');
                }
            }
            
            return $payload;
            
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid token: ' . $e->getMessage());
        }
    }
    
    /**
     * Refresh JWT token
     */
    public function refresh_token($token) {
        $payload = $this->validate_token($token);
        
        if (is_wp_error($payload)) {
            return $payload;
        }
        
        // Generate new token with same user data
        return $this->generate_token($payload['user_id']);
    }
    
    /**
     * Encode JWT token
     */
    private function encode($payload) {
        $header = array(
            'typ' => 'JWT',
            'alg' => $this->algorithm
        );
        
        $header_encoded = $this->base64url_encode(json_encode($header));
        $payload_encoded = $this->base64url_encode(json_encode($payload));
        
        $signature = $this->generate_signature($header_encoded . '.' . $payload_encoded);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature;
    }
    
    /**
     * Decode JWT token
     */
    private function decode($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        list($header_encoded, $payload_encoded, $signature) = $parts;
        
        // Verify signature
        $expected_signature = $this->generate_signature($header_encoded . '.' . $payload_encoded);
        if (!hash_equals($signature, $expected_signature)) {
            throw new Exception('Invalid token signature');
        }
        
        // Decode header and payload
        $header = json_decode($this->base64url_decode($header_encoded), true);
        $payload = json_decode($this->base64url_decode($payload_encoded), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in token');
        }
        
        // Verify algorithm
        if (!isset($header['alg']) || $header['alg'] !== $this->algorithm) {
            throw new Exception('Invalid algorithm');
        }
        
        return $payload;
    }
    
    /**
     * Generate HMAC signature
     */
    private function generate_signature($data) {
        $signature = hash_hmac('sha256', $data, $this->secret_key, true);
        return $this->base64url_encode($signature);
    }
    
    /**
     * Base64 URL encode
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Extract token from Authorization header
     */
    public function get_token_from_header() {
        $auth_header = null;
        
        // Check different ways the header might be set
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
        
        if (!$auth_header) {
            return null;
        }
        
        // Extract token from "Bearer TOKEN" format
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
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
            return null;
        }
        
        $payload = $this->validate_token($token);
        
        if (is_wp_error($payload)) {
            return null;
        }
        
        return isset($payload['user_id']) ? $payload['user_id'] : null;
    }
    
    /**
     * Check if current request has valid JWT
     */
    public function is_authenticated() {
        $user_id = $this->get_user_id_from_token();
        return !empty($user_id);
    }
    
    /**
     * Set expiration time for tokens (in seconds)
     */
    public function set_expiration_time($seconds) {
        $this->expiration_time = intval($seconds);
    }
    
    /**
     * Get token expiration time
     */
    public function get_expiration_time() {
        return $this->expiration_time;
    }
    
    /**
     * Generate refresh token (longer lived)
     */
    public function generate_refresh_token($user_id) {
        $old_expiration = $this->expiration_time;
        $this->expiration_time = 604800; // 7 days
        
        $token = $this->generate_token($user_id, array('type' => 'refresh'));
        
        $this->expiration_time = $old_expiration; // Reset
        
        return $token;
    }
    
    /**
     * Validate refresh token and generate new access token
     */
    public function refresh_access_token($refresh_token) {
        $payload = $this->validate_token($refresh_token);
        
        if (is_wp_error($payload)) {
            return $payload;
        }
        
        if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
            return new WP_Error('invalid_refresh_token', 'Not a valid refresh token');
        }
        
        // Generate new access token
        return $this->generate_token($payload['user_id']);
    }
    
    /**
     * Blacklist a token (simple implementation using transients)
     */
    public function blacklist_token($token) {
        $payload = $this->decode($token);
        if (isset($payload['exp'])) {
            $remaining_time = $payload['exp'] - time();
            if ($remaining_time > 0) {
                // Store token hash in blacklist until it expires
                $token_hash = hash('sha256', $token);
                set_transient('wp_auth_blacklist_' . $token_hash, true, $remaining_time);
            }
        }
    }
    
    /**
     * Check if token is blacklisted
     */
    public function is_token_blacklisted($token) {
        $token_hash = hash('sha256', $token);
        return get_transient('wp_auth_blacklist_' . $token_hash) !== false;
    }
    
    /**
     * Validate token with blacklist check
     */
    public function validate_token_with_blacklist($token) {
        if ($this->is_token_blacklisted($token)) {
            return new WP_Error('token_blacklisted', 'Token has been revoked');
        }
        
        return $this->validate_token($token);
    }
}
