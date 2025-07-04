<?php include __DIR__ . '/../partials/header.php'; ?>
<div class="form-outer-container">
  <div class="card" style="padding:2rem; background:#fff; max-width:500px; margin:2rem auto;">
    <h2>Delete Journal Entry</h2>
    <p>Are you sure you want to delete this journal entry? This action cannot be undone.</p>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES) ?>">
      <button type="submit" class="btn btn-danger">Yes, Delete</button>
      <a href="index.php?page=journal&action=list" class="btn btn-link">Cancel</a>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?> 