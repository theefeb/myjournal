<?php
// Redirect logged-in users to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php?page=dashboard");
    exit;
}

// Set page title
$page_title = "Register";

// Include header
require_once __DIR__ . '/../../views/partials/header.php';
?>

<div class="form-outer-container">
    <div class="auth-container">
        <div class="auth-card">
            <h1>Create Account</h1>
            <p>Start your personal journaling journey today</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="system-message error">
                    <p><?php echo $_SESSION['message']; ?></p>
                    <button class="close-message">&times;</button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <form action="includes/register_process.php" method="POST" class="auth-form" aria-label="Register form" autocomplete="on" role="form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-icon-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-icon-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <small class="password-hint">
                        At least 8 characters with 1 number and 1 special character
                    </small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-icon-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" class="btn">Create Account</button>
                <div class="auth-footer">
                    Already have an account? <a href="index.php?page=login">Log in</a>
                </div>
            </form>
        </div>
        <div class="auth-image">
            <img src="assets/images/journal-register.jpg" alt="Journal writing">
        </div>
    </div>
</div>

<?php
// Include footer
require_once __DIR__ . '/../../views/partials/footer.php';
?>