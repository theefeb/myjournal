<?php
// Test script to debug CSRF functionality
session_start();

// Load required files
require_once __DIR__ . '/includes/security.php';

echo "CSRF Token Test<br><br>";

// Test 1: Generate a token
$token1 = generate_csrf_token();
echo "Generated token: " . substr($token1, 0, 20) . "...<br>";

// Test 2: Verify the same token
$valid1 = verify_csrf_token($token1);
echo "Token validation result: " . ($valid1 ? 'VALID' : 'INVALID') . "<br>";

// Test 3: Test with wrong token
$valid2 = verify_csrf_token('wrong_token');
echo "Wrong token validation result: " . ($valid2 ? 'VALID' : 'INVALID') . "<br>";

// Test 4: Test with empty token
$valid3 = verify_csrf_token('');
echo "Empty token validation result: " . ($valid3 ? 'VALID' : 'INVALID') . "<br>";

// Test 5: Generate another token
$token2 = generate_csrf_token();
echo "Second token: " . substr($token2, 0, 20) . "...<br>";

// Test 6: Verify both tokens are the same (should be from session)
echo "Tokens are the same: " . ($token1 === $token2 ? 'YES' : 'NO') . "<br>";

// Test 7: Check session
echo "Session CSRF token: " . (isset($_SESSION['csrf_token']) ? 'EXISTS' : 'MISSING') . "<br>";
if (isset($_SESSION['csrf_token'])) {
    echo "Session token: " . substr($_SESSION['csrf_token'], 0, 20) . "...<br>";
}

echo "<br>CSRF test completed.";
?> 