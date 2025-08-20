<?php
/**
 * JWT Authentication Examples
 * 
 * ⚠️  SECURITY WARNING ⚠️
 * 
 * This is a custom JWT implementation for demonstration purposes.
 * For production applications, consider using maintained libraries like:
 * - firebase/php-jwt (https://github.com/firebase/php-jwt)
 * - lcobucci/jwt (https://github.com/lcobucci/jwt)
 * 
 * IMPORTANT SECURITY REQUIREMENTS:
 * - ALWAYS use HTTPS in production
 * - Implement proper token expiration
 * - Use secure secret keys (64+ characters)
 * - Implement token revocation/blacklisting
 * - Validate issuer and audience claims
 * - Monitor for security vulnerabilities
 * 
 * This file shows examples of how to use the JWT authentication API endpoints
 * Do not include this file in production - it's for demonstration only
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Example 1: User Login
 * POST /wp-json/wp-auth/v1/login
 */
function example_login() {
    $login_data = array(
        'username' => 'your_username',
        'password' => 'your_password'
    );
    
    $response = wp_remote_post(home_url('/wp-json/wp-auth/v1/login'), array(
        'body' => json_encode($login_data),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
    ));
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['success']) {
        // Store tokens for future requests
        $access_token = $data['data']['token'];
        $refresh_token = $data['data']['refresh_token'];
        $expires = $data['data']['expires'];
        
        echo "Login successful! Token expires at: " . date('Y-m-d H:i:s', $expires);
        return $access_token;
    } else {
        echo "Login failed: " . $data['message'];
        return false;
    }
}

/**
 * Example 2: Making authenticated requests
 */
function example_authenticated_request($token) {
    $response = wp_remote_get(home_url('/wp-json/wp-auth/v1/profile'), array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ),
    ));
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['success']) {
        echo "User profile retrieved: " . $data['data']['username'];
        return $data['data'];
    } else {
        echo "Request failed: " . $data['message'];
        return false;
    }
}

/**
 * Example 3: Token validation
 */
function example_validate_token($token) {
    $response = wp_remote_post(home_url('/wp-json/wp-auth/v1/validate-token'), array(
        'body' => json_encode(array('token' => $token)),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
    ));
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['success']) {
        echo "Token is valid for user ID: " . $data['data']['user_id'];
        return true;
    } else {
        echo "Token validation failed: " . $data['message'];
        return false;
    }
}

/**
 * Example 4: Refresh token
 */
function example_refresh_token($refresh_token) {
    $response = wp_remote_post(home_url('/wp-json/wp-auth/v1/refresh-token'), array(
        'body' => json_encode(array('refresh_token' => $refresh_token)),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
    ));
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['success']) {
        $new_token = $data['data']['token'];
        $new_refresh_token = $data['data']['refresh_token'];
        echo "Token refreshed successfully!";
        return array(
            'token' => $new_token,
            'refresh_token' => $new_refresh_token
        );
    } else {
        echo "Token refresh failed: " . $data['message'];
        return false;
    }
}

/**
 * Example 5: User Registration
 */
function example_register() {
    $register_data = array(
        'username' => 'new_username',
        'email' => 'user@example.com',
        'password' => 'secure_password123'
    );
    
    $response = wp_remote_post(home_url('/wp-json/wp-auth/v1/register'), array(
        'body' => json_encode($register_data),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
    ));
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['success']) {
        echo "Registration successful! User ID: " . $data['data']['user_id'];
        return $data['data'];
    } else {
        echo "Registration failed: " . $data['message'];
        return false;
    }
}

/**
 * Example 6: Update user profile
 */
function example_update_profile($token) {
    $update_data = array(
        'first_name' => 'John',
        'last_name' => 'Doe',
        'description' => 'Updated bio'
    );
    
    $response = wp_remote_post(home_url('/wp-json/wp-auth/v1/profile'), array(
        'method' => 'PUT',
        'body' => json_encode($update_data),
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ),
    ));
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['success']) {
        echo "Profile updated successfully!";
        return true;
    } else {
        echo "Profile update failed: " . $data['message'];
        return false;
    }
}

/**
 * Example 7: Secure Logout with Token Revocation
 */
function example_logout($token, $refresh_token = null) {
    $logout_data = array(
        'token' => $token
    );
    
    // Include refresh token if available for complete logout
    if ($refresh_token) {
        $logout_data['refresh_token'] = $refresh_token;
    }
    
    $response = wp_remote_post(home_url('/wp-json/wp-auth/v1/logout'), array(
        'body' => json_encode($logout_data),
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ),
    ));
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data['success']) {
        echo "Logout successful! Tokens have been revoked.";
        // Clear stored tokens
        // localStorage.removeItem('access_token');
        // localStorage.removeItem('refresh_token');
        return true;
    } else {
        echo "Logout failed: " . $data['message'];
        return false;
    }
}

/**
 * JavaScript/AJAX Example for frontend usage
 */
function example_javascript_usage() {
    ?>
    <script>
    // Login example
    async function login(username, password) {
        try {
            const response = await fetch('/wp-json/wp-auth/v1/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Store tokens in localStorage or secure storage
                localStorage.setItem('access_token', data.data.token);
                localStorage.setItem('refresh_token', data.data.refresh_token);
                console.log('Login successful!');
                return data.data.token;
            } else {
                console.error('Login failed:', data.message);
                return null;
            }
        } catch (error) {
            console.error('Login error:', error);
            return null;
        }
    }
    
    // Authenticated request example
    async function getProfile() {
        const token = localStorage.getItem('access_token');
        
        if (!token) {
            console.error('No access token found');
            return null;
        }
        
        try {
            const response = await fetch('/wp-json/wp-auth/v1/profile', {
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('Profile data:', data.data);
                return data.data;
            } else {
                console.error('Failed to get profile:', data.message);
                // Token might be expired, try to refresh
                if (data.message.includes('expired')) {
                    await refreshToken();
                }
                return null;
            }
        } catch (error) {
            console.error('Profile request error:', error);
            return null;
        }
    }
    
    // Token refresh example
    async function refreshToken() {
        const refreshToken = localStorage.getItem('refresh_token');
        
        if (!refreshToken) {
            console.error('No refresh token found');
            return false;
        }
        
        try {
            const response = await fetch('/wp-json/wp-auth/v1/refresh-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    refresh_token: refreshToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                localStorage.setItem('access_token', data.data.token);
                localStorage.setItem('refresh_token', data.data.refresh_token);
                console.log('Token refreshed successfully!');
                return true;
            } else {
                console.error('Token refresh failed:', data.message);
                // Refresh token is invalid, redirect to login
                localStorage.removeItem('access_token');
                localStorage.removeItem('refresh_token');
                return false;
            }
        } catch (error) {
            console.error('Token refresh error:', error);
            return false;
        }
    }
    
    // Secure logout with token revocation
    async function logout() {
        const token = localStorage.getItem('access_token');
        const refreshToken = localStorage.getItem('refresh_token');
        
        if (!token) {
            console.error('No access token found');
            // Still clear local storage
            localStorage.removeItem('access_token');
            localStorage.removeItem('refresh_token');
            return true;
        }
        
        try {
            const logoutData = { token: token };
            if (refreshToken) {
                logoutData.refresh_token = refreshToken;
            }
            
            const response = await fetch('/wp-json/wp-auth/v1/logout', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(logoutData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('Logout successful! Tokens revoked.');
            } else {
                console.error('Logout failed:', data.message);
            }
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            // Always clear local storage regardless of API response
            localStorage.removeItem('access_token');
            localStorage.removeItem('refresh_token');
            console.log('Local tokens cleared');
        }
        
        return true;
    }
    </script>
    <?php
}

/**
 * curl command examples for testing
 */
function example_curl_commands() {
    echo "
    <!-- CURL Examples for testing JWT Authentication -->
    
    <!-- 1. Login -->
    curl -X POST http://your-site.com/wp-json/wp-auth/v1/login \\
      -H 'Content-Type: application/json' \\
      -d '{
        \"username\": \"your_username\",
        \"password\": \"your_password\"
      }'
    
    <!-- 2. Get Profile (authenticated) -->
    curl -X GET http://your-site.com/wp-json/wp-auth/v1/profile \\
      -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\
      -H 'Content-Type: application/json'
    
    <!-- 3. Validate Token -->
    curl -X POST http://your-site.com/wp-json/wp-auth/v1/validate-token \\
      -H 'Content-Type: application/json' \\
      -d '{
        \"token\": \"YOUR_JWT_TOKEN\"
      }'
    
    <!-- 4. Refresh Token -->
    curl -X POST http://your-site.com/wp-json/wp-auth/v1/refresh-token \\
      -H 'Content-Type: application/json' \\
      -d '{
        \"refresh_token\": \"YOUR_REFRESH_TOKEN\"
      }'
    
    <!-- 5. Register New User -->
    curl -X POST http://your-site.com/wp-json/wp-auth/v1/register \\
      -H 'Content-Type: application/json' \\
      -d '{
        \"username\": \"newuser\",
        \"email\": \"newuser@example.com\",
        \"password\": \"securepassword123\"
      }'
    
    <!-- 6. Update Profile (authenticated) -->
    curl -X PUT http://your-site.com/wp-json/wp-auth/v1/profile \\
      -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\
      -H 'Content-Type: application/json' \\
      -d '{
        \"first_name\": \"John\",
        \"last_name\": \"Doe\",
        \"description\": \"Updated bio\"
      }'
    ";
}
