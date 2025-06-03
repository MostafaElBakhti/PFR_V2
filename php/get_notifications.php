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
    // Get unread notifications count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get recent notifications
    $stmt = $pdo->prepare("
        SELECT n.*, t.title as task_title
        FROM notifications n
        LEFT JOIN tasks t ON n.related_id = t.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'unread_count' => $unreadCount,
        'notifications' => $notifications
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 