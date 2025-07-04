<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2>Edit Profile</h2>
                </div>
                <div class="card-body">
                    <?php include __DIR__ . '/../partials/messages.php'; ?>
                    
                    <form method="POST" action="index.php?page=profile&action=update" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="form-group">
                            <label for="avatar">Profile Picture</label>
                            <input type="file" id="avatar" name="avatar" accept="image/*">
                            <?php if (!empty($user['avatar'])): ?>
                                <div style="margin-top:0.5em;"><img src="<?= htmlspecialchars($user['avatar'], ENT_QUOTES) ?>" alt="Current Avatar" style="width:70px; height:70px; border-radius:50%; object-fit:cover; border:2px solid #5d78ff;"></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group row mb-4">
                            <label for="username" class="col-md-4 col-form-label">Username</label>
                            <div class="col-md-8">
                                <input type="text" id="username" name="username" class="form-control" 
                                       value="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group row mb-4">
                            <label for="email" class="col-md-4 col-form-label">Email Address</label>
                            <div class="col-md-8">
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group row mb-4">
                            <label for="bio" class="col-md-4 col-form-label">Bio</label>
                            <div class="col-md-8">
                                <textarea id="bio" name="bio" class="form-control" rows="4"><?= 
                                    htmlspecialchars($user['bio'] ?? '', ENT_QUOTES) 
                                ?></textarea>
                                <small class="form-text text-muted">Tell us about yourself</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="index.php?page=profile" class="btn btn-link">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview avatar before upload
document.getElementById('avatar').addEventListener('change', function(e) {
    const reader = new FileReader();
    reader.onload = function(event) {
        document.getElementById('avatarPreview').src = event.target.result;
    };
    reader.readAsDataURL(e.target.files[0]);
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>