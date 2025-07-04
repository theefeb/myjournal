<?php
// Application Constants
define('APP_NAME', 'My Journal');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // 'production' or 'development'

// Directory Separator for cross-platform compatibility
define('DS', DIRECTORY_SEPARATOR);

// Path Constants (using DS for reliability)
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . DS . 'public');
define('APP_PATH', BASE_PATH . DS . 'app');
define('VIEWS_PATH', APP_PATH . DS . 'views');
define('UPLOADS_PATH', PUBLIC_PATH . DS . 'uploads');
define('CONFIG_PATH', BASE_PATH . DS . 'config');
define('INCLUDES_PATH', BASE_PATH . DS . 'includes');

// URL Constants (ensure these match your virtual host)
define('BASE_URL', 'http://localhost/journal-management-system');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Session Constants
define('SESSION_NAME', 'JOURNAL_SESSID');
define('SESSION_LIFETIME', 86400); // 1 day in seconds
define('SESSION_SECURE', false); // Set to true in production with HTTPS
define('SESSION_HTTPONLY', true);
define('SESSION_PATH', BASE_PATH . DS . 'sessions');

// Security Constants
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_RESET_EXPIRE', 3600); // 1 hour in seconds
define('REMEMBER_ME_EXPIRE', 2592000); // 30 days in seconds

// Database Constants (verified)
define('DB_HOST', 'localhost');
define('DB_NAME', 'journal_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4'); // Fixed: removed trailing underscore
define('DB_PORT', '3307'); // Added explicit port

// Pagination
define('ITEMS_PER_PAGE', 10);
define('MAX_PAGINATION_LINKS', 5);

// Mood Tracking
define('MOOD_MIN', 1);
define('MOOD_MAX', 5);
define('MOOD_DEFAULT', 3);

// File Uploads
define('MAX_UPLOAD_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf' // Added PDF support
]);
define('UPLOAD_OVERWRITE', false); // Prevent overwriting existing files

// Date/Time
define('DATE_FORMAT', 'F j, Y');
define('TIME_FORMAT', 'g:i a');
define('DATETIME_FORMAT', DATE_FORMAT . ' ' . TIME_FORMAT);
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    define('DEBUG_MODE', true);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    define('DEBUG_MODE', false);
}

// Additional Security Headers (can be used in your bootstrap)
define('CONTENT_SECURITY_POLICY', "default-src 'self'");
define('X_FRAME_OPTIONS', 'DENY');
define('X_XSS_PROTECTION', '1; mode=block');