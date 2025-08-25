<?php
/**
 * Swagger Documentation Handler for WP Authenticator
 * 
 * Provides OpenAPI/Swagger documentation for all REST API endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Auth_Swagger_Handler {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_swagger_routes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_swagger_assets'));
        add_action('admin_menu', array($this, 'add_swagger_admin_page'));
        add_action('init', array($this, 'handle_swagger_ui_requests'));
    }

    /**
     * Handle standalone Swagger UI requests
     */
    public function handle_swagger_ui_requests() {
        if (isset($_GET['wp_auth_swagger']) && $_GET['wp_auth_swagger'] === 'ui') {
            $this->serve_standalone_swagger_ui();
        }
    }

    /**
     * Serve standalone Swagger UI
     */
    private function serve_standalone_swagger_ui() {
        $swagger_url = get_rest_url(null, 'wp-auth/v1/swagger.json');
        
        // Set headers and output HTML
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }
        
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>WP Authenticator API Documentation</title>
            <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
            <style>
                html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
                *, *:before, *:after { box-sizing: inherit; }
                body { margin: 0; background: #fafafa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
                .swagger-ui .topbar { display: none; }
                .info-container {
                    padding: 20px;
                    background: #fff;
                    border-bottom: 1px solid #e8e8e8;
                    margin-bottom: 0;
                }
                .info-container h1 { margin: 0 0 10px 0; color: #3b4151; }
                .info-container p { margin: 5px 0; color: #666; }
                .loading { text-align: center; padding: 50px; color: #666; }
            </style>
        </head>
        <body>
            <div class="info-container">
                <h1>🚀 WP Authenticator API Documentation</h1>
                <p>Interactive API documentation for the WP Authenticator plugin.</p>
                <p><strong>Tip:</strong> Use the <code>/login</code> endpoint to get a JWT token, then click "Authorize" to test protected endpoints.</p>
            </div>
            <div id="swagger-ui">
                <div class="loading">Loading API Documentation...</div>
            </div>
            
            <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js" charset="UTF-8"></script>
            <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
            <script>
                window.onload = function() {
                    try {
                        console.log('Loading Swagger UI from: <?php echo esc_js($swagger_url); ?>');
                        
                        const ui = SwaggerUIBundle({
                            url: '<?php echo esc_js($swagger_url); ?>',
                            dom_id: '#swagger-ui',
                            deepLinking: true,
                            presets: [
                                SwaggerUIBundle.presets.apis,
                                SwaggerUIStandalonePreset
                            ],
                            plugins: [
                                SwaggerUIBundle.plugins.DownloadUrl
                            ],
                            layout: 'StandaloneLayout',
                            validatorUrl: null,
                            docExpansion: 'list',
                            defaultModelsExpandDepth: 1,
                            defaultModelExpandDepth: 1,
                            onComplete: function() {
                                console.log('Swagger UI loaded successfully');
                            },
                            onFailure: function(error) {
                                console.error('Swagger UI failed to load:', error);
                                document.getElementById('swagger-ui').innerHTML = 
                                    '<div style="padding: 20px; color: red;">Error loading API documentation. Please check the console for details.</div>';
                            }
                        });
                        
                    } catch (error) {
                        console.error('Swagger UI initialization failed:', error);
                        document.getElementById('swagger-ui').innerHTML = 
                            '<div style="padding: 20px; color: red;">Error initializing Swagger UI: ' + error.message + '</div>';
                    }
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Register Swagger-related REST API routes
     */
    public function register_swagger_routes() {
        // Swagger JSON endpoint
        register_rest_route('wp-auth/v1', '/swagger.json', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_swagger_json'),
            'permission_callback' => '__return_true',
        ));

        // Swagger UI endpoint - modified to work better with WordPress
        register_rest_route('wp-auth/v1', '/docs', array(
            'methods' => 'GET',
            'callback' => array($this, 'redirect_to_swagger_ui'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Get the OpenAPI/Swagger JSON specification
     */
    public function get_swagger_json() {
        $swagger_spec = $this->generate_swagger_spec();
        return rest_ensure_response($swagger_spec);
    }

    /**
     * Generate the complete OpenAPI specification
     */
    private function generate_swagger_spec() {
        $base_url = get_rest_url(null, 'wp-auth/v1');
        
        return array(
            'openapi' => '3.0.0',
            'info' => array(
                'title' => 'WP Authenticator API',
                'description' => 'Modern JWT-based authentication system for WordPress with REST API, OTP verification, and mobile app support.',
                'version' => WP_AUTHENTICATOR_VERSION,
                'contact' => array(
                    'name' => 'Chethan S Poojary',
                    'url' => 'https://github.com/itscsp/wp-authenticator',
                    'email' => 'support@wpauth.com'
                ),
                'license' => array(
                    'name' => 'GPL v2 or later',
                    'url' => 'https://www.gnu.org/licenses/gpl-2.0.html'
                )
            ),
            'servers' => array(
                array(
                    'url' => $base_url,
                    'description' => 'Current WordPress Site'
                )
            ),
            'components' => $this->get_swagger_components(),
            'paths' => $this->get_swagger_paths(),
            'tags' => array(
                array('name' => 'Authentication', 'description' => 'Login, logout, and token validation'),
                array('name' => 'Registration', 'description' => 'User registration processes (3-step and legacy)'),
                array('name' => 'OTP', 'description' => 'One-Time Password operations'),
                array('name' => 'Profile', 'description' => 'User profile management'),
                array('name' => 'Security', 'description' => 'Security statistics and admin features')
            )
        );
    }

    /**
     * Get Swagger components (schemas, security schemes, etc.)
     */
    private function get_swagger_components() {
        return array(
            'securitySchemes' => array(
                'bearerAuth' => array(
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT'
                )
            ),
            'schemas' => array(
                'User' => array(
                    'type' => 'object',
                    'properties' => array(
                        'ID' => array('type' => 'integer'),
                        'user_login' => array('type' => 'string'),
                        'user_email' => array('type' => 'string', 'format' => 'email'),
                        'display_name' => array('type' => 'string'),
                        'first_name' => array('type' => 'string'),
                        'last_name' => array('type' => 'string'),
                        'user_registered' => array('type' => 'string', 'format' => 'date-time')
                    )
                ),
                'LoginRequest' => array(
                    'type' => 'object',
                    'required' => array('username', 'password'),
                    'properties' => array(
                        'username' => array('type' => 'string', 'description' => 'Username or email'),
                        'password' => array('type' => 'string', 'description' => 'User password'),
                        'remember' => array('type' => 'boolean', 'description' => 'Remember login (optional)', 'default' => false)
                    )
                ),
                'LoginResponse' => array(
                    'type' => 'object',
                    'properties' => array(
                        'success' => array('type' => 'boolean'),
                        'message' => array('type' => 'string'),
                        'token' => array('type' => 'string', 'description' => 'JWT access token'),
                        'refresh_token' => array('type' => 'string', 'description' => 'JWT refresh token'),
                        'user' => array('$ref' => '#/components/schemas/User'),
                        'expires_in' => array('type' => 'integer', 'description' => 'Token expiration time in seconds')
                    )
                ),
                'RegisterStartRequest' => array(
                    'type' => 'object',
                    'required' => array('email', 'first_name'),
                    'properties' => array(
                        'email' => array('type' => 'string', 'format' => 'email'),
                        'first_name' => array('type' => 'string'),
                        'last_name' => array('type' => 'string')
                    )
                ),
                'RegisterStartResponse' => array(
                    'type' => 'object',
                    'properties' => array(
                        'success' => array('type' => 'boolean'),
                        'message' => array('type' => 'string'),
                        'session_token' => array('type' => 'string', 'description' => 'Registration session token'),
                        'expires_at' => array('type' => 'string', 'format' => 'date-time')
                    )
                ),
                'VerifyOTPRequest' => array(
                    'type' => 'object',
                    'required' => array('session_token', 'otp'),
                    'properties' => array(
                        'session_token' => array('type' => 'string'),
                        'otp' => array('type' => 'string', 'description' => '6-digit OTP code')
                    )
                ),
                'CompleteRegistrationRequest' => array(
                    'type' => 'object',
                    'required' => array('session_token', 'username', 'password'),
                    'properties' => array(
                        'session_token' => array('type' => 'string'),
                        'username' => array('type' => 'string'),
                        'password' => array('type' => 'string', 'minLength' => 6)
                    )
                ),
                'ErrorResponse' => array(
                    'type' => 'object',
                    'properties' => array(
                        'success' => array('type' => 'boolean', 'example' => false),
                        'message' => array('type' => 'string'),
                        'code' => array('type' => 'string'),
                        'data' => array('type' => 'object')
                    )
                )
            )
        );
    }

    /**
     * Get all API paths for Swagger documentation
     */
    private function get_swagger_paths() {
        return array(
            '/login' => $this->get_login_path(),
            '/logout' => $this->get_logout_path(),
            '/validate-token' => $this->get_validate_token_path(),
            '/register' => $this->get_legacy_register_path(),
            '/register/start' => $this->get_register_start_path(),
            '/register/verify-otp' => $this->get_register_verify_otp_path(),
            '/register/complete' => $this->get_register_complete_path(),
            '/register/status' => $this->get_register_status_path(),
            '/profile' => $this->get_profile_path(),
            '/verify-otp' => $this->get_verify_otp_path(),
            '/resend-otp' => $this->get_resend_otp_path(),
            '/otp-status' => $this->get_otp_status_path(),
            '/security/stats' => $this->get_security_stats_path()
        );
    }

    /**
     * Login endpoint documentation
     */
    private function get_login_path() {
        return array(
            'post' => array(
                'tags' => array('Authentication'),
                'summary' => 'Authenticate user and receive JWT token',
                'description' => 'Authenticates a user with username/email and password, returns JWT tokens for API access.',
                'requestBody' => array(
                    'required' => true,
                    'content' => array(
                        'application/json' => array(
                            'schema' => array('$ref' => '#/components/schemas/LoginRequest')
                        )
                    )
                ),
                'responses' => array(
                    '200' => array(
                        'description' => 'Login successful',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/LoginResponse')
                            )
                        )
                    ),
                    '401' => array(
                        'description' => 'Invalid credentials',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/ErrorResponse')
                            )
                        )
                    ),
                    '429' => array(
                        'description' => 'Too many failed attempts - temporarily blocked',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/ErrorResponse')
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Logout endpoint documentation
     */
    private function get_logout_path() {
        return array(
            'post' => array(
                'tags' => array('Authentication'),
                'summary' => 'Logout user and invalidate token',
                'description' => 'Invalidates the current JWT token and logs out the user.',
                'security' => array(array('bearerAuth' => array())),
                'responses' => array(
                    '200' => array(
                        'description' => 'Logout successful',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'success' => array('type' => 'boolean'),
                                        'message' => array('type' => 'string')
                                    )
                                )
                            )
                        )
                    ),
                    '401' => array(
                        'description' => 'Invalid or expired token',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/ErrorResponse')
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Token validation endpoint documentation
     */
    private function get_validate_token_path() {
        return array(
            'get' => array(
                'tags' => array('Authentication'),
                'summary' => 'Validate JWT token',
                'description' => 'Validates the current JWT token and returns user information if valid.',
                'security' => array(array('bearerAuth' => array())),
                'responses' => array(
                    '200' => array(
                        'description' => 'Token is valid',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'valid' => array('type' => 'boolean'),
                                        'user' => array('$ref' => '#/components/schemas/User')
                                    )
                                )
                            )
                        )
                    ),
                    '401' => array(
                        'description' => 'Invalid or expired token',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/ErrorResponse')
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Registration start endpoint documentation
     */
    private function get_register_start_path() {
        return array(
            'post' => array(
                'tags' => array('Registration'),
                'summary' => 'Start 3-step registration process (Step 1)',
                'description' => 'Begins the registration process by collecting user information and sending OTP via email.',
                'requestBody' => array(
                    'required' => true,
                    'content' => array(
                        'application/json' => array(
                            'schema' => array('$ref' => '#/components/schemas/RegisterStartRequest')
                        )
                    )
                ),
                'responses' => array(
                    '200' => array(
                        'description' => 'Registration started successfully, OTP sent',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/RegisterStartResponse')
                            )
                        )
                    ),
                    '400' => array(
                        'description' => 'Email already exists or validation error',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/ErrorResponse')
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Registration OTP verification endpoint documentation
     */
    private function get_register_verify_otp_path() {
        return array(
            'post' => array(
                'tags' => array('Registration'),
                'summary' => 'Verify OTP for registration (Step 2)',
                'description' => 'Verifies the OTP sent via email during registration process.',
                'requestBody' => array(
                    'required' => true,
                    'content' => array(
                        'application/json' => array(
                            'schema' => array('$ref' => '#/components/schemas/VerifyOTPRequest')
                        )
                    )
                ),
                'responses' => array(
                    '200' => array(
                        'description' => 'OTP verified successfully',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'success' => array('type' => 'boolean'),
                                        'message' => array('type' => 'string'),
                                        'verified' => array('type' => 'boolean')
                                    )
                                )
                            )
                        )
                    ),
                    '400' => array(
                        'description' => 'Invalid OTP or session token',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/ErrorResponse')
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Complete registration endpoint documentation
     */
    private function get_register_complete_path() {
        return array(
            'post' => array(
                'tags' => array('Registration'),
                'summary' => 'Complete registration process (Step 3)',
                'description' => 'Completes the registration by setting username and password, then automatically logs in the user.',
                'requestBody' => array(
                    'required' => true,
                    'content' => array(
                        'application/json' => array(
                            'schema' => array('$ref' => '#/components/schemas/CompleteRegistrationRequest')
                        )
                    )
                ),
                'responses' => array(
                    '200' => array(
                        'description' => 'Registration completed successfully, user logged in',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/LoginResponse')
                            )
                        )
                    ),
                    '400' => array(
                        'description' => 'Invalid session token or username already exists',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/ErrorResponse')
                            )
                        )
                    )
                )
            )
        );
    }

    // Add more endpoint documentation methods...
    private function get_register_status_path() {
        return array(
            'get' => array(
                'tags' => array('Registration'),
                'summary' => 'Check registration status',
                'description' => 'Check the current status of a registration session.',
                'parameters' => array(
                    array(
                        'name' => 'session_token',
                        'in' => 'query',
                        'required' => true,
                        'schema' => array('type' => 'string'),
                        'description' => 'Registration session token'
                    )
                ),
                'responses' => array(
                    '200' => array(
                        'description' => 'Registration status retrieved',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'status' => array('type' => 'string', 'enum' => array('started', 'verified', 'completed')),
                                        'expires_at' => array('type' => 'string', 'format' => 'date-time')
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    private function get_legacy_register_path() {
        return array(
            'post' => array(
                'tags' => array('Registration'),
                'summary' => 'Legacy single-step registration',
                'description' => 'Traditional single-step registration for backward compatibility.',
                'requestBody' => array(
                    'required' => true,
                    'content' => array(
                        'application/json' => array(
                            'schema' => array(
                                'type' => 'object',
                                'required' => array('username', 'email', 'password'),
                                'properties' => array(
                                    'username' => array('type' => 'string'),
                                    'email' => array('type' => 'string', 'format' => 'email'),
                                    'password' => array('type' => 'string'),
                                    'first_name' => array('type' => 'string'),
                                    'last_name' => array('type' => 'string')
                                )
                            )
                        )
                    )
                ),
                'responses' => array(
                    '201' => array(
                        'description' => 'User registered successfully',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/LoginResponse')
                            )
                        )
                    ),
                    '400' => array(
                        'description' => 'Registration failed - username or email already exists',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/ErrorResponse')
                            )
                        )
                    )
                )
            )
        );
    }

    private function get_profile_path() {
        return array(
            'get' => array(
                'tags' => array('Profile'),
                'summary' => 'Get user profile',
                'description' => 'Retrieve the current user\'s profile information.',
                'security' => array(array('bearerAuth' => array())),
                'responses' => array(
                    '200' => array(
                        'description' => 'Profile retrieved successfully',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/User')
                            )
                        )
                    ),
                    '401' => array(
                        'description' => 'Unauthorized',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/ErrorResponse')
                            )
                        )
                    )
                )
            ),
            'put' => array(
                'tags' => array('Profile'),
                'summary' => 'Update user profile',
                'description' => 'Update the current user\'s profile information.',
                'security' => array(array('bearerAuth' => array())),
                'requestBody' => array(
                    'required' => true,
                    'content' => array(
                        'application/json' => array(
                            'schema' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'first_name' => array('type' => 'string'),
                                    'last_name' => array('type' => 'string'),
                                    'email' => array('type' => 'string', 'format' => 'email'),
                                    'description' => array('type' => 'string')
                                )
                            )
                        )
                    )
                ),
                'responses' => array(
                    '200' => array(
                        'description' => 'Profile updated successfully',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('$ref' => '#/components/schemas/User')
                            )
                        )
                    )
                )
            )
        );
    }

    // Add placeholder methods for remaining endpoints
    private function get_verify_otp_path() {
        return array(
            'post' => array(
                'tags' => array('OTP'),
                'summary' => 'Verify OTP code',
                'description' => 'Verify a one-time password for email verification.',
                'requestBody' => array(
                    'required' => true,
                    'content' => array(
                        'application/json' => array(
                            'schema' => array(
                                'type' => 'object',
                                'required' => array('email', 'otp'),
                                'properties' => array(
                                    'email' => array('type' => 'string', 'format' => 'email'),
                                    'otp' => array('type' => 'string')
                                )
                            )
                        )
                    )
                ),
                'responses' => array(
                    '200' => array('description' => 'OTP verified successfully'),
                    '400' => array('description' => 'Invalid OTP')
                )
            )
        );
    }

    private function get_resend_otp_path() {
        return array(
            'post' => array(
                'tags' => array('OTP'),
                'summary' => 'Resend OTP',
                'description' => 'Resend OTP code to email address.',
                'requestBody' => array(
                    'required' => true,
                    'content' => array(
                        'application/json' => array(
                            'schema' => array(
                                'type' => 'object',
                                'required' => array('email'),
                                'properties' => array(
                                    'email' => array('type' => 'string', 'format' => 'email')
                                )
                            )
                        )
                    )
                ),
                'responses' => array(
                    '200' => array('description' => 'OTP sent successfully')
                )
            )
        );
    }

    private function get_otp_status_path() {
        return array(
            'get' => array(
                'tags' => array('OTP'),
                'summary' => 'Check OTP status',
                'description' => 'Check if an OTP is pending for an email address.',
                'parameters' => array(
                    array(
                        'name' => 'email',
                        'in' => 'query',
                        'required' => true,
                        'schema' => array('type' => 'string', 'format' => 'email')
                    )
                ),
                'responses' => array(
                    '200' => array('description' => 'OTP status retrieved')
                )
            )
        );
    }

    private function get_security_stats_path() {
        return array(
            'get' => array(
                'tags' => array('Security'),
                'summary' => 'Get security statistics (Admin only)',
                'description' => 'Retrieve security statistics and monitoring data.',
                'security' => array(array('bearerAuth' => array())),
                'responses' => array(
                    '200' => array(
                        'description' => 'Security statistics retrieved',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'total_logins' => array('type' => 'integer'),
                                        'failed_attempts' => array('type' => 'integer'),
                                        'blocked_ips' => array('type' => 'array')
                                    )
                                )
                            )
                        )
                    ),
                    '403' => array('description' => 'Insufficient permissions')
                )
            )
        );
    }

    /**
     * Redirect to standalone Swagger UI page
     */
    public function redirect_to_swagger_ui() {
        $swagger_page_url = site_url('/?wp_auth_swagger=ui');
        return new WP_REST_Response(array(
            'message' => 'Redirecting to Swagger UI...',
            'swagger_ui_url' => $swagger_page_url,
            'instructions' => 'If you are not automatically redirected, visit: ' . $swagger_page_url
        ), 302, array(
            'Location' => $swagger_page_url
        ));
    }

    /**
     * Serve Swagger UI HTML page
     */
    public function get_swagger_ui() {
        $swagger_url = rest_url('wp-auth/v1/swagger.json');
        
        // Set proper headers
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        // Output HTML directly and exit
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP Authenticator API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        html { 
            box-sizing: border-box; 
            overflow: -moz-scrollbars-vertical; 
            overflow-y: scroll; 
        }
        *, *:before, *:after { 
            box-sizing: inherit; 
        }
        body { 
            margin: 0; 
            background: #fafafa; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .swagger-ui .topbar { 
            display: none; 
        }
        .info-container {
            padding: 20px;
            background: #fff;
            border-bottom: 1px solid #e8e8e8;
            margin-bottom: 0;
        }
        .info-container h1 {
            margin: 0 0 10px 0;
            color: #3b4151;
        }
        .info-container p {
            margin: 5px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="info-container">
        <h1>🚀 WP Authenticator API Documentation</h1>
        <p>Interactive API documentation for the WP Authenticator plugin.</p>
        <p><strong>Tip:</strong> Use the <code>/login</code> endpoint to get a JWT token, then click "Authorize" to test protected endpoints.</p>
    </div>
    <div id="swagger-ui"></div>
    
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js" charset="UTF-8"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
    <script>
        window.onload = function() {
            try {
                const ui = SwaggerUIBundle({
                    url: "' . $swagger_url . '",
                    dom_id: "#swagger-ui",
                    deepLinking: true,
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIStandalonePreset
                    ],
                    plugins: [
                        SwaggerUIBundle.plugins.DownloadUrl
                    ],
                    layout: "StandaloneLayout",
                    validatorUrl: null,
                    docExpansion: "list",
                    defaultModelsExpandDepth: 1,
                    defaultModelExpandDepth: 1
                });
                
                // Add some custom styling after initialization
                setTimeout(function() {
                    const infoDiv = document.querySelector(".info");
                    if (infoDiv) {
                        infoDiv.style.marginTop = "0";
                    }
                }, 1000);
                
            } catch (error) {
                console.error("Swagger UI initialization failed:", error);
                document.getElementById("swagger-ui").innerHTML = 
                    "<div style=\"padding: 20px; color: red;\">Error loading Swagger UI. Please check console for details.</div>";
            }
        };
    </script>
</body>
</html>';

        // For WordPress REST API, we need to properly handle HTML output
        echo $html;
        exit;
    }

    /**
     * Add admin page for Swagger documentation
     */
    public function add_swagger_admin_page() {
        add_submenu_page(
            'options-general.php',
            'WP Authenticator API Documentation',
            'API Docs',
            'manage_options',
            'wp-auth-api-docs',
            array($this, 'swagger_admin_page')
        );
    }

    /**
     * Render admin page for Swagger documentation
     */
    public function swagger_admin_page() {
        $swagger_url = site_url('/?wp_auth_swagger=ui');
        echo '<div class="wrap">';
        echo '<h1>WP Authenticator API Documentation</h1>';
        echo '<p>Interactive API documentation for WP Authenticator endpoints.</p>';
        echo '<p><a href="' . esc_url($swagger_url) . '" target="_blank" class="button button-primary">Open API Documentation</a></p>';
        echo '<iframe src="' . esc_url($swagger_url) . '" style="width: 100%; height: 800px; border: 1px solid #ccc;"></iframe>';
        echo '</div>';
    }

    /**
     * Enqueue Swagger assets if needed
     */
    public function enqueue_swagger_assets() {
        // Assets are loaded via CDN in the HTML, no need to enqueue locally
    }
}

// Initialize Swagger handler
new WP_Auth_Swagger_Handler();
