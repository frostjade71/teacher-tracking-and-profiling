<?php
// public/test_chatbot_debug.php
// Quick debug script to test chatbot API directly

require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';

echo "=== Chatbot API Debug ===\n\n";

// Test 1: Check if API key is accessible
echo "1. Checking PERPLEXITY_API_KEY...\n";
$apiKey = getenv('PERPLEXITY_API_KEY');
if ($apiKey) {
    echo "   ✅ API Key found: " . substr($apiKey, 0, 10) . "...\n\n";
} else {
    echo "   ❌ API Key NOT found in environment!\n\n";
}

// Test 2: Test Perplexity API helper
echo "2. Testing Perplexity API Helper...\n";
try {
    require_once __DIR__ . '/../app/helpers/perplexity_helper.php';
    echo "   ✅ Helper loaded successfully\n\n";
    
    $perplexity = new PerplexityAPI();
    echo "   ✅ PerplexityAPI class instantiated\n\n";
    
    // Test 3: Simple API call
    echo "3. Testing API call...\n";
    $response = $perplexity->ask("Say hello");
    echo "   ✅ API call successful!\n";
    echo "   Response: " . substr($response, 0, 100) . "...\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

// Test 4: Database connection
echo "4. Testing database connection...\n";
try {
    $pdo = db();
    echo "   ✅ Database connected\n\n";
    
    // Test teacher query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Teachers in database: " . $result['count'] . "\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n\n";
}

echo "=== Debug Complete ===\n";
