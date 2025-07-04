<?php
class UserController {
    private $userModel;

    public function __construct($pdo) {
        $this->userModel = new User($pdo);
    }

    /**
     * Handle user registration
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];

                $user_id = $this->userModel->register($username, $email, $password);

                // Auto-login after registration
                $user = $this->userModel->getById($user_id);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                $_SESSION['message'] = 'Registration successful! Welcome!';
                $_SESSION['message_type'] = 'success';
                header("Location: index.php?page=dashboard");
                exit;
            } catch (Exception $e) {
                $_SESSION['message'] = $e->getMessage();
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=register");
                exit;
            }
        }

        require_once 'views/auth/register.php';
    }

    /**
     * Handle user login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $identifier = trim($_POST['username']);
                $password = $_POST['password'];
                $remember = isset($_POST['remember']);

                $user = $this->userModel->login($identifier, $password, $remember);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_login'] = time();

                // Update last login timestamp
                $this->updateLastLogin($user['id']);

                $_SESSION['message'] = 'Login successful! Welcome back!';
                $_SESSION['message_type'] = 'success';
                header("Location: index.php?page=dashboard");
                exit;
            } catch (Exception $e) {
                $_SESSION['message'] = $e->getMessage();
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=login");
                exit;
            }
        }

        require_once 'views/auth/login.php';
    }

    /**
     * Handle password reset request
     */
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            
            try {
                // Verify email exists
                $stmt = $this->pdo->prepare(
                    "SELECT id FROM users WHERE email = ?"
                );
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    throw new Exception("No account found with that email");
                }

                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration

                // Store token
                $stmt = $this->pdo->prepare(
                    "INSERT INTO password_resets (user_id, token, expires_at)
                    VALUES (?, ?, ?)"
                );
                $stmt->execute([$user['id'], $token, $expires]);

                // Send email (implementation depends on your email service)
                $reset_link = "https://yourdomain.com/index.php?page=reset_password&token=$token";
                // sendPasswordResetEmail($email, $reset_link);

                $_SESSION['message'] = 'Password reset link sent to your email';
                $_SESSION['message_type'] = 'success';
                header("Location: index.php?page=login");
                exit;
            } catch (Exception $e) {
                $_SESSION['message'] = $e->getMessage();
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=forgot_password");
                exit;
            }
        }

        require_once 'views/auth/forgot_password.php';
    }

    /**
     * Handle profile update
     */
    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'username' => trim($_POST['username']),
                    'email' => trim($_POST['email']),
                    'bio' => trim($_POST['bio'] ?? '')
                ];

                // Handle avatar upload
                if (!empty($_FILES['avatar']['name'])) {
                    $avatar = $this->handleAvatarUpload($_SESSION['user_id']);
                    if ($avatar) {
                        $data['avatar'] = $avatar;
                    }
                }

                $success = $this->userModel->updateProfile($_SESSION['user_id'], $data);

                if ($success) {
                    $_SESSION['username'] = $data['username'];
                    $_SESSION['message'] = 'Profile updated successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'No changes were made';
                    $_SESSION['message_type'] = 'info';
                }
                header("Location: index.php?page=profile");
                exit;
            } catch (Exception $e) {
                $_SESSION['message'] = $e->getMessage();
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=profile");
                exit;
            }
        }

        header("Location: index.php?page=profile");
        exit;
    }

    /**
     * Handle password change
     */
    public function changePassword() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];

                $success = $this->userModel->changePassword(
                    $_SESSION['user_id'],
                    $current_password,
                    $new_password
                );

                if ($success) {
                    $_SESSION['message'] = 'Password changed successfully!';
                    $_SESSION['message_type'] = 'success';
                }
                header("Location: index.php?page=profile");
                exit;
            } catch (Exception $e) {
                $_SESSION['message'] = $e->getMessage();
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=profile");
                exit;
            }
        }

        header("Location: index.php?page=profile");
        exit;
    }

    /**
     * Handle account deletion
     */
    public function deleteAccount() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $password = $_POST['password'];

                $success = $this->userModel->deleteAccount(
                    $_SESSION['user_id'],
                    $password
                );

                if ($success) {
                    session_destroy();
                    $_SESSION['message'] = 'Your account has been deleted';
                    $_SESSION['message_type'] = 'success';
                    header("Location: index.php?page=login");
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['message'] = $e->getMessage();
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?page=profile");
                exit;
            }
        }

        require_once 'views/auth/delete_account.php';
    }

    /**
     * Handle user logout
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->userModel->logout($_SESSION['user_id']);
        }

        session_destroy();
        header("Location: index.php?page=login");
        exit;
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($user_id) {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET last_login = NOW() WHERE id = ?"
        );
        $stmt->execute([$user_id]);
    }

    /**
     * Handle avatar upload
     */
    private function handleAvatarUpload($user_id) {
        $upload_dir = 'assets/uploads/avatars/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error");
        }

        if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
            throw new Exception("Only JPG, PNG, and GIF images are allowed");
        }

        if ($_FILES['avatar']['size'] > $max_size) {
            throw new Exception("File size must be less than 2MB");
        }

        $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = "user_{$user_id}_" . time() . ".{$extension}";
        $destination = $upload_dir . $filename;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
            throw new Exception("Failed to save uploaded file");
        }

        // Resize image if needed
        $this->resizeImage($destination, 200, 200);

        return $filename;
    }

    /**
     * Resize uploaded image
     */
    private function resizeImage($filepath, $width, $height) {
        $info = getimagesize($filepath);
        $mime = $info['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filepath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filepath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filepath);
                break;
            default:
                return false;
        }

        $src_w = imagesx($image);
        $src_h = imagesy($image);

        $dst_image = imagecreatetruecolor($width, $height);

        // Preserve transparency for PNG/GIF
        if ($mime == 'image/png' || $mime == 'image/gif') {
            imagecolortransparent($dst_image, imagecolorallocatealpha($dst_image, 0, 0, 0, 127));
            imagealphablending($dst_image, false);
            imagesavealpha($dst_image, true);
        }

        imagecopyresampled($dst_image, $image, 0, 0, 0, 0, $width, $height, $src_w, $src_h);

        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($dst_image, $filepath, 90);
                break;
            case 'image/png':
                imagepng($dst_image, $filepath, 9);
                break;
            case 'image/gif':
                imagegif($dst_image, $filepath);
                break;
        }

        imagedestroy($image);
        imagedestroy($dst_image);

        return true;
    }
}