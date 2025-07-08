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

$journalEntry = new JournalEntry($pdo);

// Get current month/year or from URL parameters
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// Get entries for the month
$entries = $journalEntry->getByMonth($_SESSION['user_id'], $year, $month);

// Create calendar data
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$firstDayOfWeek = date('w', $firstDay);
$monthName = date('F Y', $firstDay);

// Create entries lookup by date
$entriesByDate = [];
foreach ($entries as $entry) {
    $date = $entry['entry_date'];
    if (!isset($entriesByDate[$date])) {
        $entriesByDate[$date] = [];
    }
    $entriesByDate[$date][] = $entry;
}
?>

<div class="form-outer-container">
    <h2 style="margin-bottom: 1.5rem; text-align:center;">Journal Calendar</h2>

    <?php include __DIR__ . '/../partials/messages.php'; ?>

    <!-- Calendar Navigation -->
    <div class="card" style="padding:1.5rem; background:#fff; max-width:800px; margin:0 auto 2rem;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <a href="?page=journal&action=calendar&year=<?= $month == 1 ? $year - 1 : $year ?>&month=<?= $month == 1 ? 12 : $month - 1 ?>" 
               class="btn btn-secondary">&laquo; Previous</a>
            <h3 style="margin:0; color:#374151;"><?= $monthName ?></h3>
            <a href="?page=journal&action=calendar&year=<?= $month == 12 ? $year + 1 : $year ?>&month=<?= $month == 12 ? 1 : $month + 1 ?>" 
               class="btn btn-secondary">Next &raquo;</a>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="card" style="padding:2rem; background:#fff; max-width:800px; margin:0 auto;">
        <div class="calendar-grid" style="display:grid; grid-template-columns:repeat(7, 1fr); gap:1px; background:#e5e7eb;">
            <!-- Day headers -->
            <div style="background:#f8fafc; padding:1rem; text-align:center; font-weight:500; color:#374151;">Sun</div>
            <div style="background:#f8fafc; padding:1rem; text-align:center; font-weight:500; color:#374151;">Mon</div>
            <div style="background:#f8fafc; padding:1rem; text-align:center; font-weight:500; color:#374151;">Tue</div>
            <div style="background:#f8fafc; padding:1rem; text-align:center; font-weight:500; color:#374151;">Wed</div>
            <div style="background:#f8fafc; padding:1rem; text-align:center; font-weight:500; color:#374151;">Thu</div>
            <div style="background:#f8fafc; padding:1rem; text-align:center; font-weight:500; color:#374151;">Fri</div>
            <div style="background:#f8fafc; padding:1rem; text-align:center; font-weight:500; color:#374151;">Sat</div>

            <?php
            // Empty cells for days before the first day of the month
            for ($i = 0; $i < $firstDayOfWeek; $i++) {
                echo '<div style="background:#f8fafc; padding:1rem; min-height:80px;"></div>';
            }

            // Days of the month
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $isToday = $date === date('Y-m-d');
                $hasEntries = isset($entriesByDate[$date]);
                
                echo '<div style="background:#fff; padding:0.5rem; min-height:80px; border:1px solid #e5e7eb; position:relative;">';
                echo '<div style="font-weight:500; color:' . ($isToday ? '#5d78ff' : '#374151') . '; margin-bottom:0.5rem;">' . $day . '</div>';
                
                if ($hasEntries) {
                    foreach ($entriesByDate[$date] as $entry) {
                        echo '<div style="font-size:0.8em; color:#666; margin-bottom:0.2rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">';
                        echo '<a href="index.php?page=journal&action=view&id=' . $entry['id'] . '" style="color:#5d78ff; text-decoration:none;">';
                        echo htmlspecialchars($entry['title'], ENT_QUOTES);
                        echo '</a>';
                        if ($entry['mood']) {
                            echo ' <span style="font-size:0.7em;">' . str_repeat('😊', $entry['mood']) . '</span>';
                        }
                        echo '</div>';
                    }
                }
                
                echo '</div>';
            }

            // Fill remaining cells to complete the grid
            $remainingCells = (7 - (($firstDayOfWeek + $daysInMonth) % 7)) % 7;
            for ($i = 0; $i < $remainingCells; $i++) {
                echo '<div style="background:#f8fafc; padding:1rem; min-height:80px;"></div>';
            }
            ?>
        </div>
    </div>

    <!-- Legend -->
    <div class="card" style="padding:1.5rem; background:#fff; max-width:800px; margin:2rem auto;">
        <h4 style="margin-bottom:1rem; color:#374151;">Legend</h4>
        <div style="display:flex; gap:2rem; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <div style="width:12px; height:12px; background:#5d78ff; border-radius:2px;"></div>
                <span style="font-size:0.9em; color:#666;">Today</span>
            </div>
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <span style="font-size:0.9em;">😊</span>
                <span style="font-size:0.9em; color:#666;">Mood rating</span>
            </div>
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <div style="width:12px; height:12px; background:#e0e7ff; border-radius:2px;"></div>
                <span style="font-size:0.9em; color:#666;">Has entries</span>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div style="text-align:center; margin-top:2rem;">
        <a href="index.php?page=journal&action=list" class="btn btn-primary">View All Entries</a>
        <a href="index.php?page=journal&action=create" class="btn btn-secondary">New Entry</a>
        <a href="index.php?page=dashboard" class="btn btn-link">Back to Dashboard</a>
    </div>
</div>

 