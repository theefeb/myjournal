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
    $_SESSION['error'] = "Entry not found";
    header("Location: ../../index.php?page=journal&action=list");
    exit;
}

$journalEntry = new JournalEntry($pdo);
$entry = $journalEntry->getById($entryId, $_SESSION['user_id']);

if (!$entry) {
    $_SESSION['error'] = "Entry not found or you don't have permission to view it";
    header("Location: ../../index.php?page=journal&action=list");
    exit;
}
?>

<div class="form-outer-container">
    <div style="max-width: 800px; margin: 0 auto;">
        <!-- Header with actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="margin: 0;"><?= htmlspecialchars($entry['title'], ENT_QUOTES) ?></h2>
            <div style="display: flex; gap: 0.5rem;">
                <a href="/journalms/index.php?page=journal&action=edit&id=<?= $entryId ?>" class="btn btn-secondary">Edit</a>
                <a href="/journalms/index.php?page=journal&action=list" class="btn btn-link">Back to List</a>
            </div>
        </div>

        <?php include __DIR__ . '/../partials/messages.php'; ?>

        <!-- Entry content card -->
        <div class="card" style="padding: 2rem; background: #fff; margin-bottom: 1.5rem;">
            <!-- Entry metadata -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="color: #6b7280; font-size: 0.9rem;">
                        Created: <?= date('F j, Y \a\t g:i A', strtotime($entry['created_at'])) ?>
                    </span>
                    <?php if ($entry['updated_at'] && $entry['updated_at'] !== $entry['created_at']): ?>
                        <span style="color: #6b7280; font-size: 0.9rem;">
                            Updated: <?= date('F j, Y \a\t g:i A', strtotime($entry['updated_at'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($entry['mood']): ?>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="color: #6b7280; font-size: 0.9rem;">Mood:</span>
                        <span style="font-size: 1.2em;"><?= str_repeat('😊', $entry['mood']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Entry content -->
            <div style="line-height: 1.7; color: #374151;">
                <?= nl2br(htmlspecialchars($entry['content'], ENT_QUOTES)) ?>
            </div>

            <!-- Tags -->
            <?php if (!empty($entry['tags'])): ?>
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #374151;">Tags:</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        <?php foreach ($entry['tags'] as $tag): ?>
                            <span style="background: #e0e7ff; color: #5d78ff; padding: 0.3em 0.8em; border-radius: 12px; font-size: 0.9rem;">
                                <?= htmlspecialchars($tag, ENT_QUOTES) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Prompt info if applicable -->
            <?php if ($entry['prompt_id'] && $entry['prompt_content']): ?>
                <div style="margin-top: 1.5rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 4px solid #5d78ff;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #374151;">Writing Prompt:</h4>
                    <p style="margin: 0; color: #6b7280; font-style: italic;">
                        "<?= htmlspecialchars($entry['prompt_content'], ENT_QUOTES) ?>"
                    </p>
                    <?php if (!empty($entry['prompt_category'])): ?>
                        <span style="display: inline-block; margin-top: 0.5rem; background: #5d78ff; color: white; padding: 0.2em 0.6em; border-radius: 8px; font-size: 0.8rem;">
                            <?= htmlspecialchars($entry['prompt_category'], ENT_QUOTES) ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action buttons -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; gap: 0.5rem;">
                <a href="/journalms/index.php?page=journal&action=edit&id=<?= $entryId ?>" class="btn btn-primary">Edit Entry</a>
                <a href="/journalms/index.php?page=journal&action=create" class="btn btn-secondary">New Entry</a>
            </div>
            <button onclick="deleteEntry(<?= $entryId ?>)" class="btn btn-danger">
                Delete Entry
            </button>
        </div>
    </div>
</div>

<script>
function deleteEntry(entryId) {
    if (confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
        // Create a form and submit it
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/journalms/index.php?page=journal&action=delete&id=' + entryId;
        
        // Add CSRF token
        var csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES) ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

