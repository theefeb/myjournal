<?php
// Start session
session_start();


// Load configuration files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/security.php';

// Initialize database connection
try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Load helpers
require_once __DIR__ . '/includes/helpers.php';

// Load models
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/JournalEntry.php';
require_once __DIR__ . '/models/Prompt.php';
require_once __DIR__ . '/models/MoodTracker.php';

// Load controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/JournalController.php';
require_once __DIR__ . '/controllers/PromptController.php';
require_once __DIR__ . '/controllers/MoodController.php';

// Initialize controllers
$authController = new AuthController($pdo);
$journalController = new JournalController($pdo);
$promptController = new PromptController($pdo);
$moodController = new MoodController($pdo);

// Get requested page
$page = $_GET['page'] ?? 'dashboard';

// Define public pages (don't require authentication)
$publicPages = ['login', 'register', 'forgot_password', 'reset_password'];

// Check authentication for protected pages
if (!isset($_SESSION['user_id']) && !in_array($page, $publicPages)) {
    redirect('index.php?page=login');
}

// Route the request
switch ($page) {
    // Authentication routes
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            require_once __DIR__ . '/views/auth/login.php';
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->register();
        } else {
            require_once __DIR__ . '/views/auth/register.php';
        }
        break;

    case 'logout':
        $authController->logout();
        break;

    case 'forgot_password':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->forgotPassword();
        } else {
            require_once __DIR__ . '/views/auth/forgot_password.php';
        }
        break;

    case 'reset_password':
        $token = $_GET['token'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->resetPassword($token);
        } else {
            require_once __DIR__ . '/views/auth/reset_password.php';
        }
        break;

    // Journal routes
    case 'journal':
        $action = $_GET['action'] ?? 'list';
        $entryId = $_GET['id'] ?? null;

        // Debug logging
        error_log("Journal routing - Action: $action, Entry ID: $entryId, Method: " . $_SERVER['REQUEST_METHOD']);

        switch ($action) {
            case 'create':
                require_once __DIR__ . '/views/journal/create.php';
                break;

            case 'edit':
                require_once __DIR__ . '/views/journal/edit.php';
                break;

            case 'view':
                require_once __DIR__ . '/views/journal/view.php';
                break;

            case 'delete':
                error_log("Delete action called with entry ID: $entryId");
                $journalController->delete($entryId);
                break;

            case 'list':
                require_once __DIR__ . '/views/journal/list.php';
                break;

            case 'calendar':
                require_once __DIR__ . '/views/journal/calendar.php';
                break;

            default:
                require_once __DIR__ . '/views/journal/list.php';
                break;
        }
        break;

    // Dashboard route
    case 'dashboard':
        require_once __DIR__ . '/views/dashboard.php';
        break;

    // Mood tracker route
    case 'mood':
        $action = $_GET['action'] ?? 'track';
        switch ($action) {
            case 'track':
                require_once __DIR__ . '/views/mood/track.php';
                break;
            case 'history':
                require_once __DIR__ . '/views/mood/history.php';
                break;
            case 'stats':
                require_once __DIR__ . '/views/mood/stats.php';
                break;
            default:
                require_once __DIR__ . '/views/mood/track.php';
                break;
        }
        break;

    // Calendar route
    case 'calendar':
        require_once __DIR__ . '/views/journal/calendar.php';
        break;

    // Static pages routes
    case 'help':
        require_once __DIR__ . '/views/static/help.php';
        break;

    case 'terms':
        require_once __DIR__ . '/views/static/terms.php';
        break;

    case 'privacy':
        require_once __DIR__ . '/views/static/privacy.php';
        break;

    // Profile route
    case 'profile':
        // Verify user is logged in (redundant check for safety)
        if (!isset($_SESSION['user_id'])) {
            redirect('index.php?page=login');
            exit;
        }
    
        // Sanitize and validate action
        $allowedActions = ['update', 'change_password', 'delete', ''];
        $action = in_array($_GET['action'] ?? '', $allowedActions) ? ($_GET['action'] ?? '') : '';
        
        switch ($action) {
            case 'update':
                // Validate CSRF for POST requests
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                        $_SESSION['error'] = "Invalid form submission";
                        redirect('index.php?page=profile');
                        exit;
                    }
                    
                    try {
                        $authController->updateProfile();
                    } catch (Exception $e) {
                        error_log("Profile update error: " . $e->getMessage());
                        $_SESSION['error'] = "Failed to update profile";
                        redirect('index.php?page=profile');
                    }
                } else {
                    // Show form for GET requests
                    require_once __DIR__ . '/views/user/profile_edit.php';
                }
                break;
    
            case 'change_password':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                        $_SESSION['error'] = "Invalid form submission";
                        redirect('index.php?page=profile');
                        exit;
                    }
                    
                    try {
                        $authController->changePassword();
                    } catch (Exception $e) {
                        error_log("Password change error: " . $e->getMessage());
                        $_SESSION['error'] = "Failed to change password";
                        redirect('index.php?page=profile&action=change_password');
                    }
                } else {
                    require_once __DIR__ . '/views/user/change_password.php';
                }
                break;
    
            case 'delete':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                        $_SESSION['error'] = "Invalid form submission";
                        redirect('index.php?page=profile');
                        exit;
                    }
                    
                    try {
                        $authController->deleteAccount();
                        // Should redirect to logout or home after deletion
                        redirect('index.php?page=logout');
                        exit;
                    } catch (Exception $e) {
                        error_log("Account deletion error: " . $e->getMessage());
                        $_SESSION['error'] = "Failed to delete account";
                        redirect('index.php?page=profile');
                    }
                } else {
                    // Show confirmation for GET requests
                    require_once __DIR__ . '/views/user/delete_confirm.php';
                }
                break;
    
            default:
                // Main profile view
                try {
                    $user = (new User($pdo))->getById($_SESSION['user_id']);
                    if (!$user) {
                        throw new Exception("User not found");
                    }
                    require_once __DIR__ . '/views/user/profile.php';
                } catch (Exception $e) {
                    error_log("Profile error: " . $e->getMessage());
                    $_SESSION['error'] = "Could not load profile";
                    redirect('index.php?page=dashboard');
                }
                break;
        }
        break;

    // 404 - Not Found
    default:
        header("HTTP/1.0 404 Not Found");
        require_once __DIR__ . '/views/errors/404.php';
        break;
}