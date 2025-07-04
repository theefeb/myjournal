<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['message'] = 'Please fill in all fields';
        $_SESSION['message_type'] = 'error';
        header("Location: ../index.php?page=register");
        exit;
    }

    // Check password match
    if ($password !== $confirm_password) {
        $_SESSION['message'] = 'Passwords do not match';
        $_SESSION['message_type'] = 'error';
        header("Location: ../index.php?page=register");
        exit;
    }

    // Check password strength
    if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/", $password)) {
        $_SESSION['message'] = 'Password must be at least 8 characters with 1 number and 1 special character';
        $_SESSION['message_type'] = 'error';
        header("Location: ../index.php?page=register");
        exit;
    }

    // Check if username/email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = 'Username or email already exists';
        $_SESSION['message_type'] = 'error';
        header("Location: ../index.php?page=register");
        exit;
    }

    // Create user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$username, $email, $hashed_password])) {
        $_SESSION['message'] = 'Registration successful! Please log in';
        $_SESSION['message_type'] = 'success';
        header("Location: ../index.php?page=login");
        exit;
    } else {
        $_SESSION['message'] = 'Registration failed. Please try again';
        $_SESSION['message_type'] = 'error';
        header("Location: ../index.php?page=register");
        exit;
    }
}
?>