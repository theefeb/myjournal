<?php
require_once '../config/database.php';
require_once '../models/JournalEntry.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$journalEntry = new JournalEntry($pdo);
$entryId = $_GET['id'] ?? 0;

// Get the existing entry
$entry = $journalEntry->getById($entryId, $_SESSION['user_id']);

if (!$entry) {
    $_SESSION['error'] = "Entry not found";
    header("Location: list.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid form submission";
        header("Location: edit.php?id=" . $entryId);
        exit;
    }

    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $tags = isset($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];
    $mood = isset($_POST['mood']) ? (int)$_POST['mood'] : null;

    // Validate input
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = "Title and content are required";
    } elseif (strlen($title) > 255) {
        $_SESSION['error'] = "Title must be 255 characters or less";
    } else {
        $success = $journalEntry->update(
            $entryId,
            $_SESSION['user_id'],
            $title,
            $content,
            $tags,
            $mood
        );

        if ($success) {
            $_SESSION['success'] = "Entry updated successfully!";
            header("Location: view.php?id=" . $entryId);
            exit;
        } else {
            $_SESSION['error'] = "Failed to update entry";
        }
    }
}

// Include header
include __DIR__ . '/../views/partials/header.php';
?>

<div class="container">
    <h1>Edit Journal Entry</h1>

    <?php include __DIR__ . '/../views/partials/messages.php'; ?>

    <form method="POST" class="journal-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES) ?>">

        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required
                   value="<?= htmlspecialchars($entry['title'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-group">
            <label for="content">Your Entry</label>
            <textarea id="content" name="content" rows="10" required><?= 
                htmlspecialchars($entry['content'] ?? '', ENT_QUOTES)
            ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="tags">Tags (comma separated)</label>
                <input type="text" id="tags" name="tags"
                       value="<?= htmlspecialchars(implode(', ', $entry['tags'] ?? []), ENT_QUOTES) ?>">
            </div>

            <div class="form-group">
                <label>Mood</label>
                <div class="mood-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="mood" id="mood-<?= $i ?>"
                               value="<?= $i ?>" <?= 
                               ($entry['mood'] ?? '') == $i ? 'checked' : '' ?>>
                        <label for="mood-<?= $i ?>" class="mood-option">
                            <?= str_repeat('😊', $i) ?>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Entry</button>
            <a href="list.php" class="btn btn-link">Back to List</a>
            <a href="view.php?id=<?= $entryId ?>" class="btn btn-link">View Entry</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>