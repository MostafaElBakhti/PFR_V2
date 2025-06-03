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
                        <h2><span class="highlight">C</span>onnect. <span class="highlight">C</span>ollaborate. <span class="highlight">C</span>omplete.</h2>
                        <p>Find the help you need or offer your skills to others in your community.</p>
                        <img src="caracter.png" alt="TaskHelper Character" class="character-image" style="max-width: 100%; height: auto; opacity: 0.9; margin-top: 20px;">
                    </div>
                </div>
                <div class="auth-form-container">
                    <div class="auth-header">
                        <h1>Create Your Account</h1>
                        <p>Join our community of helpers and clients</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <p><?php echo htmlspecialchars($success); ?></p>
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
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Confirm your password" 
                                   required>
                        </div>

                        <div class="form-group terms">
                            <label class="checkbox-container">
                                <input type="checkbox" name="terms" required>
                                <span class="checkmark"></span>
                                I agree to the <a href="privacy.php">Privacy Policy</a>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>

                        <div class="login-option mt-3">
                            <p class="text-muted mb-1">Already registered?</p>
                            <a href="login.php" class="btn btn-link">
                                <i class="fas fa-sign-in-alt"></i> Log In
                            </a>
                        </div>

                        <div class="auth-footer">
                            <p>Already have an account?</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <style>
    .user-type-switch {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        width: 100%;
    }

    .switch-btn {
        flex: 1;
        padding: 1rem;
        border: 2px solid var(--primary-color);
        background: white;
        color: var(--primary-color);
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .switch-btn i {
        font-size: 1.2rem;
    }

    .switch-btn.active {
        background: var(--primary-color);
        color: white;
    }

    .switch-btn:hover {
        background: var(--primary-color);
        color: white;
    }

    .login-option {
        margin: 1rem 0;
        text-align: center;
    }

    .login-option .text-muted {
        color: var(--text-muted);
        margin-bottom: 0.5rem;
    }

    .login-option .btn-link {
        padding: 0.5rem 1rem;
        color: var(--primary-color);
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .login-option .btn-link:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }

    .login-option .btn-link i {
        font-size: 1rem;
        margin-right: 0.5rem;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
    });
    </script>
</body>
</html> 