<?php
class Prompt {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get today's prompt for a user
     */
    public function getTodaysPrompt($user_id) {
        try {
            // Check if user already has a prompt for today
            $stmt = $this->pdo->prepare(
                "SELECT p.* FROM user_prompts up
                JOIN prompts p ON up.prompt_id = p.id
                WHERE up.user_id = ? AND DATE(up.assigned_at) = CURDATE()"
            );
            $stmt->execute([$user_id]);
            $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($prompt) {
                return $prompt;
            }

            // Get a prompt the user hasn't seen recently
            $stmt = $this->pdo->prepare(
                "SELECT p.* FROM prompts p
                WHERE p.id NOT IN (
                    SELECT prompt_id FROM user_prompts 
                    WHERE user_id = ? 
                    AND assigned_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY RAND() LIMIT 1"
            );
            $stmt->execute([$user_id]);
            $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

            // If all prompts have been used in last 30 days, get any random one
            if (!$prompt) {
                $stmt = $this->pdo->prepare(
                    "SELECT * FROM prompts ORDER BY RAND() LIMIT 1"
                );
                $stmt->execute();
                $prompt = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Record this prompt assignment
            if ($prompt) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO user_prompts (user_id, prompt_id, assigned_at)
                    VALUES (?, ?, NOW())"
                );
                $stmt->execute([$user_id, $prompt['id']]);
            }

            return $prompt ?: null;
        } catch (PDOException $e) {
            error_log("Prompt getTodaysPrompt error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a new random prompt (without recording it)
     */
    public function getRandomPrompt($user_id) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM prompts 
                WHERE id NOT IN (
                    SELECT prompt_id FROM user_prompts 
                    WHERE user_id = ? 
                    AND assigned_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                )
                ORDER BY RAND() LIMIT 1"
            );
            $stmt->execute([$user_id]);
            $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

            // If all prompts have been used in last 7 days, get any random one
            if (!$prompt) {
                $stmt = $this->pdo->prepare(
                    "SELECT * FROM prompts ORDER BY RAND() LIMIT 1"
                );
                $stmt->execute();
                $prompt = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $prompt ?: null;
        } catch (PDOException $e) {
            error_log("Prompt getRandomPrompt error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all prompts in the system (admin function)
     */
    public function getAllPrompts($page = 1, $per_page = 20) {
        try {
            $offset = ($page - 1) * $per_page;
            $stmt = $this->pdo->prepare(
                "SELECT * FROM prompts 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?"
            );
            $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Prompt getAllPrompts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add a new prompt to the system
     */
    public function addPrompt($content, $category = null, $author = 'system') {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO prompts (content, category, author, created_at)
                VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$content, $category, $author]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Prompt addPrompt error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get prompt categories with counts
     */
    public function getCategories() {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT category, COUNT(*) as count 
                FROM prompts 
                WHERE category IS NOT NULL
                GROUP BY category 
                ORDER BY count DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Prompt getCategories error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user's prompt history
     */
    public function getUserPromptHistory($user_id, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT p.content, up.assigned_at 
                FROM user_prompts up
                JOIN prompts p ON up.prompt_id = p.id
                WHERE up.user_id = ?
                ORDER BY up.assigned_at DESC
                LIMIT ?"
            );
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Prompt getUserPromptHistory error: " . $e->getMessage());
            return [];
        }
    }
}