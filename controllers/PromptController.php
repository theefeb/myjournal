<?php
class PromptController {
    private $promptModel;
    private $user_id;

    public function __construct($pdo) {
        $this->promptModel = new Prompt($pdo);
        $this->user_id = $_SESSION['user_id'] ?? null;
    }

    /**
     * Get today's prompt for the user
     */
    public function getTodaysPrompt() {
        if (!$this->user_id) {
            header("HTTP/1.1 401 Unauthorized");
            exit;
        }

        $prompt = $this->promptModel->getTodaysPrompt($this->user_id);
        
        header('Content-Type: application/json');
        echo json_encode($prompt ?: ['content' => 'No prompt available today']);
        exit;
    }

    /**
     * Get a new random prompt
     */
    public function getRandomPrompt() {
        if (!$this->user_id) {
            header("HTTP/1.1 401 Unauthorized");
            exit;
        }

        $prompt = $this->promptModel->getRandomPrompt($this->user_id);
        
        header('Content-Type: application/json');
        echo json_encode($prompt ?: ['content' => 'No prompts available']);
        exit;
    }

    /**
     * Get prompt history for the user
     */
    public function getPromptHistory() {
        if (!$this->user_id) {
            header("HTTP/1.1 401 Unauthorized");
            exit;
        }

        $history = $this->promptModel->getUserPromptHistory($this->user_id);
        
        header('Content-Type: application/json');
        echo json_encode($history);
        exit;
    }

    /**
     * Admin: Add a new prompt
     */
    public function addPrompt() {
        // Verify admin privileges here
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = trim($_POST['content'] ?? '');
            $category = trim($_POST['category'] ?? '');
            
            if (empty($content)) {
                $_SESSION['message'] = 'Prompt content is required';
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=prompts&action=add");
                exit;
            }

            $id = $this->promptModel->addPrompt($content, $category);
            
            if ($id) {
                $_SESSION['message'] = 'Prompt added successfully!';
                $_SESSION['message_type'] = 'success';
                header("Location: index.php?page=prompts&action=edit&id=$id");
                exit;
            } else {
                $_SESSION['message'] = 'Failed to add prompt';
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=prompts&action=add");
                exit;
            }
        }

        require_once 'views/admin/prompts/add.php';
    }
}