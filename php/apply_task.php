<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a helper
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'helper') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$taskId = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$helperId = $_SESSION['user_id'];

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Check if task exists and is still open
    $stmt = $pdo->prepare("
        SELECT status, helper_id 
        FROM tasks 
        WHERE id = ? 
        FOR UPDATE
    ");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        throw new Exception('Task not found');
    }

    if ($task['status'] !== 'open') {
        throw new Exception('This task is no longer available');
    }

    if ($task['helper_id'] !== null) {
        throw new Exception('This task has already been assigned');
    }

    // Update task status and assign helper
    $stmt = $pdo->prepare("
        UPDATE tasks 
        SET status = 'in_progress', 
            helper_id = ?, 
            updated_at = NOW() 
        WHERE id = ? 
        AND status = 'open' 
        AND helper_id IS NULL
    ");
    $result = $stmt->execute([$helperId, $taskId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Failed to apply for task');
    }

    // Create a notification for the client
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, content, related_id, created_at)
        SELECT client_id, 'task_application', 'A helper has applied for your task', ?, NOW()
        FROM tasks
        WHERE id = ?
    ");
    $stmt->execute([$taskId, $taskId]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Successfully applied for the task'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 