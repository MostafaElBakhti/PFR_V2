<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Mark all unread notifications as read
    $stmt = $pdo->prepare("
        UPDATE notifications
        SET is_read = 1,
            read_at = NOW()
        WHERE user_id = ?
        AND is_read = 0
    ");
    $stmt->execute([$userId]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Notifications marked as read'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 