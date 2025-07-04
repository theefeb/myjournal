<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h2>Delete Account</h2>
                </div>
                <div class="card-body">
                    <?php include __DIR__ . '/../partials/messages.php'; ?>
                    
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">Warning!</h5>
                        <p>This action cannot be undone. All your data including:</p>
                        <ul>
                            <li>Journal entries</li>
                            <li>Mood tracking data</li>
                            <li>Account information</li>
                        </ul>
                        <p>will be permanently deleted.</p>
                    </div>
                    
                    <form method="POST" action="index.php?page=profile&action=delete">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="form-group mb-4">
                            <label for="confirm_password">Enter your password to confirm</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" required>
                        </div>
                        
                        <div class="form-group mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirm_delete" required>
                                <label class="form-check-label" for="confirm_delete">
                                    I understand this action is irreversible
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group d-flex justify-content-between">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt"></i> Permanently Delete Account
                            </button>
                            <a href="index.php?page=profile" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>