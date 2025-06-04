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

// Get user information
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'client'");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all tasks with applications
$stmt = $pdo->prepare("
    SELECT t.*, 
           u.fullname as helper_name, 
           u.profile_image as helper_profile,
           u.email as helper_email,
           u.created_at as helper_join_date,
           (SELECT COUNT(*) FROM tasks WHERE helper_id IS NOT NULL AND id = t.id) as application_count
    FROM tasks t 
    LEFT JOIN users u ON t.helper_id = u.id 
    WHERE t.client_id = ? 
    AND t.status = 'in_progress'
    ORDER BY t.created_at DESC
");
$stmt->execute([$userId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get helper ratings
$helperRatings = [];
foreach ($tasks as $task) {
    if ($task['helper_id']) {
        $stmt = $pdo->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
            FROM reviews
            WHERE reviewee_id = ?
        ");
        $stmt->execute([$task['helper_id']]);
        $helperRatings[$task['helper_id']] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - TaskHelper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #1F2937;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
        }

        .back-button:hover {
            background: #f3f4f6;
            color: #1f2937;
            transform: translateX(-2px);
        }

        .application-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #eee;
            transition: transform 0.2s ease;
            margin-bottom: 1.5rem;
        }

        .application-card:hover {
            transform: translateY(-2px);
        }

        .application-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .application-body {
            padding: 1.5rem;
        }

        .helper-profile {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1rem;
            letter-spacing: 2px;
        }

        .status-badge {
            padding: 0.5rem 1.25rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .status-in-progress { 
            background: #e6f7ff; 
            color: #1890ff;
            border: 1px solid #91d5ff;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .action-buttons .btn {
            flex: 1;
            padding: 0.75rem;
            font-weight: 500;
        }

        .helper-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #eee;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3B82F6;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .no-applications {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .no-applications i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="user_dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <h2 class="mb-4">Task Applications</h2>

        <?php if (empty($tasks)): ?>
        <div class="no-applications">
            <i class="fas fa-clipboard-list"></i>
            <h4>No Active Applications</h4>
            <p class="text-muted">You don't have any active task applications at the moment.</p>
            <a href="user_dashboard.php" class="btn btn-primary mt-3">
                <i class="fas fa-plus me-2"></i>Post a New Task
            </a>
        </div>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
            <div class="application-card">
                <div class="application-header">
                    <h4 class="mb-0"><?php echo htmlspecialchars($task['title']); ?></h4>
                    <span class="status-badge status-in-progress">
                        <i class="fas fa-clock me-1"></i>In Progress
                    </span>
                </div>
                <div class="application-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-4">
                                <img src="<?php echo $task['helper_profile'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($task['helper_name']); ?>" 
                                     alt="<?php echo htmlspecialchars($task['helper_name']); ?>" 
                                     class="helper-profile me-3">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($task['helper_name']); ?></h5>
                                    <div class="rating-stars mb-2">
                                        <?php
                                        $rating = round($helperRatings[$task['helper_id']]['avg_rating'] ?? 0);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                        <span class="text-muted ms-2">(<?php echo $helperRatings[$task['helper_id']]['total_reviews'] ?? 0; ?> reviews)</span>
                                    </div>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($task['helper_email']); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="helper-stats">
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $helperRatings[$task['helper_id']]['total_reviews'] ?? 0; ?></div>
                                    <div class="stat-label">Total Reviews</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo number_format($helperRatings[$task['helper_id']]['avg_rating'] ?? 0, 1); ?></div>
                                    <div class="stat-label">Average Rating</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $task['application_count']; ?></div>
                                    <div class="stat-label">Tasks Completed</div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h6 class="mb-3">Task Details</h6>
                                <p class="mb-3"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                                <div class="d-flex gap-4">
                                    <div>
                                        <small class="text-muted d-block">Budget</small>
                                        <strong class="text-primary">$<?php echo number_format($task['budget'], 2); ?></strong>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Location</small>
                                        <strong><?php echo htmlspecialchars($task['location']); ?></strong>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Posted Date</small>
                                        <strong><?php echo date('M d, Y', strtotime($task['created_at'])); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="action-buttons">
                                <button class="btn btn-success" onclick="completeTask(<?php echo $task['id']; ?>)">
                                    <i class="fas fa-check me-2"></i>Mark as Complete
                                </button>
                                <button class="btn btn-outline-primary" onclick="messageHelper(<?php echo $task['helper_id']; ?>)">
                                    <i class="fas fa-envelope me-2"></i>Message
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function completeTask(taskId) {
        if (confirm('Are you sure you want to mark this task as complete?')) {
            // Add your task completion logic here
            alert('Task marked as complete!');
        }
    }

    function messageHelper(helperId) {
        // Add your messaging logic here
        alert('Opening chat with helper...');
    }
    </script>
</body>
</html> 