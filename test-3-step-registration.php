<?php
/**
 * Test script for 3-step registration process
 * 
 * This script demonstrates how to test the new 3-step registration endpoints.
 * Run this in a WordPress environment or modify URLs for your setup.
 * 
 * Updated for new endpoint organization structure:
 * - Registration endpoints are now in: includes/endpoints/registration/
 * - OTP endpoints are now in: includes/endpoints/otp/
 * - Auth endpoints are now in: includes/endpoints/auth/
 */

// Test configuration
$base_url = 'http://your-site.com/wp-json/wp-auth/v1';
$test_email = 'test+' . time() . '@example.com';
$test_data = array(
    'first_name' => 'Test',
    'last_name' => 'User',
    'username' => 'testuser' . time(),
    'password' => 'testpassword123'
);

echo "=== WP Authenticator 3-Step Registration Test ===\n\n";

// Helper function to make HTTP requests
function make_request($url, $data = null, $method = 'GET') {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'User-Agent: WP-Auth-Test/1.0'
    ));
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return array(
        'status_code' => $http_code,
        'body' => json_decode($response, true)
    );
}

// Test Step 1: Start Registration
echo "Step 1: Starting registration...\n";
$step1_response = make_request($base_url . '/register/start', array(
    'email' => $test_email,
    'first_name' => $test_data['first_name'],
    'last_name' => $test_data['last_name']
), 'POST');

if ($step1_response['status_code'] === 200 && $step1_response['body']['success']) {
    echo "✓ Step 1 successful\n";
    echo "Session Token: " . $step1_response['body']['data']['session_token'] . "\n";
    echo "OTP sent to: " . $test_email . "\n\n";
    
    $session_token = $step1_response['body']['data']['session_token'];
    
    // In a real scenario, user would check email for OTP
    echo "Note: Check your email for the OTP code, then manually test steps 2 and 3\n";
    echo "Or if testing in development with OTP in response, use the OTP below:\n";
    if (isset($step1_response['body']['data']['otp'])) {
        echo "Development OTP: " . $step1_response['body']['data']['otp'] . "\n";
    }
    
} else {
    echo "✗ Step 1 failed\n";
    echo "Status: " . $step1_response['status_code'] . "\n";
    echo "Response: " . json_encode($step1_response['body'], JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

// Test Session Status
echo "\nTesting session status...\n";
$status_response = make_request($base_url . '/register/status?session_token=' . urlencode($session_token));

if ($status_response['status_code'] === 200 && $status_response['body']['success']) {
    echo "✓ Session status check successful\n";
    echo "Current step: " . $status_response['body']['data']['current_step'] . "\n";
    echo "Next action: " . $status_response['body']['data']['next_action'] . "\n\n";
} else {
    echo "✗ Session status check failed\n";
    echo "Response: " . json_encode($status_response['body'], JSON_PRETTY_PRINT) . "\n";
}

// Manual testing instructions
echo "=== Manual Testing Instructions ===\n";
echo "1. Check email for OTP code\n";
echo "2. Test Step 2 with:\n";
echo "   POST {$base_url}/register/verify-otp\n";
echo "   Body: {\n";
echo "     \"session_token\": \"{$session_token}\",\n";
echo "     \"otp\": \"YOUR_OTP_HERE\"\n";
echo "   }\n\n";

echo "3. Test Step 3 with:\n";
echo "   POST {$base_url}/register/complete\n";
echo "   Body: {\n";
echo "     \"session_token\": \"{$session_token}\",\n";
echo "     \"username\": \"{$test_data['username']}\",\n";
echo "     \"password\": \"{$test_data['password']}\"\n";
echo "   }\n";
echo "   Expected Response: User account created + JWT token + refresh token\n\n";

echo "=== cURL Examples ===\n";
echo "Step 2:\n";
echo "curl -X POST {$base_url}/register/verify-otp \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\n";
echo "    \"session_token\": \"{$session_token}\",\n";
echo "    \"otp\": \"123456\"\n";
echo "  }'\n\n";

echo "Step 3:\n";
echo "curl -X POST {$base_url}/register/complete \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\n";
echo "    \"session_token\": \"{$session_token}\",\n";
echo "    \"username\": \"{$test_data['username']}\",\n";
echo "    \"password\": \"{$test_data['password']}\"\n";
echo "  }'\n\n";

echo "Test completed. Session token valid for 30 minutes.\n";
?>
