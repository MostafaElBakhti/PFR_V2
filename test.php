<?php
// index.php - Main application file

require_once 'php/config.php';


// Start session
session_start();

// Get database instance
$db = Database::getInstance();

// Example: Create users table if it doesn't exist
try {
    $createTable = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $db->query($createTable);
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage();
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                try {
                    $userData = [
                        'username' => $_POST['username'],
                        'email' => $_POST['email'],
                        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
                    ];
                    
                    $userId = $db->insert('users', $userData);
                    $message = "User registered successfully with ID: " . $userId;
                } catch (Exception $e) {
                    $error = "Registration failed: " . $e->getMessage();
                }
                break;
                
            case 'login':
                try {
                    $user = $db->fetchOne(
                        "SELECT * FROM users WHERE username = :username", 
                        ['username' => $_POST['username']]
                    );
                    
                    if ($user && password_verify($_POST['password'], $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $message = "Login successful! Welcome, " . $user['username'];
                    } else {
                        $error = "Invalid username or password";
                    }
                } catch (Exception $e) {
                    $error = "Login failed: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all users for display
try {
    $users = $db->fetchAll("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC");
} catch (Exception $e) {
    $users = [];
    $error = "Failed to fetch users: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #005a87;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .forms-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 600px) {
            .forms-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo APP_NAME; ?></h1>
        <p>Version: <?php echo APP_VERSION; ?></p>
        <?php if (isset($_SESSION['username'])): ?>
            <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
        <?php endif; ?>
    </div>

    <?php if (isset($message)): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="forms-container">
        <div class="container">
            <h2>Register</h2>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="reg_username">Username:</label>
                    <input type="text" id="reg_username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="reg_email">Email:</label>
                    <input type="email" id="reg_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="reg_password">Password:</label>
                    <input type="password" id="reg_password" name="password" required>
                </div>
                
                <button type="submit">Register</button>
            </form>
        </div>

        <div class="container">
            <h2>Login</h2>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="login_username">Username:</label>
                    <input type="text" id="login_username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="login_password">Password:</label>
                    <input type="password" id="login_password" name="password" required>
                </div>
                
                <button type="submit">Login</button>
            </form>
        </div>
    </div>

    <div class="container">
        <h2>Registered Users</h2>
        <?php if (!empty($users)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No users registered yet.</p>
        <?php endif; ?>
    </div>

    <div class="container">
        <h3>Configuration Info</h3>
        <p><strong>Database Host:</strong> <?php echo DB_HOST; ?></p>
        <p><strong>Database Name:</strong> <?php echo DB_NAME; ?></p>
        <p><strong>Debug Mode:</strong> <?php echo APP_DEBUG ? 'Enabled' : 'Disabled'; ?></p>
        <p><strong>Upload Path:</strong> <?php echo UPLOAD_PATH; ?></p>
    </div>
</body>
</html>