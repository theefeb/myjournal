<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="form-outer-container" style="max-width:480px; margin:3rem auto;">
  <div class="card" style="padding:2.5rem 2rem; border:2px solid #e74c3c;">
    <h2 style="margin-bottom:1.2em; color:#e74c3c; text-align:center;">Delete Account</h2>
    <?php include __DIR__ . '/../partials/messages.php'; ?>
    <div class="system-message error" style="margin-bottom:1.5em;">
      <div>
        <strong>Warning!</strong> This action cannot be undone. All your data including journal entries, mood tracking, and account info will be <b>permanently deleted</b>.
      </div>
    </div>
    <form method="POST" action="index.php?page=profile&action=delete">
      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
      <div class="form-group" style="margin-bottom:1.2em;">
        <label for="confirm_password">Enter your password to confirm</label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
      </div>
      <div class="form-group" style="margin-bottom:1.2em;">
        <input type="checkbox" id="confirm_delete" required>
        <label for="confirm_delete">I understand this action is irreversible</label>
      </div>
      <div class="form-group" style="text-align:center; margin-top:2em;">
        <button type="submit" class="btn" style="background:#e74c3c; color:#fff;">Permanently Delete Account</button>
        <a href="index.php?page=profile" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>

