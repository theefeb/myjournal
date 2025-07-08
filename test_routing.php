<?php
// Test script to check routing
session_start();

echo "<h2>Routing Test</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>User not logged in</p>";
    echo "<p><a href='index.php?page=login'>Go to Login</a></p>";
    exit;
}

echo "<p style='color: green;'>User logged in: " . $_SESSION['user_id'] . "</p>";

// Test CSRF token
echo "<h3>CSRF Token Test</h3>";
require_once __DIR__ . '/includes/security.php';
$token = generate_csrf_token();
echo "<p>Generated token: " . substr($token, 0, 20) . "...</p>";
echo "<p>Token validation: " . (verify_csrf_token($token) ? 'VALID' : 'INVALID') . "</p>";

// Test database connection
echo "<h3>Database Connection Test</h3>";
require_once __DIR__ . '/config/database.php';
try {
    $pdo = Database::getInstance();
    echo "<p style='color: green;'>Database connection: SUCCESS</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection: FAILED - " . $e->getMessage() . "</p>";
    exit;
}

// Test JournalEntry model
echo "<h3>Journal Entry Model Test</h3>";
require_once __DIR__ . '/models/JournalEntry.php';
$journalEntry = new JournalEntry($pdo);

// Get some entries
$entries = $journalEntry->getAll($_SESSION['user_id'], ['limit' => 3]);
echo "<p>Found " . count($entries) . " entries</p>";

if (!empty($entries)) {
    $test_entry = $entries[0];
    echo "<p>Testing with entry ID: " . $test_entry['id'] . " - " . $test_entry['title'] . "</p>";
    
    // Test getById
    $entry = $journalEntry->getById($test_entry['id'], $_SESSION['user_id']);
    if ($entry) {
        echo "<p style='color: green;'>getById: SUCCESS</p>";
    } else {
        echo "<p style='color: red;'>getById: FAILED</p>";
    }
}

// Test routing URLs
echo "<h3>Routing URL Test</h3>";
echo "<p>Delete confirmation URL: <a href='index.php?page=journal&action=delete&id=1'>Test Delete</a></p>";
echo "<p>Edit URL: <a href='index.php?page=journal&action=edit&id=1'>Test Edit</a></p>";
echo "<p>View URL: <a href='index.php?page=journal&action=view&id=1'>Test View</a></p>";

echo "<h3>Form Test</h3>";
echo "<form method='POST' action='index.php?page=journal&action=delete&id=1'>";
echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($token, ENT_QUOTES) . "'>";
echo "<button type='submit'>Test Delete Form</button>";
echo "</form>";

echo "<br><p><a href='index.php?page=dashboard'>Back to Dashboard</a></p>";
?> 