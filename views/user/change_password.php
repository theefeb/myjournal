<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Change Password</h2>
                </div>
                <div class="card-body">
                    <?php include __DIR__ . '/../partials/messages.php'; ?>
                    
                    <form method="POST" action="index.php?page=profile&action=change_password">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="form-group mb-4">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-control" required>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-control" required minlength="8">
                            <small class="form-text text-muted">
                                Minimum 8 characters with at least one number and one special character
                            </small>
                            <div class="password-strength mt-2">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted">Password strength</small>
                            </div>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" required>
                            <div class="invalid-feedback" id="password-match-feedback">
                                Passwords do not match
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                            <a href="index.php?page=profile" class="btn btn-link">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
    
    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
});

function calculatePasswordStrength(password) {
    let score = 0;
    if (!password) return { percentage: 0, class: 'bg-danger' };
    
    // Length
    score += Math.min(5, Math.floor(password.length / 2));
    
    // Contains numbers
    if (/\d/.test(password)) score += 1;
    
    // Contains special chars
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score += 2;
    
    // Contains both lower and upper case
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 2;
    
    const percentage = Math.min(100, score * 10);
    let strengthClass = 'bg-danger';
    if (percentage > 60) strengthClass = 'bg-warning';
    if (percentage > 80) strengthClass = 'bg-success';
    
    return { percentage, class: strengthClass };
}
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>