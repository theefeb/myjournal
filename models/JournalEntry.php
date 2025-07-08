<?php
class JournalEntry {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new journal entry
     */
    public function create($user_id, $title, $content, $prompt_id = null, $tags = [], $mood = null) {
        try {
            $this->pdo->beginTransaction();

            // Insert the main entry
            $stmt = $this->pdo->prepare(
                "INSERT INTO journal_entries 
                (user_id, title, content, prompt_id, mood, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$user_id, $title, $content, $prompt_id, $mood]);
            $entry_id = $this->pdo->lastInsertId();

            // Handle tags if provided
            if (!empty($tags)) {
                $this->linkTagsToEntry($entry_id, $tags);
            }

            $this->pdo->commit();
            return $entry_id;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("JournalEntry create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get single entry by ID
     */
    public function getById($entry_id, $user_id) {
        $stmt = $this->pdo->prepare(
            "SELECT journal_entries.*, prompts.content as prompt_content 
            FROM journal_entries 
            LEFT JOIN prompts ON journal_entries.prompt_id = prompts.id 
            WHERE journal_entries.id = ? AND journal_entries.user_id = ?"
        );
        $stmt->execute([$entry_id, $user_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($entry) {
            $entry['tags'] = $this->getEntryTags($entry_id);
        }

        return $entry;
    }

    // In models/JournalEntry.php
public function getAllTags(int $userId): array
{
    try {
        $sql = "SELECT DISTINCT t.name 
                FROM tags t
                JOIN entry_tags et ON t.id = et.tag_id
                JOIN journal_entries je ON et.entry_id = je.id
                WHERE je.user_id = :user_id
                ORDER BY t.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
    } catch (PDOException $e) {
        error_log("Error fetching tags: " . $e->getMessage());
        return [];
    }
}

// In models/JournalEntry.php
public function getPopularTags(int $userId, int $limit = 5): array
{
    try {
        $sql = "SELECT t.name, COUNT(et.tag_id) as tag_count
                FROM tags t
                JOIN entry_tags et ON t.id = et.tag_id
                JOIN journal_entries je ON et.entry_id = je.id
                WHERE je.user_id = :user_id
                GROUP BY t.name
                ORDER BY tag_count DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
    } catch (PDOException $e) {
        error_log("Error fetching popular tags: " . $e->getMessage());
        return [];
    }
}

    /**
     * Get all entries for a user with filters
     */
    public function getAll($user_id, $filters = []) {
        $query = "SELECT journal_entries.*, prompts.content as prompt_content 
                 FROM journal_entries 
                 LEFT JOIN prompts ON journal_entries.prompt_id = prompts.id 
                 WHERE journal_entries.user_id = ?";
        $params = [$user_id];

        // Apply date filters
        if (!empty($filters['start_date'])) {
            $query .= " AND journal_entries.created_at >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $query .= " AND journal_entries.created_at <= ?";
            $params[] = $filters['end_date'];
        }

        // Apply tag filter
        if (!empty($filters['tag'])) {
            $query .= " AND journal_entries.id IN (
                SELECT entry_id FROM entry_tags 
                JOIN tags ON entry_tags.tag_id = tags.id 
                WHERE tags.name = ?
            )";
            $params[] = $filters['tag'];
        }

        // Apply search term
        if (!empty($filters['search'])) {
            $query .= " AND (journal_entries.title LIKE ? OR journal_entries.content LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $query .= " ORDER BY journal_entries.created_at DESC";

        // Apply pagination if needed
        if (!empty($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            if (!empty($filters['offset'])) {
                $query .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
            }
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get tags for each entry
        foreach ($entries as &$entry) {
            $entry['tags'] = $this->getEntryTags($entry['id']);
        }

        return $entries;
    }

    /**
     * Update an existing entry
     */
    public function update($entry_id, $user_id, $title, $content, $tags = [], $mood = null) {
        try {
            $this->pdo->beginTransaction();

            // Update main entry
            $stmt = $this->pdo->prepare(
                "UPDATE journal_entries 
                SET title = ?, content = ?, mood = ?, updated_at = NOW() 
                WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$title, $content, $mood, $entry_id, $user_id]);

            // Handle tags - first remove existing, then add new
            $this->removeAllTagsFromEntry($entry_id);
            if (!empty($tags)) {
                $this->linkTagsToEntry($entry_id, $tags);
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("JournalEntry update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an entry
     */
    public function delete($entry_id, $user_id) {
        try {
            // Debug: Check if entry exists first
            $check_stmt = $this->pdo->prepare(
                "SELECT id FROM journal_entries WHERE id = ? AND user_id = ?"
            );
            $check_stmt->execute([$entry_id, $user_id]);
            $exists = $check_stmt->fetch();
            
            if (!$exists) {
                error_log("Delete failed: Entry $entry_id not found for user $user_id");
                return false;
            }
            
            error_log("Delete proceeding: Entry $entry_id found for user $user_id");
            
            $this->pdo->beginTransaction();

            // First delete tags association
            $this->removeAllTagsFromEntry($entry_id);
            error_log("Tags removed for entry $entry_id");

            // Then delete the entry
            $stmt = $this->pdo->prepare(
                "DELETE FROM journal_entries WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$entry_id, $user_id]);
            $rows_affected = $stmt->rowCount();
            
            error_log("Delete query executed. Rows affected: $rows_affected");

            $this->pdo->commit();
            return $rows_affected > 0;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("JournalEntry delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get entries count for statistics
     */
    public function getCount($user_id) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) as count FROM journal_entries WHERE user_id = ?"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    /**
     * Get entries by month for calendar view
     */
    public function getByMonth($user_id, $year, $month) {
        $start_date = "$year-$month-01";
        $end_date = date("Y-m-t", strtotime($start_date));

        $stmt = $this->pdo->prepare(
            "SELECT id, title, mood, DATE(created_at) as entry_date 
            FROM journal_entries 
            WHERE user_id = ? 
            AND created_at BETWEEN ? AND ? 
            ORDER BY created_at"
        );
        $stmt->execute([$user_id, $start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===== PRIVATE HELPER METHODS ===== //

    private function linkTagsToEntry($entry_id, $tags) {
        foreach ($tags as $tag_name) {
            // Get or create tag
            $tag_stmt = $this->pdo->prepare(
                "INSERT IGNORE INTO tags (name) VALUES (?)"
            );
            $tag_stmt->execute([$tag_name]);

            // Get tag ID
            $tag_id_stmt = $this->pdo->prepare(
                "SELECT id FROM tags WHERE name = ?"
            );
            $tag_id_stmt->execute([$tag_name]);
            $tag_id = $tag_id_stmt->fetchColumn();

            // Link to entry
            $link_stmt = $this->pdo->prepare(
                "INSERT INTO entry_tags (entry_id, tag_id) VALUES (?, ?)"
            );
            $link_stmt->execute([$entry_id, $tag_id]);
        }
    }

    private function getEntryTags($entry_id) {
        $stmt = $this->pdo->prepare(
            "SELECT tags.name FROM tags 
            JOIN entry_tags ON tags.id = entry_tags.tag_id 
            WHERE entry_tags.entry_id = ?"
        );
        $stmt->execute([$entry_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    private function removeAllTagsFromEntry($entry_id) {
        $stmt = $this->pdo->prepare(
            "DELETE FROM entry_tags WHERE entry_id = ?"
        );
        $stmt->execute([$entry_id]);
    }
}