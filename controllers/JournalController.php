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
                $_SESSION['message'] = 'Title and content are required';
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=journal&action=create");
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
                $_SESSION['message'] = 'Entry created successfully!';
                $_SESSION['message_type'] = 'success';
                header("Location: index.php?page=journal&action=edit&id=$entry_id");
                exit;
            } else {
                $_SESSION['message'] = 'Failed to create entry';
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=journal&action=create");
                exit;
            }
        }

        // Display create form
        require_once 'views/journal/create.php';
    }

    /**
     * View single entry
     */
    public function view($entry_id) {
        $entry = $this->journalEntry->getById($entry_id, $this->user_id);

        if (!$entry) {
            $_SESSION['message'] = 'Entry not found';
            $_SESSION['message_type'] = 'error';
            header("Location: index.php?page=journal&action=list");
            exit;
        }

        require_once 'views/journal/view.php';
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

        require_once 'views/journal/list.php';
    }

    /**
     * Edit existing entry
     */
    public function edit($entry_id) {
        $entry = $this->journalEntry->getById($entry_id, $this->user_id);

        if (!$entry) {
            $_SESSION['message'] = 'Entry not found';
            $_SESSION['message_type'] = 'error';
            header("Location: index.php?page=journal&action=list");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
            $mood = isset($_POST['mood']) ? (int)$_POST['mood'] : null;

            if (empty($title) || empty($content)) {
                $_SESSION['message'] = 'Title and content are required';
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=journal&action=edit&id=$entry_id");
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
                $_SESSION['message'] = 'Entry updated successfully!';
                $_SESSION['message_type'] = 'success';
                header("Location: index.php?page=journal&action=edit&id=$entry_id");
                exit;
            } else {
                $_SESSION['message'] = 'Failed to update entry';
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=journal&action=edit&id=$entry_id");
                exit;
            }
        }

        require_once 'views/journal/edit.php';
    }

    /**
     * Delete entry
     */
    public function delete($entry_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $success = $this->journalEntry->delete($entry_id, $this->user_id);

            if ($success) {
                $_SESSION['message'] = 'Entry deleted successfully';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Failed to delete entry';
                $_SESSION['message_type'] = 'error';
            }

            header("Location: index.php?page=journal&action=list");
            exit;
        }

        // If not POST request, show confirmation view
        $entry = $this->journalEntry->getById($entry_id, $this->user_id);
        if (!$entry) {
            $_SESSION['message'] = 'Entry not found';
            $_SESSION['message_type'] = 'error';
            header("Location: index.php?page=journal&action=list");
            exit;
        }

        require_once 'views/journal/delete.php';
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