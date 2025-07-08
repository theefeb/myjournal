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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid form submission";
        header("Location: index.php?page=mood&action=track");
        exit;
    }

    $mood = isset($_POST['mood']) ? (int)$_POST['mood'] : null;
    $notes = trim($_POST['notes'] ?? '');
    $entry_id = isset($_POST['entry_id']) ? (int)$_POST['entry_id'] : null;

    if ($mood && $mood >= 1 && $mood <= 5) {
        $success = $moodModel->logMood($_SESSION['user_id'], $mood, $notes, $entry_id);
        if ($success) {
            $_SESSION['success'] = "Mood logged successfully!";
        } else {
            $_SESSION['error'] = "Failed to log mood";
        }
    } else {
        $_SESSION['error'] = "Please select a valid mood";
    }
    
    header("Location: index.php?page=mood&action=track");
    exit;
}

// Get today's mood if already logged
$todayMood = $moodModel->getTodayMood($_SESSION['user_id']);
?>

<div class="form-outer-container">
    <h2 style="margin-bottom: 1.5rem; text-align:center;">Track Your Mood</h2>

    <?php include __DIR__ . '/../partials/messages.php'; ?>

    <div class="card" style="padding:2rem; background:#fff; max-width:600px; margin:0 auto;">
        <?php if ($todayMood): ?>
            <div style="text-align:center; margin-bottom:2rem;">
                <h3 style="color:#5d78ff;">Today's Mood</h3>
                <div style="font-size:3em; margin:1rem 0;"><?= str_repeat('😊', $todayMood['mood']) ?></div>
                <p style="color:#666;">You logged a mood of <?= $todayMood['mood'] ?>/5 today</p>
                <?php if (!empty($todayMood['notes'])): ?>
                    <p style="color:#666; font-style:italic;">"<?= htmlspecialchars($todayMood['notes'], ENT_QUOTES) ?>"</p>
                <?php endif; ?>
                <p style="color:#888; font-size:0.9em;">You can update your mood throughout the day</p>
            </div>
        <?php endif; ?>

        <form method="POST" class="mood-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES) ?>">

            <div class="form-group" style="margin-bottom:2rem;">
                <label style="display:block; margin-bottom:1rem; font-size:1.1em; color:#374151;">How are you feeling today?</label>
                <div class="mood-options" style="display:flex; justify-content:space-between; gap:1rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div style="text-align:center; flex:1;">
                            <input type="radio" name="mood" id="mood-<?= $i ?>" value="<?= $i ?>" 
                                   style="display:none;" <?= ($todayMood && $todayMood['mood'] == $i) ? 'checked' : '' ?>>
                            <label for="mood-<?= $i ?>" class="mood-option" 
                                   style="display:block; font-size:2.5em; cursor:pointer; padding:1rem; border-radius:12px; transition:all 0.2s; border:2px solid #e5e7eb;">
                                <?= str_repeat('😊', $i) ?>
                            </label>
                            <div style="margin-top:0.5rem; font-size:0.9em; color:#666;">
                                <?= $i === 1 ? 'Very Bad' : ($i === 2 ? 'Bad' : ($i === 3 ? 'Okay' : ($i === 4 ? 'Good' : 'Great'))) ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:2rem;">
                <label for="notes" style="display:block; margin-bottom:0.5rem; color:#374151;">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="4" placeholder="How was your day? What made you feel this way?"
                          style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:8px; resize:vertical;"><?= htmlspecialchars($todayMood['notes'] ?? '', ENT_QUOTES) ?></textarea>
            </div>

            <div class="form-actions" style="display:flex; gap:1rem; justify-content:center;">
                <button type="submit" class="btn btn-primary" style="min-width:120px;">
                    <?= $todayMood ? 'Update Mood' : 'Log Mood' ?>
                </button>
                <a href="index.php?page=mood&action=history" class="btn btn-secondary">View History</a>
                <a href="index.php?page=dashboard" class="btn btn-link">Back to Dashboard</a>
            </div>
        </form>
    </div>

    <!-- Recent Mood Stats -->
    <div class="card" style="padding:2rem; background:#fff; max-width:600px; margin:2rem auto;">
        <h3 style="margin-bottom:1rem; color:#374151;">Your Mood This Week</h3>
        <?php
        $weeklyMood = $moodModel->getMoodData($_SESSION['user_id'], 'week');
        if (!empty($weeklyMood)): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <?php foreach ($weeklyMood as $mood): ?>
                    <div style="text-align:center; flex:1; min-width:60px;">
                        <div style="font-size:1.5em;">
                            <?= isset($mood['mood']) && $mood['mood'] ? str_repeat('😊', (int)$mood['mood']) : '' ?>
                        </div>
                        <div style="font-size:0.8em; color:#666;">
                            <?= isset($mood['created_at']) && $mood['created_at'] ? date('D', strtotime($mood['created_at'])) : '' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#666; text-align:center;">No mood data for this week yet</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Mood radio highlight
const moodLabels = document.querySelectorAll('.mood-option');
moodLabels.forEach(label => {
    label.addEventListener('click', function() {
        moodLabels.forEach(l => {
            l.style.background = '';
            l.style.borderColor = '#e5e7eb';
        });
        this.style.background = '#e0e7ff';
        this.style.borderColor = '#5d78ff';
    });
});

// Initialize selected mood
document.querySelectorAll('input[name="mood"]:checked').forEach(input => {
    const label = document.querySelector(`label[for="${input.id}"]`);
    if (label) {
        label.style.background = '#e0e7ff';
        label.style.borderColor = '#5d78ff';
    }
});
</script>

