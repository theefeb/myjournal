<?php
class JournalController {
    private $journalEntry;
    private $user_id;

    public function __construct($pdo) {
        $this->journalEntry = new JournalEntry($pdo);
        $this->user_id = $_SESSION['user_id'] ?? null;
    }

    /**
     * Handle entry creation
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate input
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $prompt_id = isset($_POST['prompt_id']) ? (int)$_POST['prompt_id'] : null;
            $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
            $mood = isset($_POST['mood']) ? (int)$_POST['mood'] : null;

            if (empty($title) || empty($content)) {
                $_SESSION['error'] = 'Title and content are required';
                header("Location: ../../index.php?page=journal&action=create");
                exit;
            }

            // Create entry
            $entry_id = $this->journalEntry->create(
                $this->user_id,
                $title,
                $content,
                $prompt_id,
                $tags,
                $mood
            );

            if ($entry_id) {
                $_SESSION['success'] = 'Entry created successfully!';
                header("Location: ../../index.php?page=journal&action=view&id=$entry_id");
                exit;
            } else {
                $_SESSION['error'] = 'Failed to create entry';
                header("Location: ../../index.php?page=journal&action=create");
                exit;
            }
        }

        // Display create form
        require_once __DIR__ . '/../views/journal/create.php';
    }

    /**
     * View single entry
     */
    public function view($entry_id) {
        $entry = $this->journalEntry->getById($entry_id, $this->user_id);

        if (!$entry) {
            $_SESSION['error'] = 'Entry not found';
            header("Location: ../../index.php?page=journal&action=list");
            exit;
        }

        require_once __DIR__ . '/../views/journal/view.php';
    }

    /**
     * List all entries with optional filters
     */
    public function list() {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'tag' => $_GET['tag'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? '',
            'limit' => 10,
            'offset' => ($_GET['page'] ?? 0) * 10
        ];

        $entries = $this->journalEntry->getAll($this->user_id, $filters);
        $total_entries = $this->journalEntry->getCount($this->user_id);
        $total_pages = ceil($total_entries / $filters['limit']);

        require_once __DIR__ . '/../views/journal/list.php';
    }

    /**
     * Edit existing entry
     */
    public function edit($entry_id) {
        $entry = $this->journalEntry->getById($entry_id, $this->user_id);

        if (!$entry) {
            $_SESSION['error'] = 'Entry not found';
            header("Location: ../../index.php?page=journal&action=list");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
            $mood = isset($_POST['mood']) ? (int)$_POST['mood'] : null;

            if (empty($title) || empty($content)) {
                $_SESSION['error'] = 'Title and content are required';
                header("Location: ../../index.php?page=journal&action=edit&id=$entry_id");
                exit;
            }

            $success = $this->journalEntry->update(
                $entry_id,
                $this->user_id,
                $title,
                $content,
                $tags,
                $mood
            );

            if ($success) {
                $_SESSION['success'] = 'Entry updated successfully!';
                header("Location: ../../index.php?page=journal&action=view&id=$entry_id");
                exit;
            } else {
                $_SESSION['error'] = 'Failed to update entry';
                header("Location: ../../index.php?page=journal&action=edit&id=$entry_id");
                exit;
            }
        }

        require_once __DIR__ . '/../views/journal/edit.php';
    }

    /**
     * Delete entry
     */
    public function delete($entry_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            // Debug: Log the deletion attempt
            error_log("Delete attempt - Entry ID: $entry_id, User ID: {$this->user_id}");
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                error_log("CSRF token validation failed for entry deletion");
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Invalid form submission']);
                    exit;
                } else {
                    $_SESSION['error'] = "Invalid form submission";
                    header("Location: ../../index.php?page=journal&action=list");
                    exit;
                }
            }
            $success = $this->journalEntry->delete($entry_id, $this->user_id);
            // Debug: Log the result
            error_log("Delete result - Success: " . ($success ? 'true' : 'false'));
            if ($isAjax) {
                header('Content-Type: application/json');
                if ($success) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to delete entry']);
                }
                exit;
            } else {
                if ($success) {
                    $_SESSION['success'] = "Entry deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete entry";
                }
                header("Location: ../../index.php?page=journal&action=list");
                exit;
            }
        }
        // If not POST request, show confirmation view
        $entry = $this->journalEntry->getById($entry_id, $this->user_id);
        if (!$entry) {
            $_SESSION['error'] = "Entry not found";
            header("Location: ../../index.php?page=journal&action=list");
            exit;
        }
        require_once __DIR__ . '/../views/journal/delete.php';
    }

    /**
     * Calendar view of entries
     */
    public function calendar() {
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

        $entries = $this->journalEntry->getByMonth($this->user_id, $year, $month);

        require_once 'views/journal/calendar.php';
    }
}