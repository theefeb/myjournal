<?php
// Test script to debug delete functionality
session_start();

// Load required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/JournalEntry.php';
require_once __DIR__ . '/includes/security.php';

// Initialize database connection
try {
    $pdo = Database::getInstance();
    echo "Database connection successful<br>";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

$user_id = $_SESSION['user_id'];
echo "User ID: $user_id<br>";

$journalEntry = new JournalEntry($pdo);

// Test 1: Check if we can get entries
$entries = $journalEntry->getAll($user_id, ['limit' => 5]);
echo "Found " . count($entries) . " entries<br>";

if (empty($entries)) {
    die("No entries found to test deletion");
}

// Test 2: Try to get a specific entry
$test_entry = $entries[0];
$entry_id = $test_entry['id'];
echo "Testing with entry ID: $entry_id<br>";

// Test 3: Check if entry exists
$entry = $journalEntry->getById($entry_id, $user_id);
if ($entry) {
    echo "Entry found: " . $entry['title'] . "<br>";
} else {
    die("Entry not found");
}

// Test 4: Try to delete the entry (without actually deleting)
echo "Testing delete method...<br>";
$success = $journalEntry->delete($entry_id, $user_id);
echo "Delete result: " . ($success ? 'SUCCESS' : 'FAILED') . "<br>";

// Test 5: Check if entry still exists
$entry_after = $journalEntry->getById($entry_id, $user_id);
if ($entry_after) {
    echo "Entry still exists after delete attempt<br>";
} else {
    echo "Entry was successfully deleted<br>";
}

echo "<br>Test completed. Check the error logs for more details.";
?> 