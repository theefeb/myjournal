<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="form-outer-container" style="max-width:900px; margin:3rem auto;">
  <div style="display:flex; flex-wrap:wrap; gap:2.5rem; align-items:flex-start;">
    <!-- Sidebar/Profile Card -->
    <div style="flex:1 1 260px; max-width:320px; min-width:220px;">
      <div class="card" style="padding:2rem 1.5rem; text-align:center; background:#fff;">
                    <img src="<?= htmlspecialchars($user['avatar'] ?? 'assets/images/default-avatar.png', ENT_QUOTES) ?>" 
             alt="Profile Picture" class="avatar" style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #5d78ff; box-shadow:0 2px 8px rgba(93,120,255,0.10); margin-bottom:1em;">
        <h2 style="margin:0; color:#4254c5; font-size:1.3em;"><?= htmlspecialchars($user['username'], ENT_QUOTES) ?></h2>
        <p style="color:#888; font-size:0.97em; margin-bottom:0.7em;">Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
        <div style="display:flex; flex-direction:column; gap:0.5em; margin-top:1.2em;">
          <a href="index.php?page=profile" class="btn btn-link" style="text-align:left;"><i class="fas fa-user"></i> Profile Overview</a>
          <a href="index.php?page=profile&action=update" class="btn btn-link" style="text-align:left;"><i class="fas fa-edit"></i> Edit Profile</a>
          <a href="index.php?page=profile&action=change_password" class="btn btn-link" style="text-align:left;"><i class="fas fa-lock"></i> Change Password</a>
          <a href="index.php?page=profile&action=delete" class="btn btn-link text-danger" style="text-align:left;"><i class="fas fa-trash-alt"></i> Delete Account</a>
                </div>
            </div>
        </div>
    <!-- Main Profile Content -->
    <div style="flex:2 1 340px; min-width:260px;">
            <div class="profile-content">
        <h2 style="margin-bottom:1.2em;">Profile Overview</h2>
                <?php include __DIR__ . '/../partials/messages.php'; ?>
        <div class="card" style="margin-bottom:2rem;">
          <div class="card-header" style="background:none; border-bottom:none; padding-bottom:0;">
            <h5 style="margin:0; color:#5d78ff;">Personal Information</h5>
                    </div>
          <div class="card-body" style="padding-top:0.7em;">
            <div style="display:flex; flex-wrap:wrap; gap:2em;">
              <div style="flex:1 1 180px;">
                                <p><strong>Username:</strong> <?= htmlspecialchars($user['username'], ENT_QUOTES) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($user['email'], ENT_QUOTES) ?></p>
                            </div>
              <div style="flex:1 1 180px;">
                                <?php if (!empty($user['bio'])): ?>
                                    <p><strong>Bio:</strong></p>
                                    <p><?= nl2br(htmlspecialchars($user['bio'], ENT_QUOTES)) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
          <div class="card-header" style="background:none; border-bottom:none; padding-bottom:0;">
            <h5 style="margin:0; color:#5d78ff;">Activity Summary</h5>
                    </div>
          <div class="card-body" style="padding-top:0.7em;">
            <div style="display:flex; gap:1.5em; flex-wrap:wrap; justify-content:center;">
              <div class="stat-card" style="background:#e0e7ff; border-radius:10px; padding:1em 2em; text-align:center; min-width:120px;">
                <h3 style="margin:0; color:#4254c5; font-size:1.5em;"><?= $journalStats['entry_count'] ?? 0 ?></h3>
                <p class="text-muted" style="color:#888;">Journal Entries</p>
                                </div>
              <div class="stat-card" style="background:#e0e7ff; border-radius:10px; padding:1em 2em; text-align:center; min-width:120px;">
                <h3 style="margin:0; color:#4254c5; font-size:1.5em;"><?= $moodStats['current_streak'] ?? 0 ?></h3>
                <p class="text-muted" style="color:#888;">Day Streak</p>
                            </div>
              <div class="stat-card" style="background:#e0e7ff; border-radius:10px; padding:1em 2em; text-align:center; min-width:120px;">
                <h3 style="margin:0; color:#4254c5; font-size:1.5em;"><?= number_format($moodStats['avg_mood'] ?? 0, 1) ?>/5</h3>
                <p class="text-muted" style="color:#888;">Avg Mood</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

