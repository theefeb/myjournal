<?php require_once __DIR__ . '/../../views/partials/header.php'; ?>
<div class="form-outer-container">
  <div class="auth-container">
    <div class="auth-card">
      <h1>Reset Password</h1>
      <p>Enter your new password below.</p>
      <?php if (isset($_SESSION['message'])): ?>
        <div class="system-message error">
          <p><?php echo $_SESSION['message']; ?></p>
          <button class="close-message">&times;</button>
        </div>
        <?php unset($_SESSION['message']); ?>
      <?php endif; ?>
      <form method="POST" autocomplete="off">
        <div class="form-group">
          <label for="password">New Password</label>
          <input type="password" id="password" name="password" required aria-label="New Password">
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required aria-label="Confirm Password">
        </div>
        <button type="submit" class="btn">Reset Password</button>
        <a href="index.php?page=login" class="btn btn-link">Back to Login</a>
      </form>
    </div>
  </div>
</div>
