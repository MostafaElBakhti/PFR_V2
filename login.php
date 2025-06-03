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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #014678;
            --secondary-color: #0277bd;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --danger-color: #EF4444;
            --light-bg: #f8f9fa;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .auth-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-box {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1000px;
            overflow: hidden;
        }

        .auth-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }

        .auth-image {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color), var(--primary-color));
            background-size: 200% 200%;
            animation: gradientMove 2s ease infinite;
            padding: 3rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .auth-image-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .auth-image h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .auth-image p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .character-image {
            max-width: 80%;
            height: auto;
            margin-top: 2rem;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        .auth-form-container {
            padding: 3rem;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #6B7280;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4B5563;
            font-weight: 500;
        }

        .form-group label i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(1, 70, 120, 0.1);
            outline: none;
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color), var(--primary-color));
            background-size: 200% 200%;
            animation: gradientMove 2s ease infinite;
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            animation: gradientMove 1s ease infinite;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(1, 70, 120, 0.2);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(1, 70, 120, 0.2);
        }

        .btn-primary i {
            font-size: 1.1rem;
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #E5E7EB;
        }

        .auth-footer p {
            color: #6B7280;
            margin-bottom: 0.5rem;
        }

        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: #DC2626;
        }

        @media (max-width: 768px) {
            .auth-content {
                grid-template-columns: 1fr;
            }

            .auth-image {
                display: none;
            }

            .auth-form-container {
                padding: 2rem;
            }
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color), var(--primary-color));
            background-size: 200% 200%;
            animation: gradientMove 2s ease infinite;
            color: #fff;
            padding: 80px 0 40px;
            position: relative;
            overflow: hidden;
            margin-top: auto;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .footer .container {
            position: relative;
            z-index: 1;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section {
            padding: 0 20px;
        }

        .footer-section h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-section h3 i {
            color: #fff;
        }

        .footer-section h4 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.2rem;
            color: #fff;
        }

        .footer-section p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.8rem;
        }

        .footer-section ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-section ul li a:hover {
            color: #fff;
            transform: translateX(5px);
        }

        .footer-section ul li a i {
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }

        .footer-section ul li a:hover i {
            transform: translateX(3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .footer {
                padding: 60px 0 30px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 30px;
            }
            
            .footer-section {
                padding: 0;
            }
            
            .footer-section h3,
            .footer-section h4 {
                justify-content: center;
            }
            
            .footer-section ul li a {
                justify-content: center;
            }
        }
    </style>
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
                        <img src="caracter.png" alt="TaskHelper Character" class="character-image">
                    </div>
                </div>
                <div class="auth-form-container">
                    <div class="auth-header">
                        <h1>Login to Your Account</h1>
                        <p>Enter your credentials below</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="auth-form" novalidate>
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" 
                                   class="form-control"
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
                                   class="form-control"
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password" 
                                   required>
                        </div>

                        <button type="submit" class="btn btn-primary">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 