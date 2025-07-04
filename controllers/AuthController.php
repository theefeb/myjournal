<?php
class AuthController {
    private $userModel;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
    }

    /* Existing Authentication Methods */
    
    public function showLogin() {
        if (isset($_SESSION['user_id'])) {
            redirect('index.php?page=dashboard');
        }
        require_once 'views/auth/login.php';
    }

    public function login() {
        if (!validate_csrf_token($_POST['csrf_token'])) {
            $_SESSION['message'] = 'Invalid CSRF token';
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=login');
        }

        try {
            $identifier = sanitize_input($_POST['username']);
            $password = $_POST['password'];
            $remember = isset($_POST['remember']);

            $user = $this->userModel->login($identifier, $password, $remember);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['last_login'] = time();

            $this->updateLastLogin($user['id']);

            $_SESSION['message'] = 'Welcome back, ' . e($user['username']) . '!';
            $_SESSION['message_type'] = 'success';
            redirect('index.php?page=dashboard');
        } catch (Exception $e) {
            error_log('Login failed: ' . $e->getMessage());
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=login');
        }
    }

    public function showRegister() {
        if (isset($_SESSION['user_id'])) {
            redirect('index.php?page=dashboard');
        }
        require_once 'views/auth/register.php';
    }

    public function register() {
        if (!validate_csrf_token($_POST['csrf_token'])) {
            $_SESSION['message'] = 'Invalid CSRF token';
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=register');
        }

        try {
            $username = sanitize_input($_POST['username']);
            $email = sanitize_input($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if ($password !== $confirm_password) {
                throw new Exception("Passwords do not match");
            }

            $user_id = $this->userModel->register($username, $email, $password);
            $user = $this->userModel->getById($user_id);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            $_SESSION['message'] = 'Registration successful! Welcome to your journal.';
            $_SESSION['message_type'] = 'success';
            redirect('index.php?page=dashboard');
        } catch (Exception $e) {
            error_log('Registration failed: ' . $e->getMessage());
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=register');
        }
    }

    public function logout() {
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');
        redirect('index.php?page=login');
    }

    public function showForgotPassword() {
        require_once 'views/auth/forgot_password.php';
    }

    public function forgotPassword() {
        try {
            $email = sanitize_input($_POST['email']);
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);

            $stmt = $this->pdo->prepare(
                "INSERT INTO password_resets (email, token, expires_at)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?"
            );
            $stmt->execute([$email, $token, $expires, $token, $expires]);

            $reset_link = base_url("index.php?page=reset_password&token=$token");
            $this->sendPasswordResetEmail($email, $reset_link);

            $_SESSION['message'] = 'Password reset link sent to your email';
            $_SESSION['message_type'] = 'success';
            redirect('index.php?page=login');
        } catch (Exception $e) {
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=forgot_password');
        }
    }

    public function showResetPassword($token) {
        try {
            $valid = $this->validatePasswordResetToken($token);
            if (!$valid) {
                throw new Exception("Invalid or expired token");
            }
            require_once 'views/auth/reset_password.php';
        } catch (Exception $e) {
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=login');
        }
    }

    public function resetPassword($token) {
        try {
            $email = $this->validatePasswordResetToken($token);
            if (!$email) {
                throw new Exception("Invalid or expired token");
            }

            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if ($password !== $confirm_password) {
                throw new Exception("Passwords do not match");
            }

            $this->updateUserPassword($email, $password);
            $this->deletePasswordResetToken($token);

            $_SESSION['message'] = 'Password updated successfully. Please login.';
            $_SESSION['message_type'] = 'success';
            redirect('index.php?page=login');
        } catch (Exception $e) {
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = 'error';
            redirect("index.php?page=reset_password&token=$token");
        }
    }

    /* Profile Management Methods */

    public function updateProfile() {
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['message'] = 'Invalid CSRF token';
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=profile');
            return;
        }

        try {
            $userId = $_SESSION['user_id'];
            $username = sanitize_input($_POST['username'] ?? '');
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $bio = sanitize_input($_POST['bio'] ?? '');

            if (empty($username) || !$email) {
                throw new Exception('Username and valid email are required');
            }

            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                $fileType = mime_content_type($_FILES['avatar']['tmp_name']);
                $fileSize = $_FILES['avatar']['size'];
                if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                    $dest = 'uploads/' . $filename;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                        // Update avatar path in DB
                        $this->userModel->updateAvatar($_SESSION['user_id'], $dest);
                    }
                }
            }

            $sql = "UPDATE users SET username = ?, email = ?, bio = ?";
            $params = [$username, $email, $bio];

            $stmt = $this->pdo->prepare($sql);
            if (!$stmt->execute($params)) {
                throw new Exception('Failed to update profile');
            }

            $_SESSION['username'] = $username;
            $_SESSION['message'] = 'Profile updated successfully';
            $_SESSION['message_type'] = 'success';
            redirect('index.php?page=profile');
        } catch (Exception $e) {
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=profile&action=update');
        }
    }

    public function changePassword() {
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['message'] = 'Invalid CSRF token';
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=profile');
            return;
        }

        try {
            $userId = $_SESSION['user_id'];
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('All password fields are required');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match');
            }

            if (strlen($newPassword) < 8) {
                throw new Exception('Password must be at least 8 characters');
            }

            $user = $this->userModel->getById($userId);
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if (!$stmt->execute([$hashedPassword, $userId])) {
                throw new Exception('Failed to update password');
            }

            $_SESSION['message'] = 'Password changed successfully';
            $_SESSION['message_type'] = 'success';
            redirect('index.php?page=profile');
        } catch (Exception $e) {
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=profile&action=change_password');
        }
    }

    public function deleteAccount() {
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['message'] = 'Invalid CSRF token';
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=profile');
            return;
        }

        try {
            $userId = $_SESSION['user_id'];
            $password = $_POST['confirm_password'] ?? '';

            $user = $this->userModel->getById($userId);
            if (!password_verify($password, $user['password'])) {
                throw new Exception('Password confirmation failed');
            }

            $this->pdo->beginTransaction();

            try {
                $tables = ['journal_entries', 'mood_entries', 'password_resets', 'remember_tokens'];
                foreach ($tables as $table) {
                    $stmt = $this->pdo->prepare("DELETE FROM $table WHERE user_id = ?");
                    $stmt->execute([$userId]);
                }

                $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);

                $this->pdo->commit();

                session_destroy();
                setcookie('remember_token', '', time() - 3600, '/');

                $_SESSION['message'] = 'Your account has been deleted';
                $_SESSION['message_type'] = 'success';
                redirect('index.php?page=login');
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = 'error';
            redirect('index.php?page=profile&action=delete');
        }
    }

    /* Helper Methods */

    private function updateLastLogin($user_id) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    }

    private function sendPasswordResetEmail($email, $reset_link) {
        error_log("Password reset email to $email: $reset_link");
    }

    private function validatePasswordResetToken($token) {
        $stmt = $this->pdo->prepare(
            "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()"
        );
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['email'] : false;
    }

    private function updateUserPassword($email, $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);
    }

    private function deletePasswordResetToken($token) {
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
    }

    private function handleAvatarUpload($userId) {
        if (empty($_FILES['avatar']['name'])) {
            return null;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024;

        if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
            throw new Exception('Only JPG, PNG, and GIF images are allowed');
        }

        if ($_FILES['avatar']['size'] > $maxSize) {
            throw new Exception('Image size exceeds 2MB limit');
        }

        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = "user_{$userId}_" . time() . ".{$extension}";
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
            throw new Exception('Failed to upload avatar');
        }

        return "/uploads/avatars/{$filename}";
    }
}