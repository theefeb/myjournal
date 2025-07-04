<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Journal - <?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;500;700&display=swap" rel="stylesheet">
    
    
    <!-- CSS -->
    <link rel="stylesheet" href="/journalms/assets/style.css">
    <link rel="stylesheet" href="/journalms/assets/dashboard.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- Mobile Specific -->
    <meta name="theme-color" content="#5d78ff">
    
    <!-- Bootstrap JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-qQ2iX+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <meta name="description" content="My Journal is a personal journaling system for self-reflection and growth.">
    <meta name="keywords" content="journal, diary, mood tracker, prompts, self-reflection, mental health">
    <meta property="og:title" content="My Journal">
    <meta property="og:description" content="A personal journaling system for self-reflection and growth.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://localhost/journalms/">
    <meta property="og:image" content="/journalms/assets/images/favicon.png">
</head>
<body>
    <header class="main-header" role="banner" aria-label="Main site header">
      <div class="header-content">
        <a href="index.php?page=dashboard" class="logo" aria-label="MyJournal Home">MyJournal</a>
        <nav class="main-nav" role="navigation" aria-label="Main navigation">
          <a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a>
          <a href="index.php?page=journal&action=list"><i class="fas fa-book"></i> Journal</a>
          <a href="index.php?page=journal&action=create"><i class="fas fa-plus"></i> New Entry</a>
          <a href="#mood-tracker"><i class="fas fa-chart-line"></i> Mood</a>
          <a href="includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
      </div>
    </header>

    <!-- System Messages (for alerts/notifications) -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="system-message <?php echo $_SESSION['message_type']; ?>">
            <p><?php echo $_SESSION['message']; ?></p>
            <button class="close-message">&times;</button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
</body>
</html>