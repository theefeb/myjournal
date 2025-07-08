<?php include __DIR__ . '/../partials/header.php'; ?>
<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?page=login");
    exit;
}

// Get entry ID from URL
$entryId = $_GET['id'] ?? null;
if (!$entryId) {
    header("Location: ../../index.php?page=journal&action=list");
    exit;
}

$journalEntry = new JournalEntry($pdo);
$entry = $journalEntry->getById($entryId, $_SESSION['user_id']);

if (!$entry) {
    $_SESSION['error'] = "Entry not found or you don't have permission to edit it";
    header("Location: ../../index.php?page=journal&action=list");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid form submission";
        header("Location: ../../index.php?page=journal&action=edit&id=" . $entryId);
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $tags = isset($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];
    $mood = isset($_POST['mood']) ? (int)$_POST['mood'] : null;

    // Validate input
    if (empty($title)) {
        $_SESSION['error'] = "Title is required";
    } elseif (strlen($title) > 255) {
        $_SESSION['error'] = "Title must be 255 characters or less";
    } elseif (empty($content)) {
        $_SESSION['error'] = "Content is required";
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
            header("Location: ../../index.php?page=journal&action=view&id=" . $entryId);
            exit;
        } else {
            $_SESSION['error'] = "Failed to update entry";
        }
    }
}
?>

<div class="form-outer-container">
    <h2 style="margin-bottom: 1.5rem; text-align:center;">Edit Journal Entry</h2>

    <?php include __DIR__ . '/../partials/messages.php'; ?>

    <form method="POST" class="journal-form" style="width:100%; max-width: 600px; margin: 0 auto;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES) ?>">

        <div class="card" style="padding:2rem; background:#fff;">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required maxlength="255"
                       value="<?= htmlspecialchars($entry['title'] ?? '', ENT_QUOTES) ?>">
            </div>

            <div class="form-group">
                <label for="content">Your Entry</label>
                <textarea id="content" name="content" rows="8" required style="resize:vertical;"><?= 
                    htmlspecialchars($entry['content'] ?? '', ENT_QUOTES)
                ?></textarea>
                <div class="character-count" id="content-counter" style="font-size:0.95em; color:#888; margin-top:0.3rem;"><?= strlen($entry['content'] ?? '') ?> characters</div>
            </div>

            <div class="form-group">
                <label for="tags">Tags (comma separated)</label>
                <input type="text" id="tags" name="tags"
                       value="<?= htmlspecialchars(implode(', ', $entry['tags'] ?? []), ENT_QUOTES) ?>">
                <div class="tag-hint" style="margin-top:0.5rem;">
                    <span style="color:#666; font-size:0.97em;">Popular tags:</span>
                    <?php 
                    $popularTags = $journalEntry->getPopularTags($_SESSION['user_id'], 5);
                    foreach ($popularTags as $tag): ?>
                        <span class="tag-suggestion" style="background:#e0e7ff; color:#5d78ff; border-radius:12px; padding:0.2em 0.8em; margin-right:0.3em; cursor:pointer; font-size:0.97em; display:inline-block; margin-bottom:0.2em;" onclick="addTag('<?= htmlspecialchars($tag, ENT_QUOTES) ?>')">
                            <?= htmlspecialchars($tag, ENT_QUOTES) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label style="display:block; margin-bottom:0.5rem;">Mood</label>
                <div class="mood-options" style="display:flex; gap:0.7em;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="mood" id="mood-<?= $i ?>"
                               value="<?= $i ?>" style="display:none;" <?= 
                               ($entry['mood'] ?? '') == $i ? 'checked' : '' ?>>
                        <label for="mood-<?= $i ?>" class="mood-option" style="font-size:1.5em; cursor:pointer; padding:0.2em 0.5em; border-radius:8px; transition:background 0.2s;">
                            <?= str_repeat('😊', $i) ?>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-actions" style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary">Update Entry</button>
                <div style="display:flex; gap:0.5rem;">
                    <a href="/journalms/index.php?page=journal&action=list" class="btn btn-link">Back to List</a>
                    <a href="/journalms/index.php?page=journal&action=view&id=<?= $entryId ?>" class="btn btn-link">View Entry</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Character counter for content
const contentInput = document.getElementById('content');
const counter = document.getElementById('content-counter');
if(contentInput && counter) {
    function updateCounter() {
        counter.textContent = contentInput.value.length + ' characters';
    }
    contentInput.addEventListener('input', updateCounter);
    updateCounter();
}
// Add tag suggestion to tags input
function addTag(tag) {
    const tagsInput = document.getElementById('tags');
    const currentTags = tagsInput.value.split(',').map(t => t.trim());
    if (!currentTags.includes(tag)) {
        tagsInput.value = currentTags.filter(t => t).concat(tag).join(', ');
    }
}
// Mood radio highlight
const moodLabels = document.querySelectorAll('.mood-option');
moodLabels.forEach(label => {
    label.addEventListener('click', function() {
        moodLabels.forEach(l => l.style.background = '');
        this.style.background = '#e0e7ff';
    });
});
</script>

