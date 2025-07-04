<?php
// Removed require_once('../config/database.php');
// Removed require_once('../models/JournalEntry.php');

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

// Handle filters with validation
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'tag' => trim($_GET['tag'] ?? ''),
    'start_date' => trim($_GET['start_date'] ?? ''),
    'end_date' => trim($_GET['end_date'] ?? ''),
    'page' => max(1, (int)($_GET['page'] ?? 1)), // Ensure page is at least 1
    'limit' => 10
];

// Get entries with filters
$entries = $journalEntry->getAll($_SESSION['user_id'], $filters);
$totalEntries = $journalEntry->getCount($_SESSION['user_id'], $filters);
$totalPages = max(1, ceil($totalEntries / $filters['limit'])); // Ensure at least 1 page

// Get all tags for filter dropdown
$tags = $journalEntry->getAllTags($_SESSION['user_id']);
?>

<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="form-outer-container">
    <h2 style="margin-bottom: 1.5rem; text-align:center;">Your Journal Entries</h2>

    <?php include __DIR__ . '/../partials/messages.php'; ?>

    <!-- Search and Filter Form -->
    <div class="card" style="padding:2rem; background:#fff; margin-bottom:2rem;">
        <form method="GET" class="filter-form" style="width:100%; max-width: 700px; margin: 0 auto;">
            <div class="form-row" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
                <div class="form-group" style="flex:2 1 180px; min-width:160px;">
                    <input type="text" name="search" placeholder="Search entries..."
                        value="<?= htmlspecialchars($filters['search'], ENT_QUOTES) ?>">
                </div>
                <div class="form-group" style="flex:1 1 120px; min-width:120px;">
                    <select name="tag">
                        <option value="">All Tags</option>
                        <?php foreach ($tags as $tag): ?>
                            <option value="<?= htmlspecialchars($tag, ENT_QUOTES) ?>" <?= $filters['tag'] === $tag ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tag, ENT_QUOTES) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1 1 120px; min-width:120px;">
                    <input type="date" name="start_date"
                        value="<?= htmlspecialchars($filters['start_date'], ENT_QUOTES) ?>"
                        placeholder="From date">
                </div>
                <div class="form-group" style="flex:1 1 120px; min-width:120px;">
                    <input type="date" name="end_date"
                        value="<?= htmlspecialchars($filters['end_date'], ENT_QUOTES) ?>"
                        placeholder="To date">
                </div>
                <div class="form-group" style="flex:0 0 auto; display:flex; gap:0.5em;">
                    <button type="submit" class="btn">Filter</button>
                    <a href="list.php" class="btn btn-link">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Entries List -->
    <div class="entries-list">
        <?php if (!empty($entries)): ?>
            <?php foreach ($entries as $entry): ?>
                <div class="entry-card">
                    <h2>
                        <a href="view.php?id=<?= (int)$entry['id'] ?>">
                            <?= htmlspecialchars($entry['title'], ENT_QUOTES) ?>
                        </a>
                    </h2>
                    <div class="entry-meta">
                        <span class="date"><?= format_date($entry['created_at']) ?></span>
                        <?php if (!empty($entry['mood'])): ?>
                            <span class="mood"><?= str_repeat('😊', (int)$entry['mood']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($entry['tags'])): ?>
                        <div class="entry-tags">
                            <?php foreach ($entry['tags'] as $tag): ?>
                                <a href="list.php?tag=<?= urlencode($tag) ?>" class="tag">
                                    <?= htmlspecialchars($tag, ENT_QUOTES) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="entry-content">
                        <?= htmlspecialchars(truncate(strip_tags($entry['content']), 200), ENT_QUOTES) ?>
                    </div>

                    <div class="entry-actions">
                        <a href="edit.php?id=<?= (int)$entry['id'] ?>" class="btn btn-small">Edit</a>
                        <form method="POST" action="delete.php" class="inline-form">
                            <input type="hidden" name="id" value="<?= (int)$entry['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES) ?>">
                            <button type="submit" class="btn btn-small btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this entry?')">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card" style="padding:2rem; text-align:center; background:#fff; margin-top:2rem;">
                <p class="no-entries" style="font-size:1.15em; color:#666;">No entries found.<br><a href="create.php" class="btn btn-link" style="margin-top:1em; display:inline-block;">Create your first entry!</a></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($filters['page'] > 1): ?>
                <a href="?page=<?= $filters['page'] - 1 ?>&<?= http_build_query(array_slice($filters, 0, 4)) ?>">
                    &laquo; Previous
                </a>
            <?php endif; ?>

            <?php 
            // Show limited pagination links
            $start = max(1, $filters['page'] - 2);
            $end = min($totalPages, $filters['page'] + 2);
            if ($start > 1) echo '<span>...</span>';
            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?= $i ?>&<?= http_build_query(array_slice($filters, 0, 4)) ?>"
                   class="<?= $i == $filters['page'] ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; 
            if ($end < $totalPages) echo '<span>...</span>';
            ?>
            <?php if ($filters['page'] < $totalPages): ?>
                <a href="?page=<?= $filters['page'] + 1 ?>&<?= http_build_query(array_slice($filters, 0, 4)) ?>">
                    Next &raquo;
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>