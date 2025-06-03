<?php
session_start();
require_once 'php/config.php';

// Initialize variables
$error = '';
$success = '';
$formData = [
    'fullname' => '',
    'email' => '',
    'user_type' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData = [
        'fullname' => filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'user_type' => filter_input(INPUT_POST, 'user_type', FILTER_SANITIZE_STRING),
        'terms' => isset($_POST['terms'])
    ];

    // Validate input
    $validationErrors = validateRegistrationForm($formData);
    
    if (empty($validationErrors)) {
        try {
            // Check if email exists
            if (emailExists($pdo, $formData['email'])) {
                $error = 'This email is already registered. <a href="login.php" class="login-link">Click here to login</a>';
            } else {
                // Create user account
                if (createUser($pdo, $formData)) {
                    $success = 'Registration successful! You can now login.';
                    // Clear form data after successful registration
                    $formData = [
                        'fullname' => '',
                        'email' => '',
                        'user_type' => ''
                    ];
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    } else {
        $error = $validationErrors[0];
    }
}

/**
 * Validate registration form data
 * @param array $data Form data
 * @return array Array of validation errors
 */
function validateRegistrationForm($data) {
    $errors = [];
    
    if (empty($data['fullname']) || empty($data['email']) || 
        empty($data['password']) || empty($data['confirm_password']) || 
        empty($data['user_type'])) {
        $errors[] = 'All fields are required';
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (strlen($data['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!$data['terms']) {
        $errors[] = 'You must agree to the terms and conditions';
    }
    
    return $errors;
}

/**
 * Check if email already exists in database
 * @param PDO $pdo Database connection
 * @param string $email Email to check
 * @return bool True if email exists
 */
function emailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

/**
 * Create new user account
 * @param PDO $pdo Database connection
 * @param array $data User data
 * @return bool True if user created successfully
 */
function createUser($pdo, $data) {
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (fullname, email, password, user_type) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([
        $data['fullname'],
        $data['email'],
        $hashedPassword,
        $data['user_type']
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - TaskHelper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .highlight {
            color: #FCD34D;
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

        .user-type-switch {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .switch-btn {
            padding: 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            background: white;
            color: #4B5563;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .switch-btn i {
            font-size: 1.2rem;
        }

        .switch-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .switch-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
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

        .password-hint {
            display: block;
            margin-top: 0.5rem;
            color: #6B7280;
            font-size: 0.875rem;
        }

        .terms {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: #4B5563;
        }

        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border: 2px solid #E5E7EB;
            border-radius: 4px;
            cursor: pointer;
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

        .login-option {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #E5E7EB;
        }

        .login-option p {
            color: #6B7280;
            margin-bottom: 0.5rem;
        }

        .btn-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-link:hover {
            color: #2563EB;
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

        .alert-success {
            background: #D1FAE5;
            border: 1px solid #A7F3D0;
            color: #059669;
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
                        <h2><span class="highlight">C</span>onnect. <span class="highlight">C</span>ollaborate. <span class="highlight">C</span>omplete.</h2>
                        <p>Find the help you need or offer your skills to others in your community.</p>
                        <img src="caracter.png" alt="TaskHelper Character" class="character-image">
                    </div>
                </div>
                <div class="auth-form-container">
                    <div class="auth-header">
                        <h1>Create Your Account</h1>
                        <p>Join our community of helpers and clients</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="auth-form" novalidate>
                        <div class="user-type-switch">
                            <button type="button" class="switch-btn active" data-type="client">
                                <i class="fas fa-user"></i> I need help
                            </button>
                            <button type="button" class="switch-btn" data-type="helper">
                                <i class="fas fa-hard-hat"></i> I want to work
                            </button>
                            <input type="hidden" name="user_type" id="user_type_input" value="<?php echo htmlspecialchars($formData['user_type'] ?: 'client'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="fullname">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" 
                                   class="form-control"
                                   id="fullname" 
                                   name="fullname" 
                                   value="<?php echo htmlspecialchars($formData['fullname']); ?>" 
                                   placeholder="Enter your full name" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" 
                                   class="form-control"
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($formData['email']); ?>" 
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
                                   placeholder="Create a password" 
                                   required 
                                   minlength="8">
                            <small class="password-hint">Must be at least 8 characters long</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <input type="password" 
                                   class="form-control"
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Confirm your password" 
                                   required>
                        </div>

                        <div class="form-group terms">
                            <label class="checkbox-container">
                                <input type="checkbox" name="terms" required>
                                I agree to the <a href="privacy.php">Privacy Policy</a>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>

                        <div class="login-option">
                            <p>Already have an account?</p>
                            <a href="login.php" class="btn-link">
                                <i class="fas fa-sign-in-alt"></i> Log In
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // User type switch functionality
            const switchBtns = document.querySelectorAll('.switch-btn');
            const userTypeInput = document.getElementById('user_type_input');

            switchBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    switchBtns.forEach(b => b.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Update hidden input value
                    userTypeInput.value = this.dataset.type;
                });
            });

            // Form validation
            const form = document.querySelector('.auth-form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            form.addEventListener('submit', function(e) {
                let isValid = true;
                let errorMessage = '';

                // Check if passwords match
                if (password.value !== confirmPassword.value) {
                    isValid = false;
                    errorMessage = 'Passwords do not match';
                }

                // Check password length
                if (password.value.length < 8) {
                    isValid = false;
                    errorMessage = 'Password must be at least 8 characters long';
                }

                if (!isValid) {
                    e.preventDefault();
                    alert(errorMessage);
                }
            });
        });
    </script>
</body>
</html> 