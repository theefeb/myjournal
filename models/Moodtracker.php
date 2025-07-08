<?php
class MoodTracker {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Record a mood entry for a user
     */
    public function recordMood($user_id, $mood, $entry_id = null, $notes = null) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO mood_entries 
                (user_id, mood, entry_id, notes, created_at) 
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE mood = ?, notes = ?, updated_at = NOW()"
            );
            
            return $stmt->execute([
                $user_id, 
                $mood, 
                $entry_id, 
                $notes,
                $mood,
                $notes
            ]);
        } catch (PDOException $e) {
            error_log("MoodTracker record error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get mood entries for a time period
     */
    public function getMoodData($user_id, $period = 'week') {
        $dateCondition = "";
        $params = [$user_id];

        switch ($period) {
            case 'week':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            default:
                // All time
                break;
        }

        $query = "SELECT 
                    DATE(created_at) as date, 
                    AVG(mood) as avg_mood,
                    COUNT(*) as entries_count
                  FROM mood_entries 
                  WHERE user_id = ? $dateCondition
                  GROUP BY DATE(created_at)
                  ORDER BY date";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get mood statistics
     */
    public function getMoodStats($user_id, $period = 'month') {
        $dateCondition = "";
        $params = [$user_id];

        switch ($period) {
            case 'week':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            default:
                // All time
                break;
        }

        $query = "SELECT 
                    AVG(mood) as avg_mood,
                    MIN(mood) as min_mood,
                    MAX(mood) as max_mood,
                    COUNT(*) as total_entries,
                    SUM(CASE WHEN mood >= 4 THEN 1 ELSE 0 END) as positive_days,
                    SUM(CASE WHEN mood <= 2 THEN 1 ELSE 0 END) as negative_days
                  FROM mood_entries 
                  WHERE user_id = ? $dateCondition";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get mood correlation with habits
     */
    public function getHabitCorrelations($user_id, $period = 'month') {
        // This assumes you have a habits tracking table
        $dateCondition = "";
        
        switch ($period) {
            case 'week':
                $dateCondition = "AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "AND m.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $dateCondition = "AND m.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }

        $query = "SELECT 
                    h.habit_name,
                    COUNT(*) as days_recorded,
                    AVG(m.mood) as avg_mood_with_habit,
                    (SELECT AVG(mood) FROM mood_entries WHERE user_id = ? $dateCondition) as overall_avg_mood
                  FROM mood_entries m
                  JOIN habit_entries h ON DATE(m.created_at) = DATE(h.entry_date) AND h.user_id = m.user_id
                  WHERE m.user_id = ? $dateCondition
                  GROUP BY h.habit_name
                  HAVING days_recorded > 2
                  ORDER BY (avg_mood_with_habit - overall_avg_mood) DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get current mood streak
     */
    public function getCurrentStreak($user_id) {
        $query = "WITH ranked_dates AS (
                    SELECT 
                        DATE(created_at) as date,
                        ROW_NUMBER() OVER (ORDER BY DATE(created_at) DESC) as row_num
                    FROM mood_entries
                    WHERE user_id = ?
                    GROUP BY DATE(created_at)
                  SELECT 
                    MIN(date) as streak_start,
                    MAX(date) as streak_end,
                    COUNT(*) as streak_length
                  FROM ranked_dates
                  WHERE DATE_SUB(CURDATE(), INTERVAL row_num DAY) = date";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get today's mood entry
     */
    public function getTodayMood($user_id) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM mood_entries 
             WHERE user_id = ? AND DATE(created_at) = CURDATE()
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Log a mood entry (alias for recordMood)
     */
    public function logMood($user_id, $mood, $notes = null, $entry_id = null) {
        return $this->recordMood($user_id, $mood, $entry_id, $notes);
    }

    /**
     * Get mood history with pagination
     */
    public function getMoodHistory($user_id, $period = 'month', $limit = 20, $offset = 0) {
        $dateCondition = "";
        $params = [$user_id];

        switch ($period) {
            case 'week':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            default:
                // All time
                break;
        }

        $query = "SELECT * FROM mood_entries 
                  WHERE user_id = ? $dateCondition
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get mood count for pagination
     */
    public function getMoodCount($user_id, $period = 'month') {
        $dateCondition = "";
        $params = [$user_id];

        switch ($period) {
            case 'week':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            default:
                // All time
                break;
        }

        $query = "SELECT COUNT(*) FROM mood_entries 
                  WHERE user_id = ? $dateCondition";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Get mood trends for charts
     */
    public function getMoodTrends($user_id, $period = 'month') {
        $dateCondition = "";
        $params = [$user_id];

        switch ($period) {
            case 'week':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            default:
                // All time
                break;
        }

        $query = "SELECT 
                    DATE(created_at) as date,
                    AVG(mood) as avg_mood,
                    COUNT(*) as count
                  FROM mood_entries 
                  WHERE user_id = ? $dateCondition
                  GROUP BY DATE(created_at)
                  ORDER BY date";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}