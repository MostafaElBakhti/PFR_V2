<?php
session_start();
require_once 'php/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskHelper - Find Help for Any Task</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><i class="fas fa-tasks"></i> TaskHelper</h2>
            </div>
            <div class="nav-menu">
                <a href="#home" class="nav-link">Home</a>
                <a href="#how-it-works" class="nav-link">How it Works</a>
                <a href="browse-tasks.php" class="nav-link">Browse Tasks</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="php/logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="nav-link btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <section id="home" class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Get help with any task in your daily life</h1>
                <p>
                    Post your task and connect with skilled helpers in your area. From
                    home repairs to personal assistance - find help for anything!
                </p>
                <div class="hero-buttons">
                    <?php if (isLoggedIn()): ?>
                        <a href="post-task.php" class="btn btn-primary">Post a Task</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Post a Task</a>
                    <?php endif; ?>
                    <a href="browse-tasks.php" class="btn btn-secondary">Find Work</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="hero.png" alt="People helping with various tasks">
            </div>
        </div>
        <div class="hero-benefits">
            <div class="benefit-item">
                <span>Save Time</span>
            </div>
            <div class="benefit-item">
                <span>Reduce Effort</span>
            </div>
            <div class="benefit-item">
                <span>Get Things Done</span>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="header">
                <h1>How it works</h1>
                <p>
                    Get help with your tasks in three simple steps. Whether you need help with home repairs, 
                    moving, or any other task, we make it easy to connect with reliable helpers.
                </p>
            </div>

            <div class="steps">
                <!-- Step 1 -->
                <div class="step">
                    <div class="step-number">01</div>
                    <div class="step-visual">
                        <div class="account-form">
                            <h4>Create Account</h4>
                            <div class="form-field"></div>
                            <div class="form-field"></div>
                            <div class="form-field"></div>
                            <button class="signup-btn">Sign up</button>
                        </div>
                    </div>
                    <div class="step-content">
                        <h3>Create Your Account</h3>
                        <p>
                            Sign up in minutes with your email or social media account. Choose whether you want to 
                            post tasks or offer your services as a helper. Your profile helps build trust in our community.
                        </p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step">
                    <div class="step-number">02</div>
                    <div class="step-visual">
                        <div class="search-container">
                            <div class="search-box">
                                <div class="search-input"></div>
                                <div class="search-icon"></div>
                            </div>
                        </div>
                    </div>
                    <div class="step-content">
                        <h3>Post or Find Tasks</h3>
                        <p>
                            Need help? Post your task with details and your budget. Looking for work? Browse available 
                            tasks in your area and apply to help. Our smart matching system connects you with the right people.
                        </p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step">
                    <div class="step-number">03</div>
                    <div class="step-visual">
                        <div class="emoji-container">
                            <div class="emoji">😊</div>
                            <div class="emoji">😟</div>
                            <div class="emoji">❤️</div>
                        </div>
                    </div>
                    <div class="step-content">
                        <h3>Get Things Done</h3>
                        <p>
                            Connect with your chosen helper, agree on details, and get your task completed. 
                            Our secure payment system and rating system ensure a smooth experience for everyone.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="features-header">
                <span class="features-subtitle">Why Choose Us</span>
                <h2 class="section-title">Experience the Best Service</h2>
                <div class="features-divider"></div>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Safe & Secure</h3>
                        <p>
                            Verified profiles and secure payment system to ensure your peace
                            of mind
                        </p>
                        <a href="#" class="feature-link">Learn more <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="feature-bg"></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Quick Response</h3>
                        <p>Get applications within hours of posting your task</p>
                        <a href="#" class="feature-link">Learn more <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="feature-bg"></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Quality Helpers</h3>
                        <p>Access our community of highly rated and reviewed helpers</p>
                        <a href="#" class="feature-link">Learn more <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="feature-bg"></div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Easy to Use</h3>
                        <p>
                            Simple and intuitive interface for posting and managing tasks
                        </p>
                        <a href="#" class="feature-link">Learn more <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="feature-bg"></div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-tasks"></i> TaskHelper</h3>
                    <p>
                        Connecting people who need help with those who can provide it.
                    </p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="browse-tasks.php">Browse Tasks</a></li>
                        <?php if (!isLoggedIn()): ?>
                            <li><a href="register.php">Sign Up</a></li>
                            <li><a href="login.php">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> TaskHelper. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html> 