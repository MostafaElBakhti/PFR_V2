<?php
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <a href="index.php"><h2><i class="fas fa-tasks"></i> TaskHelper</h2></a>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Home</a>
            <a href="index.php#how-it-works" class="nav-link">How it Works</a>
            <a href="browse-tasks.php" class="nav-link <?php echo $current_page === 'browse-tasks.php' ? 'active' : ''; ?>">Browse Tasks</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="php/logout.php" class="nav-link">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>">Login</a>
                <a href="register.php" class="nav-link btn-primary <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav> 