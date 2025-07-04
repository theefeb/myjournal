<?php require_once __DIR__ . '/../../views/partials/header.php'; ?>
<div class="form-outer-container">
  <div class="auth-container">
    <div class="auth-card">
      <h1>Forgot Password</h1>
      <p>Enter your email to receive a password reset link.</p>
      <?php if (isset($_SESSION['message'])): ?>
        <div class="system-message error">
          <p><?php echo $_SESSION['message']; ?></p>
          <button class="close-message">&times;</button>
        </div>
        <?php unset($_SESSION['message']); ?>
      <?php endif; ?>
      <form method="POST" autocomplete="off">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required aria-label="Email Address">
        </div>
        <button type="submit" class="btn">Send Reset Link</button>
        <a href="index.php?page=login" class="btn btn-link">Back to Login</a>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?> 