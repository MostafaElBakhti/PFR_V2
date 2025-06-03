<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3><i class="fas fa-tasks"></i> TaskHelper</h3>
                <p>Connecting people who need help with those who can provide it.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="browse-tasks.php">Browse Tasks</a></li>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li><a href="register.php">Sign Up</a></li>
                        <li><a href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <ul>
                    <li><a href="help.php">Help Center</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="terms.php">Terms of Service</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> TaskHelper. All rights reserved.</p>
        </div>
    </div>
</footer> 