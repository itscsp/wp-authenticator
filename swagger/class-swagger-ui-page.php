<?php
/**
 * Swagger UI Page Template
 * 
 * This creates a standalone page for Swagger UI that can be accessed directly
 */

// Prevent direct access when included as a file
if (!defined('ABSPATH') && !isset($_GET['swagger'])) {
    exit('Direct access not allowed');
}

// If accessed with swagger parameter, serve the UI
if (isset($_GET['swagger']) && $_GET['swagger'] === 'ui') {
    $swagger_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/wp-json/wp-auth/v1/swagger.json';
    
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
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
            .loading {
                text-align: center;
                padding: 50px;
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
        <div id="swagger-ui">
            <div class="loading">Loading API Documentation...</div>
        </div>
        
        <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js" charset="UTF-8"></script>
        <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
        <script>
            window.onload = function() {
                try {
                    console.log('Initializing Swagger UI with URL:', '<?php echo $swagger_url; ?>');
                    
                    const ui = SwaggerUIBundle({
                        url: '<?php echo $swagger_url; ?>',
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
                                '<div style="padding: 20px; color: red;">Error loading API documentation: ' + error + '</div>';
                        }
                    });
                    
                } catch (error) {
                    console.error('Swagger UI initialization failed:', error);
                    document.getElementById('swagger-ui').innerHTML = 
                        '<div style="padding: 20px; color: red;">Error initializing Swagger UI. Please check console for details.</div>';
                }
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Alternative Swagger Handler with improved WordPress compatibility
 */
class WP_Auth_Swagger_Handler_Alt {
    
    public function __construct() {
        add_action('init', array($this, 'handle_swagger_requests'));
        add_action('rest_api_init', array($this, 'register_swagger_routes'));
        add_action('admin_menu', array($this, 'add_swagger_admin_page'));
    }

    /**
     * Handle direct Swagger UI requests
     */
    public function handle_swagger_requests() {
        // Check if this is a Swagger UI request
        if (isset($_GET['wp_auth_swagger']) && $_GET['wp_auth_swagger'] === 'ui') {
            $this->serve_swagger_ui();
        }
    }

    /**
     * Serve Swagger UI directly
     */
    private function serve_swagger_ui() {
        $swagger_url = site_url('/wp-json/wp-auth/v1/swagger.json');
        
        header('Content-Type: text/html; charset=UTF-8');
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
            </style>
        </head>
        <body>
            <div class="info-container">
                <h1>🚀 WP Authenticator API Documentation</h1>
                <p>Interactive API documentation for the WP Authenticator plugin.</p>
                <p><strong>Tip:</strong> Use the <code>/login</code> endpoint to get a JWT token, then click "Authorize" to test protected endpoints.</p>
            </div>
            <div id="swagger-ui">Loading...</div>
            
            <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
            <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js"></script>
            <script>
                window.onload = function() {
                    const ui = SwaggerUIBundle({
                        url: '<?php echo esc_url($swagger_url); ?>',
                        dom_id: '#swagger-ui',
                        deepLinking: true,
                        presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
                        plugins: [SwaggerUIBundle.plugins.DownloadUrl],
                        layout: 'StandaloneLayout'
                    });
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Register REST API routes
     */
    public function register_swagger_routes() {
        // Only register the JSON endpoint, handle UI differently
        register_rest_route('wp-auth/v1', '/swagger.json', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_swagger_json'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Get the OpenAPI JSON specification
     */
    public function get_swagger_json() {
        // Use the same swagger spec generation from the main handler
        $handler = new WP_Auth_Swagger_Handler();
        return $handler->get_swagger_json();
    }

    /**
     * Add admin page
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
     * Render admin page
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
}

// Only initialize if the main handler exists
if (class_exists('WP_Auth_Swagger_Handler')) {
    new WP_Auth_Swagger_Handler_Alt();
}
