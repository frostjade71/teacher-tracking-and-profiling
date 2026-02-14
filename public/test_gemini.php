<?php
// test_gemini.php - Quick API test script
// Place this in the project root and access via browser

require __DIR__ . '/app/db.php';
require __DIR__ . '/app/helpers/gemini_helper.php';

header('Content-Type: text/plain');

echo "=== Gemini API Test ===\n\n";

// Test 1: Check API key
$apiKey = getenv('GEMINI_API_KEY');
echo "1. API Key Check:\n";
echo "   Status: " . ($apiKey ? "✓ Found" : "✗ Missing") . "\n";
echo "   Length: " . strlen($apiKey) . " characters\n";
echo "   Starts with: " . substr($apiKey, 0, 10) . "...\n\n";

// Test 2: Simple API call
echo "2. Testing Gemini API Call:\n";
$testMessage = "Hello, respond with just 'OK' if you can read this.";
$testContext = "Test context data.\n";

$response = callGeminiAPI($testMessage, $testContext);

echo "   Success: " . ($response['success'] ? "✓ Yes" : "✗ No") . "\n";
echo "   Message: " . $response['message'] . "\n";

if (isset($response['error'])) {
    echo "   Error: " . $response['error'] . "\n";
}

if (isset($response['debug'])) {
    echo "   Debug: " . $response['debug'] . "\n";
}

echo "\n=== End of Test ===\n";
