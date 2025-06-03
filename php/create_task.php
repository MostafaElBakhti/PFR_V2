<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'client') {
    header('Location: ../login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $budget = floatval($_POST['budget'] ?? 0);
    $client_id = $_SESSION['user_id'];

    // Validate inputs
    $errors = [];

    if (empty($title)) {
        $errors[] = "Title is required";
    }

    if (empty($description)) {
        $errors[] = "Description is required";
    }

    if (empty($location)) {
        $errors[] = "Location is required";
    }

    if (empty($date) || empty($time)) {
        $errors[] = "Date and time are required";
    }

    if ($budget <= 0) {
        $errors[] = "Budget must be greater than 0";
    }

    // If no errors, create the task
    if (empty($errors)) {
        try {
            // Combine date and time
            $datetime = date('Y-m-d H:i:s', strtotime("$date $time"));

            // Insert task into database
            $stmt = $pdo->prepare("
                INSERT INTO tasks (
                    client_id,
                    title,
                    description,
                    location,
                    scheduled_time,
                    budget,
                    status,
                    created_at
                ) VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'open',
                    NOW()
                )
            ");

            $stmt->execute([
                $client_id,
                $title,
                $description,
                $location,
                $datetime,
                $budget
            ]);

            // Set success message
            $_SESSION['success_message'] = "Task created successfully!";
            header('Location: ../user_dashboard.php');
            exit;

        } catch (PDOException $e) {
            $errors[] = "An error occurred while creating the task. Please try again.";
        }
    }

    // If there were errors, redirect back with error messages
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
        header('Location: ../user_dashboard.php');
        exit;
    }
} else {
    // If not POST request, redirect to dashboard
    header('Location: ../user_dashboard.php');
    exit;
} 