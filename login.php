<?php
session_start();
require_once 'php/config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    // Redirect to appropriate dashboard based on user type
    if ($_SESSION['user_type'] === 'helper') {
        header('Location: worker_dashboard.php');
    } else if ($_SESSION['user_type'] === 'client') {
        header('Location: user_dashboard.php');
    } else {
        // If user type is invalid, destroy session and redirect to login
        session_destroy();
        header('Location: login.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Debug: Log login attempt
    error_log("Login attempt for email: " . $email);

    // Validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Debug: Check database connection
            if (!isset($pdo)) {
                throw new Exception("Database connection not established");
            }

            // Prepare and execute the query
            $stmt = $pdo->prepare("SELECT id, fullname, password, user_type FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Debug: Log user data
            error_log("User data found: " . print_r($user, true));

            // Debug: Check if user was found
            if (!$user) {
                $error = 'Invalid email or password.';
                error_log("No user found for email: " . $email);
            } else {
                // Debug: Check password verification
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['user_type'] = $user['user_type'];

                    // Debug: Log session data
                    error_log("Session data after login: " . print_r($_SESSION, true));

                    // Debug: Log redirect decision
                    error_log("Redirecting user type: " . $user['user_type']);

                    // Redirect based on user type
                    if ($user['user_type'] === 'helper') {
                        header('Location: worker_dashboard.php');
                    } else if ($user['user_type'] === 'client') {
                        header('Location: user_dashboard.php');
                    } else {
                        // If user type is invalid, destroy session and show error
                        session_destroy();
                        $error = 'Invalid user type. Please contact support.';
                    }
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                    error_log("Password verification failed for email: " . $email);
                }
            }
        } catch (Exception $e) {
            // Log error and show generic message
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred during login. Please try again later.';
        }
    }
}

// Debug: Log session state at the start of the page
error_log("Session state at page load: " . print_r($_SESSION, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TaskHelper</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-content">
                 <div class="auth-image">
                    <div class="auth-image-content">
                        <h2>Welcome Back!</h2>
                        <p>Log in to access your tasks and connect with the community.</p>
                        <!-- Optional: Add an image or illustration here -->
                         <img src="caracter.png" alt="TaskHelper Character" class="character-image" style="max-width: 100%; height: auto; opacity: 0.9; margin-top: 20px;">
                    </div>
                </div>
                <div class="auth-form-container">
                    <div class="auth-header">
                        <h1>Login to Your Account</h1>
                        <p>Enter your credentials below</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="auth-form" novalidate>
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                   placeholder="Enter your email" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password" 
                                   required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>

                        <div class="auth-footer">
                            <p>Don't have an account? <a href="register.php">Sign Up here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 