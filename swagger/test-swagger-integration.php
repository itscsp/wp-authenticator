<?php
/**
 * Test Swagger Integration for WP Authenticator
 * 
 * This script tests the Swagger endpoints and validates the OpenAPI specification
 * Run this from your WordPress root directory or ensure WordPress is loaded
 */

// If running standalone, load WordPress
if (!defined('ABSPATH')) {
    // Adjust this path to your WordPress installation
    require_once dirname(__FILE__) . '/../../../wp-load.php';
}

class WP_Auth_Swagger_Test {
    
    private $base_url;
    
    public function __construct() {
        $this->base_url = get_rest_url(null, 'wp-auth/v1');
    }
    
    /**
     * Run all tests
     */
    public function run_tests() {
        echo "🧪 Testing WP Authenticator Swagger Integration\n";
        echo "==============================================\n\n";
        
        $this->test_swagger_json_endpoint();
        $this->test_swagger_ui_endpoint();
        $this->test_openapi_spec_validation();
        
        echo "\n✅ All Swagger tests completed!\n";
    }
    
    /**
     * Test the Swagger JSON endpoint
     */
    private function test_swagger_json_endpoint() {
        echo "📋 Testing Swagger JSON Endpoint...\n";
        
        $response = wp_remote_get($this->base_url . '/swagger.json');
        
        if (is_wp_error($response)) {
            echo "❌ Error: " . $response->get_error_message() . "\n";
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            echo "❌ Error: Expected 200, got $status_code\n";
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);
        
        if (!$json) {
            echo "❌ Error: Invalid JSON response\n";
            return;
        }
        
        // Validate basic OpenAPI structure
        $required_fields = ['openapi', 'info', 'paths'];
        foreach ($required_fields as $field) {
            if (!isset($json[$field])) {
                echo "❌ Error: Missing required field '$field'\n";
                return;
            }
        }
        
        echo "✅ Swagger JSON endpoint working correctly\n";
        echo "   - OpenAPI Version: " . $json['openapi'] . "\n";
        echo "   - API Title: " . $json['info']['title'] . "\n";
        echo "   - API Version: " . $json['info']['version'] . "\n";
        echo "   - Total Endpoints: " . count($json['paths']) . "\n\n";
    }
    
    /**
     * Test the Swagger UI endpoint
     */
    private function test_swagger_ui_endpoint() {
        echo "🎨 Testing Swagger UI Endpoint...\n";
        
        $response = wp_remote_get($this->base_url . '/docs');
        
        if (is_wp_error($response)) {
            echo "❌ Error: " . $response->get_error_message() . "\n";
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            echo "❌ Error: Expected 200, got $status_code\n";
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Check for Swagger UI elements
        $required_elements = [
            'swagger-ui-bundle.js',
            'swagger-ui.css',
            'SwaggerUIBundle',
            'WP Authenticator API Documentation'
        ];
        
        foreach ($required_elements as $element) {
            if (strpos($body, $element) === false) {
                echo "❌ Error: Missing element '$element' in Swagger UI\n";
                return;
            }
        }
        
        echo "✅ Swagger UI endpoint working correctly\n";
        echo "   - HTML page served successfully\n";
        echo "   - All required Swagger UI assets referenced\n";
        echo "   - Page title: 'WP Authenticator API Documentation'\n\n";
    }
    
    /**
     * Validate the OpenAPI specification structure
     */
    private function test_openapi_spec_validation() {
        echo "🔍 Validating OpenAPI Specification Structure...\n";
        
        $response = wp_remote_get($this->base_url . '/swagger.json');
        $body = wp_remote_retrieve_body($response);
        $spec = json_decode($body, true);
        
        $errors = [];
        
        // Validate info section
        if (!isset($spec['info']['title']) || empty($spec['info']['title'])) {
            $errors[] = "Missing or empty info.title";
        }
        
        if (!isset($spec['info']['version']) || empty($spec['info']['version'])) {
            $errors[] = "Missing or empty info.version";
        }
        
        // Validate paths
        if (!isset($spec['paths']) || !is_array($spec['paths']) || empty($spec['paths'])) {
            $errors[] = "Missing or empty paths";
        }
        
        // Check for required endpoints
        $required_endpoints = [
            '/login',
            '/logout', 
            '/register/start',
            '/register/verify-otp',
            '/register/complete',
            '/profile'
        ];
        
        foreach ($required_endpoints as $endpoint) {
            if (!isset($spec['paths'][$endpoint])) {
                $errors[] = "Missing endpoint: $endpoint";
            }
        }
        
        // Validate components
        if (!isset($spec['components']['schemas']) || empty($spec['components']['schemas'])) {
            $errors[] = "Missing or empty components.schemas";
        }
        
        // Check for security schemes
        if (!isset($spec['components']['securitySchemes']['bearerAuth'])) {
            $errors[] = "Missing JWT bearer authentication scheme";
        }
        
        if (!empty($errors)) {
            echo "❌ OpenAPI Specification validation failed:\n";
            foreach ($errors as $error) {
                echo "   - $error\n";
            }
            return;
        }
        
        echo "✅ OpenAPI Specification validation passed\n";
        echo "   - All required sections present\n";
        echo "   - All main endpoints documented\n";
        echo "   - Security schemes properly defined\n";
        echo "   - Component schemas available\n\n";
    }
    
    /**
     * Display usage instructions
     */
    public function display_usage_info() {
        echo "🚀 Swagger Integration Ready!\n";
        echo "============================\n\n";
        
        echo "📋 Access Points:\n";
        echo "• Swagger UI: " . $this->base_url . "/docs\n";
        echo "• OpenAPI JSON: " . $this->base_url . "/swagger.json\n";
        echo "• Admin Page: WordPress Admin → WP Authenticator → API Docs\n\n";
        
        echo "🔧 Quick Start:\n";
        echo "1. Open Swagger UI in your browser\n";
        echo "2. Use /login endpoint to get JWT token\n";
        echo "3. Click 'Authorize' and enter: Bearer YOUR_TOKEN\n";
        echo "4. Test protected endpoints\n\n";
        
        echo "📚 Documentation: docs/swagger-integration.md\n\n";
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new WP_Auth_Swagger_Test();
    $tester->run_tests();
    $tester->display_usage_info();
}
