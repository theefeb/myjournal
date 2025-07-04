<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    redirect('index.php?page=login');
}

// Set page metadata
$page_title = "Dashboard";
$page_js = ['dashboard.js', 'chart.min.js']; // Added chart library

// Include header
require_once __DIR__ . '/../views/partials/header.php';

// Initialize models
$userModel = new User($pdo);
$promptModel = new Prompt($pdo);
$journalModel = new JournalEntry($pdo);
$moodModel = new MoodTracker($pdo);

// Get user data
$user_id = $_SESSION['user_id'];
$user = $userModel->getById($user_id);

// Get today's prompt (fixed method name)
$prompt = $promptModel->getTodaysPrompt($user_id);

// Get recent entries
$recent_entries = $journalModel->getAll($user_id, ['limit' => 5]);

// Get mood data
$mood_data = $moodModel->getMoodData($user_id, 'week');
$mood_stats = $moodModel->getMoodStats($user_id);
?>

<style>
/* Accurate blending and layout for dashboard */
.main-header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 1000;
}
.dashboard-wrapper {
  min-height: 100vh;
  background: linear-gradient(135deg,#a1c4fd 0%,#c2e9fb 50%,#d4a1fd 100%);
}
.dashboard-container {
  display: flex;
  align-items: stretch;
  min-height: 100vh;
  margin-top: 80px; /* header height */
}
.sidebar {
  position: fixed;
  top: 80px; /* header height */
  left: 0;
  height: calc(100vh - 80px);
  width: 250px;
  background: linear-gradient(135deg,#5d78ff 0%,#6a82fb 100%);
  color: #ecf0f1;
  border-top-right-radius: 24px;
  border-bottom-right-radius: 24px;
  box-shadow: 2px 0 16px rgba(44,62,80,0.08);
  display: flex;
  flex-direction: column;
  z-index: 900;
  padding-bottom: 2rem;
  overflow-y: auto;
  overscroll-behavior: contain;
}
.main-content {
  flex: 1;
  margin-left: 250px;
  padding: 2.5rem 2rem 2.5rem 2.5rem;
  min-width: 0;
}
@media (max-width: 900px) {
  .sidebar {
    width: 70px;
    min-width: 70px;
    max-width: 70px;
  }
  .main-content {
    margin-left: 70px;
    padding-left: 1rem;
  }
}
.user-profile {
  text-align: center;
  padding: 1.5rem 1rem 1.5rem 1rem;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  background: rgba(255,255,255,0.10);
  border-radius: 16px;
}
.user-profile .avatar {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: 0.7rem;
  border: 3px solid #fff;
  box-shadow: 0 2px 8px rgba(93,120,255,0.10);
}
.user-profile h3 {
  color: #fff;
  font-size: 1.2em;
  margin: 0.2em 0 0.1em 0;
}
.user-profile .user-email {
  color: #e0e7ff;
  font-size: 0.97em;
  margin-bottom: 0.2em;
}
.main-footer {
  position: relative;
  z-index: 800;
  margin-top: 0;
}
</style>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar" id="sidebar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="user-profile">
                <img src="<?= htmlspecialchars($user['avatar'] ?? 'assets/images/default-avatar.png', ENT_QUOTES) ?>" 
                     alt="Profile" class="avatar">
                <h3><?= htmlspecialchars($user['username'], ENT_QUOTES) ?></h3>
                <p class="user-email"><?= htmlspecialchars($user['email'], ENT_QUOTES) ?></p>
            </div>
            <nav class="main-nav" style="flex:1;">
                <ul>
                    <li class="active"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> <span class="nav-text">Dashboard</span></a></li>
                    <li><a href="index.php?page=journal&action=list"><i class="fas fa-book"></i> <span class="nav-text">Journal Entries</span></a></li>
                    <li><a href="index.php?page=journal&action=create"><i class="fas fa-plus"></i> <span class="nav-text">New Entry</span></a></li>
                    <li><a href="#mood-tracker"><i class="fas fa-chart-line"></i> <span class="nav-text">Mood Tracker</span></a></li>
                    <li><a href="index.php?page=profile"><i class="fas fa-user"></i> <span class="nav-text">Profile</span></a></li>
                    <li><a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a></li>
                </ul>
            </nav>
        </div>
        <!-- Main Content Area -->
        <div class="main-content">
            <!-- System Messages -->
            <div id="messages-container">
                <?php include __DIR__ . '/../views/partials/messages.php'; ?>
            </div>

            <!-- Dashboard Stats Row -->
            <div style="display:flex; gap:2rem; flex-wrap:wrap; margin-bottom:2.5rem; justify-content:center;">
                <div class="card stat-card" style="flex:1 1 180px; min-width:180px; max-width:260px; background:linear-gradient(120deg,#e0e7ff 0%,#fff 100%); text-align:center;">
                    <div style="font-size:2.2em; color:#5d78ff; margin-bottom:0.3em;"><i class="fas fa-book"></i></div>
                    <h3 style="margin:0; font-size:2em; color:#4254c5;"><?= $mood_stats['entry_count'] ?? 0 ?></h3>
                    <p style="color:#666; margin:0.2em 0 0.5em;">Journal Entries</p>
                </div>
                <div class="card stat-card" style="flex:1 1 180px; min-width:180px; max-width:260px; background:linear-gradient(120deg,#e0e7ff 0%,#fff 100%); text-align:center;">
                    <div style="font-size:2.2em; color:#5d78ff; margin-bottom:0.3em;"><i class="fas fa-fire"></i></div>
                    <h3 style="margin:0; font-size:2em; color:#4254c5;"><?= (int)($mood_stats['current_streak'] ?? 0) ?></h3>
                    <p style="color:#666; margin:0.2em 0 0.5em;">Day Streak</p>
                </div>
                <div class="card stat-card" style="flex:1 1 180px; min-width:180px; max-width:260px; background:linear-gradient(120deg,#e0e7ff 0%,#fff 100%); text-align:center;">
                    <div style="font-size:2.2em; color:#5d78ff; margin-bottom:0.3em;"><i class="fas fa-smile"></i></div>
                    <h3 style="margin:0; font-size:2em; color:#4254c5;"><?= number_format($mood_stats['avg_mood'] ?? 0, 1) ?>/5</h3>
                    <p style="color:#666; margin:0.2em 0 0.5em;">Avg Mood</p>
                </div>
            </div>

            <!-- Daily Prompt Section -->
            <section class="daily-prompt card" style="margin-bottom:2.5rem;">
                <div class="section-header" style="display:flex; align-items:center; justify-content:space-between;">
                    <h2 style="margin:0; display:flex; align-items:center;"><i class="fas fa-lightbulb" style="color:#5d78ff; margin-right:0.5em;"></i>Today's Prompt</h2>
                    <button id="new-prompt-btn" class="btn-icon" title="Get new prompt" style="background:#e0e7ff; color:#5d78ff; border-radius:8px;">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="prompt-card" style="margin:1.2em 0;">
                    <?php if ($prompt): ?>
                        <p style="font-size:1.15em; color:#333; margin-bottom:0.5em;"><i class="fas fa-quote-left" style="color:#5d78ff; margin-right:0.4em;"></i><?= htmlspecialchars($prompt['content'], ENT_QUOTES) ?></p>
                        <?php if (!empty($prompt['category'])): ?>
                            <span class="prompt-category" style="background:#e0e7ff; color:#5d78ff; border-radius:8px; padding:0.2em 0.8em; font-size:0.97em;">Category: <?= htmlspecialchars($prompt['category'], ENT_QUOTES) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No prompt available today</p>
                    <?php endif; ?>
                </div>
                <div class="prompt-actions" style="text-align:right;">
                    <a href="index.php?page=journal&action=create" class="btn">
                        <i class="fas fa-plus"></i> Start Writing
                    </a>
                </div>
            </section>

            <!-- Recent Entries Section -->
            <section class="recent-entries card" style="margin-bottom:2.5rem;">
                <h2 style="margin-bottom:1.2em;"><i class="fas fa-book-open" style="color:#5d78ff; margin-right:0.5em;"></i>Recent Entries</h2>
                <div class="entries-list" id="entries-container" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:1.2em;">
                    <?php if (!empty($recent_entries)): ?>
                        <?php foreach ($recent_entries as $entry): ?>
                            <div class="entry-card" data-entry-id="<?= (int)$entry['id'] ?>" style="box-shadow:0 2px 12px rgba(0,0,0,0.07); border-radius:8px; padding:1.2em; background:#fff;">
                                <h3 style="margin-top:0; font-size:1.15em; color:#4254c5;">
                                    <a href="index.php?page=journal&action=view&id=<?= (int)$entry['id'] ?>">
                                        <?= htmlspecialchars($entry['title'], ENT_QUOTES) ?>
                                    </a>
                                </h3>
                                <p class="entry-date" style="color:#888; font-size:0.97em; margin-bottom:0.3em;"><?= format_date($entry['created_at']) ?></p>
                                <?php if (!empty($entry['prompt_content'])): ?>
                                    <p class="prompt-ref" style="color:#5d78ff; font-size:0.97em;">Prompt: <?= htmlspecialchars($entry['prompt_content'], ENT_QUOTES) ?></p>
                                <?php endif; ?>
                                <div class="entry-preview" style="color:#333; font-size:0.98em; margin-bottom:0.7em;">
                                    <?= htmlspecialchars(truncate(strip_tags($entry['content']), 150), ENT_QUOTES) ?>
                                </div>
                                <div class="entry-actions" style="display:flex; gap:0.5em;">
                                    <a href="index.php?page=journal&action=edit&id=<?= (int)$entry['id'] ?>" 
                                       class="btn btn-small">
                                       Edit
                                    </a>
                                    <form method="POST" action="index.php?page=journal&action=delete" class="inline-form">
                                        <input type="hidden" name="id" value="<?= (int)$entry['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES) ?>">
                                        <button type="submit" class="btn btn-small btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this entry?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#888;">No entries yet. <a href="index.php?page=journal&action=create" class="btn btn-link">Create your first entry!</a></p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Mood Tracker Section -->
            <section class="mood-tracker card" id="mood-tracker" style="margin-bottom:2.5rem;">
                <h2 style="margin-bottom:1.2em;"><i class="fas fa-chart-line" style="color:#5d78ff; margin-right:0.5em;"></i>Mood Tracker</h2>
                <div class="mood-period-selector" style="margin-bottom:1em;">
                    <select id="period-select" class="form-control" style="max-width:200px;">
                        <option value="week">Last 7 Days</option>
                        <option value="month">Last 30 Days</option>
                        <option value="year">Last Year</option>
                    </select>
                </div>
                <div class="mood-graph-container" style="margin-bottom:1.5em;">
                    <canvas id="moodChart"></canvas>
                </div>
                <div class="mood-stats" style="display:flex; gap:2em; justify-content:center; margin-bottom:1.5em;">
                    <div class="stat-card" style="background:#e0e7ff; border-radius:10px; padding:1em 2em; text-align:center;">
                        <h3 style="margin:0; color:#4254c5; font-size:1.1em;">Current Streak</h3>
                        <p id="current-streak" style="font-size:1.5em; color:#5d78ff; margin:0.2em 0;"><?= (int)($mood_stats['current_streak'] ?? 0) ?> days</p>
                    </div>
                    <div class="stat-card" style="background:#e0e7ff; border-radius:10px; padding:1em 2em; text-align:center;">
                        <h3 style="margin:0; color:#4254c5; font-size:1.1em;">Average Mood</h3>
                        <p id="avg-mood" style="font-size:1.5em; color:#5d78ff; margin:0.2em 0;"><?= number_format($mood_stats['avg_mood'] ?? 0, 1) ?>/5</p>
                    </div>
                </div>
                <div class="mood-input" style="background:#f8f9fa; border-radius:10px; padding:1.5em;">
                    <h3 style="margin-top:0; color:#4254c5;">How are you feeling today?</h3>
                    <form id="mood-form" method="POST" action="index.php?page=mood&action=save">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES) ?>">
                        <div class="mood-options" style="display:flex; gap:0.7em; margin-bottom:1em;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="mood" id="mood-<?= $i ?>" value="<?= $i ?>"
                                       style="display:none;" <?= (isset($mood_stats['today_mood']) && $mood_stats['today_mood'] == $i) ? 'checked' : '' ?>>
                                <label for="mood-<?= $i ?>" class="mood-option" data-mood="<?= $i ?>" style="font-size:1.5em; cursor:pointer; padding:0.2em 0.5em; border-radius:8px; transition:background 0.2s; background:#fff;">
                                    <?= str_repeat('😊', $i) ?>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div class="form-group">
                            <label for="mood-notes">Notes (optional)</label>
                            <textarea id="mood-notes" name="notes" rows="2" class="form-control" style="width:100%;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Mood</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
// Initialize mood chart with PHP data
document.addEventListener('DOMContentLoaded', function() {
    const moodData = <?= json_encode($mood_data) ?>;
    const ctx = document.getElementById('moodChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: moodData.labels,
            datasets: [{
                label: 'Mood Level',
                data: moodData.values,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: false,
                    min: 1,
                    max: 5,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});

document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.body.classList.toggle('sidebar-collapsed');
});
</script>

<?php
// Include footer
require_once __DIR__ . '/../views/partials/footer.php';
?>