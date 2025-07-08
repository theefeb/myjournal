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

$moodModel = new MoodTracker($pdo);

// Handle filters
$period = $_GET['period'] ?? 'month';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Get mood history
$moodHistory = $moodModel->getMoodHistory($_SESSION['user_id'], $period, $limit, $offset);
$totalMoods = $moodModel->getMoodCount($_SESSION['user_id'], $period);
$totalPages = ceil($totalMoods / $limit);
?>

<div class="form-outer-container">
    <h2 style="margin-bottom: 1.5rem; text-align:center;">Mood History</h2>

    <?php include __DIR__ . '/../partials/messages.php'; ?>

    <!-- Filter Controls -->
    <div class="card" style="padding:1.5rem; background:#fff; max-width:800px; margin:0 auto 2rem;">
        <div style="display:flex; gap:1rem; align-items:center; justify-content:center; flex-wrap:wrap;">
            <a href="?page=mood&action=history&period=week" 
               class="btn <?= $period === 'week' ? 'btn-primary' : 'btn-secondary' ?>">Week</a>
            <a href="?page=mood&action=history&period=month" 
               class="btn <?= $period === 'month' ? 'btn-primary' : 'btn-secondary' ?>">Month</a>
            <a href="?page=mood&action=history&period=year" 
               class="btn <?= $period === 'year' ? 'btn-primary' : 'btn-secondary' ?>">Year</a>
            <a href="?page=mood&action=history&period=all" 
               class="btn <?= $period === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All Time</a>
        </div>
    </div>

    <!-- Mood History List -->
    <div class="card" style="padding:2rem; background:#fff; max-width:800px; margin:0 auto;">
        <?php if (!empty($moodHistory)): ?>
            <div class="mood-history-list">
                <?php foreach ($moodHistory as $mood): ?>
                    <div class="mood-entry" style="display:flex; align-items:center; padding:1rem; border-bottom:1px solid #e5e7eb; gap:1rem;">
                        <div style="text-align:center; min-width:80px;">
                            <div style="font-size:2em;"><?= str_repeat('😊', $mood['mood']) ?></div>
                            <div style="font-size:0.8em; color:#666;"><?= $mood['mood'] ?>/5</div>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:500; color:#374151;">
                                <?= date('F j, Y', strtotime($mood['created_at'])) ?>
                            </div>
                            <?php if (!empty($mood['notes'])): ?>
                                <div style="color:#666; margin-top:0.3rem; font-style:italic;">
                                    "<?= htmlspecialchars($mood['notes'], ENT_QUOTES) ?>"
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="color:#888; font-size:0.9em;">
                            <?= date('g:i A', strtotime($mood['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="display:flex; justify-content:center; gap:0.5rem; margin-top:2rem;">
                    <?php if ($page > 1): ?>
                        <a href="?page=mood&action=history&period=<?= $period ?>&page=<?= $page - 1 ?>" class="btn btn-small">
                            &laquo; Previous
                        </a>
                    <?php endif; ?>

                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    if ($start > 1) echo '<span style="padding:0.5rem;">...</span>';
                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=mood&action=history&period=<?= $period ?>&page=<?= $i ?>" 
                           class="btn btn-small <?= $i == $page ? 'btn-primary' : 'btn-secondary' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; 
                    if ($end < $totalPages) echo '<span style="padding:0.5rem;">...</span>';
                    ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=mood&action=history&period=<?= $period ?>&page=<?= $page + 1 ?>" class="btn btn-small">
                            Next &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align:center; padding:3rem;">
                <div style="font-size:3em; color:#ccc; margin-bottom:1rem;">😐</div>
                <h3 style="color:#666; margin-bottom:0.5rem;">No Mood Data</h3>
                <p style="color:#888;">You haven't logged any moods for this period yet.</p>
                <a href="index.php?page=mood&action=track" class="btn btn-primary" style="margin-top:1rem;">
                    Start Tracking Your Mood
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Navigation -->
    <div style="text-align:center; margin-top:2rem;">
        <a href="index.php?page=mood&action=track" class="btn btn-primary">Track Today's Mood</a>
        <a href="index.php?page=mood&action=stats" class="btn btn-secondary">View Statistics</a>
        <a href="index.php?page=dashboard" class="btn btn-link">Back to Dashboard</a>
    </div>
</div>

 