<?php if (isset($_SESSION['message'])): ?>
    <div class="system-message <?php echo $_SESSION['message_type']; ?>">
        <p><?php echo $_SESSION['message']; ?></p>
        <button class="close-message">&times;</button>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?> 