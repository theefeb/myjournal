<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Register a new user
     */
    public function register($username, $email, $password) {
        try {
            // Validate input
            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception("All fields are required");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            if (strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters");
            }

            // Check if username/email exists
            $stmt = $this->pdo->prepare(
                "SELECT id FROM users WHERE username = ? OR email = ?"
            );
            $stmt->execute([$username, $email]);

            if ($stmt->rowCount() > 0) {
                throw new Exception("Username or email already exists");
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (username, email, password, created_at) 
                VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$username, $email, $hashed_password]);

            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("User registration error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Authenticate user
     */
    public function login($identifier, $password, $remember = false) {
        try {
            // Find user by username or email
            $stmt = $this->pdo->prepare(
                "SELECT * FROM users WHERE username = ? OR email = ?"
            );
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception("Invalid username or password");
            }

            // Generate remember token if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $this->setRememberToken($user['id'], $token);
                setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/');
            }

            return $user;
        } catch (Exception $e) {
            error_log("User login error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Login user via remember token
     */
    public function loginByToken($token) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT u.* FROM users u
                JOIN remember_tokens rt ON u.id = rt.user_id
                WHERE rt.token = ? AND rt.expires_at > NOW()"
            );
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Update token expiration
                $this->updateRememberToken($token);
                return $user;
            }
            return false;
        } catch (Exception $e) {
            error_log("Token login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by ID
     */
    public function getById($user_id) {
        $stmt = $this->pdo->prepare(
            "SELECT id, username, email, created_at, last_login 
             FROM users WHERE id = ?"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update user profile
     */
    public function updateProfile($user_id, $data) {
        try {
            $allowed_fields = ['username', 'email', 'bio', 'avatar'];
            $updates = [];
            $params = [];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowed_fields)) {
                    $updates[] = "$key = ?";
                    $params[] = $value;
                }
            }

            if (empty($updates)) {
                throw new Exception("No valid fields to update");
            }

            $params[] = $user_id;

            $stmt = $this->pdo->prepare(
                "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?"
            );
            $stmt->execute($params);

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Change password
     */
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // Verify current password
            $stmt = $this->pdo->prepare(
                "SELECT password FROM users WHERE id = ?"
            );
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );
            $stmt->execute([$hashed_password, $user_id]);

            return true;
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete user account
     */
    public function deleteAccount($user_id, $password) {
        try {
            // Verify password
            $stmt = $this->pdo->prepare(
                "SELECT password FROM users WHERE id = ?"
            );
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception("Password is incorrect");
            }

            // Begin transaction
            $this->pdo->beginTransaction();

            // Delete user data (implement cascade deletes in your database)
            $stmt = $this->pdo->prepare(
                "DELETE FROM users WHERE id = ?"
            );
            $stmt->execute([$user_id]);

            // Delete remember tokens
            $stmt = $this->pdo->prepare(
                "DELETE FROM remember_tokens WHERE user_id = ?"
            );
            $stmt->execute([$user_id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Account deletion error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Set remember token
     */
    private function setRememberToken($user_id, $token) {
        $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO remember_tokens (user_id, token, expires_at)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE token = ?, expires_at = ?"
        );
        $stmt->execute([$user_id, $token, $expires, $token, $expires]);
    }

    /**
     * Update remember token expiration
     */
    private function updateRememberToken($token) {
        $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
        
        $stmt = $this->pdo->prepare(
            "UPDATE remember_tokens SET expires_at = ? WHERE token = ?"
        );
        $stmt->execute([$expires, $token]);
    }

    /**
     * Logout by removing remember token
     */
    public function logout($user_id) {
        $stmt = $this->pdo->prepare(
            "DELETE FROM remember_tokens WHERE user_id = ?"
        );
        $stmt->execute([$user_id]);
        setcookie('remember_token', '', time() - 3600, '/');
    }

    public function updateAvatar($userId, $avatarPath) {
        $stmt = $this->pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?');
        $stmt->execute([$avatarPath, $userId]);
    }
}