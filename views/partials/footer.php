<style>
@media (min-width: 901px) {
  .main-footer {
    margin-left: 250px;
  }
}
@media (max-width: 900px) {
  .main-footer {
    margin-left: 70px;
  }
}
</style>
    <!-- Footer Section -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4>My Journal</h4>
                <p>A personal journaling system for self-reflection and growth.</p>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php?page=dashboard">Dashboard</a></li>
                    <li><a href="index.php?page=journal&action=list">Journal Entries</a></li>
                    <li><a href="index.php?page=journal&action=create">New Entry</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Resources</h4>
                <ul>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Help Center</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> My Journal. All rights reserved.</p>
            <div class="social-links">
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Main JS -->
    <script src="assets/js/main.js"></script>
    
    <!-- Page-specific JS -->
    <?php if (isset($page_js)): ?>
        <script src="assets/js/<?php echo htmlspecialchars($page_js); ?>"></script>
    <?php endif; ?>
    
    </body>
</html>