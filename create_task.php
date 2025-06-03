<?php
require_once 'php/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.']);
    exit;
}

// Check if user is a client
if ($_SESSION['user_type'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Only clients can create tasks.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $budget = floatval($_POST['budget'] ?? 0);
    $scheduled_time = $_POST['scheduled_time'] ?? '';

    $errors = [];

    // Validate inputs
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    if ($budget <= 0) {
        $errors[] = "Please enter a valid budget";
    }
    if (empty($scheduled_time)) {
        $errors[] = "Scheduled time is required";
    }

    // If there are errors, return them
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode("\n", $errors)
        ]);
        exit;
    }

    // If no errors, insert the task
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (client_id, title, description, location, scheduled_time, budget, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'open', NOW(), NOW())
        ");
        
        $stmt->execute([$userId, $title, $description, $location, $scheduled_time, $budget]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Task created successfully!'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error creating task. Please try again.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
} 