<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Validate inputs
    if (empty($username) || empty($password)) {
        $_SESSION['message'] = 'Please fill in all fields';
        $_SESSION['message_type'] = 'error';
        header("Location: ../index.php?page=login");
        exit;
    }

    // Check user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        $_SESSION['user_id'] = $user['id'];
        
        if ($remember) {
            // Set remember me cookie (30 days)
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/');
            
            // Store token in database
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $user['id']]);
        }
        
        header("Location: ../index.php?page=dashboard");
        exit;
    } else {
        $_SESSION['message'] = 'Invalid username or password';
        $_SESSION['message_type'] = 'error';
        header("Location: ../index.php?page=login");
        exit;
    }
}
?>