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

// Get mood statistics
$monthlyStats = $moodModel->getMoodStats($_SESSION['user_id'], 'month');
$weeklyStats = $moodModel->getMoodStats($_SESSION['user_id'], 'week');
$yearlyStats = $moodModel->getMoodStats($_SESSION['user_id'], 'year');

// Get mood trends
$monthlyTrends = $moodModel->getMoodTrends($_SESSION['user_id'], 'month');
$weeklyTrends = $moodModel->getMoodTrends($_SESSION['user_id'], 'week');
?>

<div class="form-outer-container">
    <h2 style="margin-bottom: 1.5rem; text-align:center;">Mood Statistics</h2>

    <?php include __DIR__ . '/../partials/messages.php'; ?>

    <!-- Overall Stats Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
        <div class="card" style="padding:1.5rem; background:#fff; text-align:center;">
            <h3 style="margin-bottom:1rem; color:#374151;">This Week</h3>
            <div style="font-size:2.5em; color:#5d78ff; margin-bottom:0.5rem;">
                <?= number_format($weeklyStats['avg_mood'] ?? 0, 1) ?>
            </div>
            <p style="color:#666; margin:0;">Average Mood</p>
            <div style="margin-top:1rem; font-size:0.9em; color:#888;">
                <?= $weeklyStats['entry_count'] ?? 0 ?> entries logged
            </div>
        </div>

        <div class="card" style="padding:1.5rem; background:#fff; text-align:center;">
            <h3 style="margin-bottom:1rem; color:#374151;">This Month</h3>
            <div style="font-size:2.5em; color:#5d78ff; margin-bottom:0.5rem;">
                <?= number_format($monthlyStats['avg_mood'] ?? 0, 1) ?>
            </div>
            <p style="color:#666; margin:0;">Average Mood</p>
            <div style="margin-top:1rem; font-size:0.9em; color:#888;">
                <?= $monthlyStats['entry_count'] ?? 0 ?> entries logged
            </div>
        </div>

        <div class="card" style="padding:1.5rem; background:#fff; text-align:center;">
            <h3 style="margin-bottom:1rem; color:#374151;">This Year</h3>
            <div style="font-size:2.5em; color:#5d78ff; margin-bottom:0.5rem;">
                <?= number_format($yearlyStats['avg_mood'] ?? 0, 1) ?>
            </div>
            <p style="color:#666; margin:0;">Average Mood</p>
            <div style="margin-top:1rem; font-size:0.9em; color:#888;">
                <?= $yearlyStats['entry_count'] ?? 0 ?> entries logged
            </div>
        </div>
    </div>

    <!-- Mood Distribution -->
    <div class="card" style="padding:2rem; background:#fff; margin-bottom:2rem;">
        <h3 style="margin-bottom:1.5rem; color:#374151;">Mood Distribution (This Month)</h3>
        <?php if (!empty($monthlyTrends)): ?>
            <div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:1rem;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?php 
                    $count = 0;
                    foreach ($monthlyTrends as $trend) {
                        if ($trend['mood'] == $i) {
                            $count = $trend['count'];
                            break;
                        }
                    }
                    $percentage = $monthlyStats['entry_count'] > 0 ? ($count / $monthlyStats['entry_count']) * 100 : 0;
                    ?>
                    <div style="text-align:center;">
                        <div style="font-size:2em; margin-bottom:0.5rem;"><?= str_repeat('😊', $i) ?></div>
                        <div style="font-size:1.2em; font-weight:500; color:#374151;"><?= $count ?></div>
                        <div style="font-size:0.9em; color:#666;"><?= number_format($percentage, 1) ?>%</div>
                    </div>
                <?php endfor; ?>
            </div>
        <?php else: ?>
            <p style="color:#666; text-align:center;">No mood data available for this month</p>
        <?php endif; ?>
    </div>

    <!-- Weekly Trend Chart -->
    <div class="card" style="padding:2rem; background:#fff; margin-bottom:2rem;">
        <h3 style="margin-bottom:1.5rem; color:#374151;">Weekly Mood Trend</h3>
        <?php if (!empty($weeklyTrends)): ?>
            <div style="display:flex; align-items:end; justify-content:space-between; height:200px; gap:0.5rem;">
                <?php foreach ($weeklyTrends as $trend): ?>
                    <div style="display:flex; flex-direction:column; align-items:center; flex:1;">
                        <div style="background:#e0e7ff; width:100%; border-radius:4px 4px 0 0; margin-bottom:0.5rem;"
                             style="height:<?= max(20, ($trend['avg_mood'] / 5) * 150) ?>px;"></div>
                        <div style="font-size:0.8em; color:#666;"><?= date('D', strtotime($trend['date'])) ?></div>
                        <div style="font-size:0.7em; color:#888;"><?= number_format($trend['avg_mood'], 1) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#666; text-align:center;">No weekly trend data available</p>
        <?php endif; ?>
    </div>

    <!-- Navigation -->
    <div style="text-align:center; margin-top:2rem;">
        <a href="index.php?page=mood&action=track" class="btn btn-primary">Track Today's Mood</a>
        <a href="index.php?page=mood&action=history" class="btn btn-secondary">View History</a>
        <a href="index.php?page=dashboard" class="btn btn-link">Back to Dashboard</a>
    </div>
</div>

