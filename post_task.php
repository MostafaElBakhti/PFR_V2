<?php
require_once 'php/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

// Check if user is a client
if ($_SESSION['user_type'] !== 'client') {
    session_destroy();
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = floatval($_POST['budget'] ?? 0);
    $deadline = $_POST['deadline'] ?? '';
    $category = $_POST['category'] ?? '';

    $errors = [];

    // Validate inputs
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    if ($budget <= 0) {
        $errors[] = "Please enter a valid budget";
    }
    if (empty($deadline)) {
        $errors[] = "Deadline is required";
    }
    if (empty($category)) {
        $errors[] = "Category is required";
    }

    // If no errors, insert the task
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (client_id, title, description, budget, deadline, category, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())
            ");
            
            $stmt->execute([$userId, $title, $description, $budget, $deadline, $category]);
            
            // Redirect to dashboard after successful creation
            header('Location: user_dashboard.php?success=1');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Error creating task. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Task - TaskHelper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3B82F6;
            --secondary-color: #6366F1;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --danger-color: #EF4444;
            --light-bg: #F3F4F6;
            --card-border-radius: 12px;
        }

        body {
            background-color: var(--light-bg);
            font-family: system-ui, -apple-system, sans-serif;
            padding-top: 2rem;
        }

        .card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Post a New Task</h3>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="post_task.php">
                            <div class="mb-3">
                                <label for="title" class="form-label">Task Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select a category</option>
                                    <option value="cleaning">Cleaning</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="delivery">Delivery</option>
                                    <option value="gardening">Gardening</option>
                                    <option value="technology">Technology</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Task Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="budget" class="form-label">Budget ($)</label>
                                    <input type="number" class="form-control" id="budget" name="budget" 
                                           value="<?php echo htmlspecialchars($_POST['budget'] ?? ''); ?>" min="1" step="0.01" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="deadline" class="form-label">Deadline</label>
                                    <input type="datetime-local" class="form-control" id="deadline" name="deadline" 
                                           value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="user_dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create Task
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 