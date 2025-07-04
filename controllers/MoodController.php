<?php
class MoodController {
    private $moodTracker;
    private $user_id;

    public function __construct($pdo) {
        $this->moodTracker = new MoodTracker($pdo);
        $this->user_id = $_SESSION['user_id'] ?? null;
    }

    /**
     * Handle mood submission
     */
    public function recordMood() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $mood = isset($_POST['mood']) ? (int)$_POST['mood'] : null;
            $entry_id = isset($_POST['entry_id']) ? (int)$_POST['entry_id'] : null;
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

            if ($mood < 1 || $mood > 5) {
                echo json_encode(['success' => false, 'message' => 'Invalid mood value']);
                exit;
            }

            $success = $this->moodTracker->recordMood(
                $this->user_id,
                $mood,
                $entry_id,
                $notes
            );

            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to record mood']);
            }
            exit;
        }

        header("HTTP/1.1 405 Method Not Allowed");
        exit;
    }

    /**
     * Get mood data for charts
     */
    public function getMoodData() {
        $period = isset($_GET['period']) ? $_GET['period'] : 'week';
        $data = $this->moodTracker->getMoodData($this->user_id, $period);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Get mood statistics
     */
    public function getMoodStats() {
        $period = isset($_GET['period']) ? $_GET['period'] : 'month';
        $stats = $this->moodTracker->getMoodStats($this->user_id, $period);
        header('Content-Type: application/json');
        echo json_encode($stats);
        exit;
    }

    /**
     * Get mood correlations with habits
     */
    public function getHabitCorrelations() {
        $period = isset($_GET['period']) ? $_GET['period'] : 'month';
        $correlations = $this->moodTracker->getHabitCorrelations($this->user_id, $period);
        header('Content-Type: application/json');
        echo json_encode($correlations);
        exit;
    }
}