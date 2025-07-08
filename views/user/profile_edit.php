<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="form-outer-container" style="max-width:480px; margin:3rem auto;">
  <div class="card" style="padding:2.5rem 2rem;">
    <h2 style="margin-bottom:1.5em; color:#4254c5; text-align:center;">Edit Profile</h2>
    <?php include __DIR__ . '/../partials/messages.php'; ?>
    <form method="POST" action="index.php?page=profile&action=update" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
      <div class="form-group" style="text-align:center; margin-bottom:1.5em;">
        <label for="avatar" style="display:block; font-weight:500; margin-bottom:0.5em;">Profile Picture</label>
        <div style="display:flex; flex-direction:column; align-items:center; gap:0.7em;">
          <img id="avatarPreview" src="<?= !empty($user['avatar']) ? 'assets/uploads/avatars/' . htmlspecialchars($user['avatar'], ENT_QUOTES) : 'assets/images/default-avatar.png' ?>" alt="Avatar Preview" style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:2px solid #5d78ff; box-shadow:0 2px 8px rgba(93,120,255,0.10);">
          <input type="file" id="avatar" name="avatar" accept="image/*" style="margin-top:0.5em;">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:1.2em;">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES) ?>" required>
      </div>
      <div class="form-group" style="margin-bottom:1.2em;">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES) ?>" required>
      </div>
      <div class="form-group" style="margin-bottom:1.2em;">
        <label for="bio">Bio</label>
        <textarea id="bio" name="bio" class="form-control" rows="4" style="resize:vertical;"><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES) ?></textarea>
        <small class="form-text text-muted">Tell us about yourself</small>
      </div>
      <div class="form-group" style="text-align:center; margin-top:2em;">
        <button type="submit" class="btn">Save Changes</button>
        <a href="index.php?page=profile" class="btn btn-link">Cancel</a>
      </div>
    </form>
  </div>
</div>
<script>
document.getElementById('avatar').addEventListener('change', function(e) {
  const preview = document.getElementById('avatarPreview');
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(event) {
      preview.src = event.target.result;
    };
    reader.readAsDataURL(file);
  }
});
</script>

