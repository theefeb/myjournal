<?php
// Redirect logged-in users to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Set page title
$page_title = "Login";

// Include header
require_once __DIR__ . '/../partials/header.php';

?>

<div class="form-outer-container">
    <div class="auth-container">
        <div class="auth-card">
            <h1>Welcome Back</h1>
            <p>Log in to continue your journaling journey</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="system-message error">
                    <p><?php echo $_SESSION['message']; ?></p>
                    <button class="close-message">&times;</button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <form action="includes/login_process.php" method="POST" class="auth-form" aria-label="Login form" autocomplete="username" role="form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-icon-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <small class="forgot-password">
                        <a href="index.php?page=forgot_password">Forgot password?</a>
                    </small>
                </div>
                <div class="form-group remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <button type="submit" class="btn">Log In</button>
                <div class="auth-footer">
                    Don't have an account? <a href="index.php?page=register">Sign up</a>
                </div>
            </form>
        </div>
        <div class="auth-image">
            <img src="assets/images/journal-login.jpg" alt="Journal writing">
        </div>
    </div>
</div>


