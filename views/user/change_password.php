<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="form-outer-container" style="max-width:480px; margin:3rem auto;">
  <div class="card" style="padding:2.5rem 2rem;">
    <h2 style="margin-bottom:1.5em; color:#4254c5; text-align:center;">Change Password</h2>
    <?php include __DIR__ . '/../partials/messages.php'; ?>
    <form method="POST" action="index.php?page=profile&action=change_password">
      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
      <div class="form-group" style="margin-bottom:1.2em;">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" class="form-control" required>
      </div>
      <div class="form-group" style="margin-bottom:1.2em;">
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
        <small class="form-text text-muted">Minimum 8 characters with at least one number and one special character</small>
        <div class="password-strength mt-2">
          <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
          </div>
          <small class="text-muted">Password strength</small>
        </div>
      </div>
      <div class="form-group" style="margin-bottom:1.2em;">
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        <div class="invalid-feedback" id="password-match-feedback" style="display:none; color:#e74c3c;">Passwords do not match</div>
      </div>
      <div class="form-group" style="text-align:center; margin-top:2em;">
        <button type="submit" class="btn">Change Password</button>
        <a href="index.php?page=profile" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>
<script>
// Password strength indicator
document.getElementById('new_password').addEventListener('input', function() {
  const password = this.value;
  const strength = calculatePasswordStrength(password);
  const progressBar = document.querySelector('.progress-bar');
  progressBar.style.width = strength.percentage + '%';
  progressBar.className = 'progress-bar ' + strength.class;
});
// Password match validation
document.getElementById('confirm_password').addEventListener('input', function() {
  const newPassword = document.getElementById('new_password').value;
  const confirmPassword = this.value;
  const feedback = document.getElementById('password-match-feedback');
  if (newPassword && confirmPassword && newPassword !== confirmPassword) {
    this.classList.add('is-invalid');
    feedback.style.display = 'block';
  } else {
    this.classList.remove('is-invalid');
    feedback.style.display = 'none';
  }
});
function calculatePasswordStrength(password) {
  let score = 0;
  if (!password) return { percentage: 0, class: 'bg-danger' };
  score += Math.min(5, Math.floor(password.length / 2));
  if (/\d/.test(password)) score += 1;
  if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score += 2;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 2;
  const percentage = Math.min(100, score * 10);
  let strengthClass = 'bg-danger';
  if (percentage > 60) strengthClass = 'bg-warning';
  if (percentage > 80) strengthClass = 'bg-success';
  return { percentage, class: strengthClass };
}
</script>

