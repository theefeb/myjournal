<?php
/**
 * Redirect to another URL
 */
function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Escape HTML output to prevent XSS
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date in a user-friendly way
 */
function format_date($date_string, $format = 'F j, Y') {
    $date = new DateTime($date_string);
    return $date->format($format);
}

/**
 * Get relative time (e.g., "2 hours ago")
 */
function relative_time($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $units = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second'
    ];

    foreach ($units as $unit => $text) {
        if ($diff->$unit) {
            // Handle pluralization
            $text .= $diff->$unit > 1 ? 's' : '';
            
            // Determine past/future
            return $diff->invert ? 
                "$diff->$unit $text ago" : 
                "in $diff->$unit $text";
        }
    }

    return 'just now';
}

/**
 * Truncate text with ellipsis
 */
function truncate($text, $length = 100, $ellipsis = '...') {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . $ellipsis;
    }
    return $text;
}

/**
 * Get current URL with query parameters
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}

/**
 * Add query parameters to URL
 */
function add_query_params($url, $params) {
    $query = parse_url($url, PHP_URL_QUERY);
    $url = strtok($url, '?');
    
    if ($query) {
        parse_str($query, $existing_params);
        $params = array_merge($existing_params, $params);
    }
    
    return $url . '?' . http_build_query($params);
}

/**
 * Get client IP address
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return trim(htmlspecialchars(strip_tags($data)));
}

/**
 * Generate a random string
 */
function random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Check if request is AJAX
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Convert array to JSON and send response
 */
function json_response($data = null, $status = 200, $message = '') {
    header_remove();
    header('Content-Type: application/json');
    http_response_code($status);
    
    $response = [
        'status' => $status < 300 ? 'success' : 'error',
        'message' => $message,
        'data' => $data
    ];
    
    echo json_encode($response);
    exit;
}

/**
 * Get the first error from an array of errors
 */
function first_error($errors) {
    if (is_array($errors) && !empty($errors)) {
        return reset($errors);
    }
    return null;
}

/**
 * Generate a unique filename
 */
function unique_filename($directory, $extension) {
    do {
        $filename = uniqid() . '.' . $extension;
    } while (file_exists($directory . $filename));
    
    return $filename;
}

/**
 * Convert markdown to HTML (simple implementation)
 */
function markdown_to_html($text) {
    $replacements = [
        '/\*\*(.*?)\*\*/' => '<strong>$1</strong>',
        '/\*(.*?)\*/' => '<em>$1</em>',
        '/\[(.*?)\]\((.*?)\)/' => '<a href="$2">$1</a>',
        '/\n\n/' => '</p><p>',
        '/\n/' => '<br>'
    ];
    
    $html = '<p>' . preg_replace(
        array_keys($replacements),
        array_values($replacements),
        $text
    ) . '</p>';
    
    return $html;
}

/**
 * Check if string starts with a substring
 */
function starts_with($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Check if string ends with a substring
 */
function ends_with($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}

/**
 * Get human-readable file size
 */
function format_filesize($bytes, $decimals = 2) {
    $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $size[$factor];
}

/**
 * Validate email address
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate URL
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

/**
 * Get base URL of the application
 */
function base_url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    return rtrim($protocol . '://' . $host . $base, '/') . '/' . ltrim($path, '/');
}

/**
 * Flash message helper
 */
function flash($name, $message = null, $type = 'info') {
    if ($message === null) {
        // Get and clear the message
        if (isset($_SESSION['flash'][$name])) {
            $message = $_SESSION['flash'][$name]['message'];
            $type = $_SESSION['flash'][$name]['type'];
            unset($_SESSION['flash'][$name]);
            return ['message' => $message, 'type' => $type];
        }
        return null;
    } else {
        // Set the message
        $_SESSION['flash'][$name] = [
            'message' => $message,
            'type' => $type
        ];
    }
}

/**
 * Dump variable and die (for debugging)
 */
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}