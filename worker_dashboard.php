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

// Check if user is a helper/worker
if ($_SESSION['user_type'] !== 'helper') {
    // If not a helper, destroy session and redirect to login
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get user information
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'helper'");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found, log them out
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get worker's tasks with client information
$stmt = $pdo->prepare("
    SELECT t.*, u.fullname as client_name, u.profile_image as client_profile 
    FROM tasks t 
    LEFT JOIN users u ON t.client_id = u.id 
    WHERE t.helper_id = ? 
    ORDER BY t.created_at DESC 
    LIMIT 10
");
$stmt->execute([$userId]);
$recent_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get task statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tasks,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN budget ELSE 0 END) as total_earnings
    FROM tasks 
    WHERE helper_id = ?
");
$stmt->execute([$userId]);
$task_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate completion rate
$completion_rate = $task_stats['total'] > 0 ? 
    round(($task_stats['completed'] / $task_stats['total']) * 100) : 0;

// Get available tasks
$stmt = $pdo->prepare("
    SELECT t.*, u.fullname as client_name
    FROM tasks t
    JOIN users u ON t.client_id = u.id
    WHERE t.status = 'open' 
    AND t.helper_id IS NULL
    ORDER BY t.created_at DESC
    LIMIT 5
");
$stmt->execute();
$available_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get worker's average rating
$stmt = $pdo->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
    FROM reviews
    WHERE reviewee_id = ?
");
$stmt->execute([$userId]);
$rating_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get tasks the worker has already applied to
$stmt = $pdo->prepare("
    SELECT id FROM tasks 
    WHERE helper_id = ? 
    AND status IN ('in_progress', 'completed')
");
$stmt->execute([$userId]);
$applied_tasks = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get notification counts
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0) as unread_notifications,
        (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0) as unread_messages
");
$stmt->execute([$userId, $userId]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskHelper - Worker Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3B82F6;
            --secondary-color: #6366F1;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --danger-color: #EF4444;
            --light-bg: #F3F4F6;
            --card-border-radius: 12px;
            --transition-speed: 0.3s;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Inter', sans-serif;
            color: #1F2937;
        }

        /* Improved Sidebar */
        .sidebar {
            height: 100vh;
            background: #FFFFFF;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            padding: 1.5rem;
            z-index: 1000;
            transition: all var(--transition-speed);
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar-toggle {
            position: absolute;
            top: 20px;
            right: -12px;
            width: 24px;
            height: 24px;
            background: var(--primary-color);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all var(--transition-speed);
            z-index: 100;
        }

        .sidebar-toggle:hover {
            transform: scale(1.1);
            background: var(--secondary-color);
        }

        .sidebar-toggle i {
            font-size: 12px;
            transition: transform var(--transition-speed);
        }

        .sidebar.collapsed .sidebar-toggle i {
            transform: rotate(180deg);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
        }

        .logo i {
            font-size: 1.8rem;
            min-width: 24px;
        }

        .sidebar.collapsed .logo span {
            display: none;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .menu-item {
            padding: 0.875rem 1.25rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all var(--transition-speed);
            font-weight: 500;
            display: flex;
            align-items: center;
            color: #4B5563;
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
        }

        .menu-item i {
            width: 20px;
            margin-right: 12px;
            font-size: 1.1rem;
            min-width: 20px;
            text-align: center;
            transition: all var(--transition-speed);
        }

        .sidebar.collapsed .menu-item {
            padding: 0.875rem;
            justify-content: center;
        }

        .sidebar.collapsed .menu-item i {
            margin-right: 0;
            font-size: 1.25rem;
        }

        .sidebar.collapsed .menu-item span {
            display: none;
        }

        .menu-item:hover {
            background: #F3F4F6;
            color: var(--primary-color);
        }

        .menu-item.active {
            background: var(--primary-color);
            color: white;
        }

        .sidebar.collapsed .menu-item.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed .menu-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Main Content Area */
        .main-content {
            margin-left: 280px;
            padding: 15px;
            transition: all var(--transition-speed);
        }

        .sidebar.collapsed + .main-content {
            margin-left: 80px;
        }

        .top-bar {
            background: white;
            padding: 1rem;
            border-radius: var(--card-border-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filter-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-select {
            padding: 0.5rem 2rem 0.5rem 1rem;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #4B5563;
            background-color: white;
            cursor: pointer;
        }

        .notification-group {
            position: relative;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .notification-icon {
            position: relative;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all var(--transition-speed);
            color: #4B5563;
        }

        .notification-icon:hover {
            background: #F3F4F6;
            color: var(--primary-color);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border: 2px solid white;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all var(--transition-speed);
        }

        .profile-section:hover {
            background: #F3F4F6;
        }

        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #E5E7EB;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
        }

        .profile-name {
            font-weight: 600;
            color: #1F2937;
        }

        .profile-role {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .divider {
            width: 1px;
            height: 24px;
            background: #E5E7EB;
            margin: 0 0.5rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-action-card {
            background: white;
            border-radius: var(--card-border-radius);
            padding: 1.5rem;
            text-align: center;
            transition: all var(--transition-speed);
            border: 1px solid #E5E7EB;
            cursor: pointer;
        }

        .quick-action-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary-color);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .quick-action-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .quick-action-icon.purple { background: #F3E8FF; color: #7C3AED; }
        .quick-action-icon.blue { background: #E0F2FE; color: #0284C7; }
        .quick-action-icon.green { background: #ECFDF5; color: #059669; }
        .quick-action-icon.orange { background: #FFF7ED; color: #EA580C; }

        /* Analytics Cards */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-in-out, opacity 0.3s ease-in-out, margin 0.5s ease-in-out;
            margin: 0;
        }

        .analytics-grid.show {
            max-height: 500px;
            opacity: 1;
            margin: 1.5rem 0;
        }

        .analytics-card {
            background: white;
            border-radius: var(--card-border-radius);
            padding: 1.5rem;
            transform: translateY(20px);
            transition: transform 0.4s ease-out;
        }

        .analytics-grid.show .analytics-card {
            transform: translateY(0);
        }

        .analytics-grid.show .analytics-card:nth-child(1) {
            transition-delay: 0.1s;
        }

        .analytics-grid.show .analytics-card:nth-child(2) {
            transition-delay: 0.2s;
        }

        .analytics-grid.show .analytics-card:nth-child(3) {
            transition-delay: 0.3s;
        }

        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .analytics-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1F2937;
        }

        .analytics-change {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
        }

        .analytics-change.positive {
            background: #ECFDF5;
            color: #059669;
        }

        .analytics-change.negative {
            background: #FEE2E2;
            color: #DC2626;
        }

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin: 1.5rem 0;
            transition: all var(--transition-speed);
        }

        @media (max-width: 768px) {
            .tasks-grid {
                grid-template-columns: 1fr;
            }
        }

        .tasks-grid.list-view {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .tasks-grid.list-view .task-grid-card {
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 1.5rem;
            padding: 1.25rem;
        }

        .tasks-grid.list-view .task-grid-card .location-budget {
            margin: 0.75rem 0;
        }

        .tasks-grid.list-view .task-grid-card .client-section {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
        }

        .tasks-grid.list-view .task-grid-card .task-main-content {
            padding-right: 1.5rem;
            border-right: 1px solid #E5E7EB;
        }

        .tasks-grid.list-view .task-grid-card .task-side-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 1rem;
        }

        .tasks-grid.list-view .task-grid-card .status-tag {
            align-self: flex-start;
        }

        .view-toggle-btn {
            border: 1px solid #E5E7EB;
            background: white;
            color: #6B7280;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            transition: all var(--transition-speed);
        }

        .view-toggle-btn:first-child {
            border-top-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }

        .view-toggle-btn:last-child {
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        .view-toggle-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .view-toggle-btn:hover:not(.active) {
            background: #F9FAFB;
            color: #374151;
        }

        .task-grid-card {
            background: white;
            border-radius: var(--card-border-radius);
            padding: 1.5rem;
            border: 1px solid #E5E7EB;
            transition: all var(--transition-speed);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .task-grid-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary-color);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .task-main-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .task-grid-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .task-grid-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1F2937;
            margin: 0;
            flex: 1;
        }

        .task-grid-description {
            color: #6B7280;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .location-budget {
            background: #F9FAFB;
            padding: 1rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .location-budget > div {
            flex: 1;
        }

        .location-budget i {
            color: var(--primary-color);
            width: 16px;
        }

        .location-budget .text-muted {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .location-budget .text-primary {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .client-section {
            padding-top: 1rem;
            border-top: 1px solid #E5E7EB;
            margin-top: auto;
        }

        .client-section .text-muted {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .client-section .fw-500 {
            color: #1F2937;
            font-weight: 500;
        }

        .apply-btn {
            width: 100%;
            padding: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.025em;
            transition: all var(--transition-speed);
        }

        .apply-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .apply-btn:disabled {
            background-color: #9CA3AF;
            border-color: #9CA3AF;
            cursor: not-allowed;
        }

        .status-tag {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .status-tag i {
            font-size: 0.75rem;
        }

        .status-open { 
            background: #ECFDF5; 
            color: #059669; 
        }
        .status-pending { 
            background: #FEF3C7; 
            color: #D97706; 
        }
        .status-progress { 
            background: #EFF6FF; 
            color: #2563EB; 
        }
        .status-completed { 
            background: #F3E8FF; 
            color: #7C3AED; 
        }

        .right-sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: 320px;
            height: 100vh;
            background: white;
            padding: 2rem;
            box-shadow: -4px 0 6px -1px rgba(0,0,0,0.1);
        }

        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #E5E7EB;
        }

        .progress-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: conic-gradient(
                var(--primary-color) <?php echo $completion_rate; ?>%, 
                #F3F4F6 <?php echo $completion_rate; ?>%
            );
            margin: 1.5rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .progress-circle::after {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
        }

        .progress-circle .percentage {
            position: relative;
            z-index: 1;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .available-task-card {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.75rem;
            background: white;
            border-radius: var(--card-border-radius);
            border: 1px solid #E5E7EB;
            transition: all var(--transition-speed);
        }

        .available-task-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary-color);
        }

        .rating-stars {
            color: #F59E0B;
            font-size: 1.25rem;
        }

        .toast {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .toast-header {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            padding: 0.75rem 1rem;
        }

        .toast-body {
            padding: 1rem;
        }

        .apply-btn {
            position: relative;
            overflow: hidden;
        }

        .apply-btn:disabled {
            cursor: not-allowed;
        }

        .apply-btn.btn-success:disabled {
            background-color: #198754;
            border-color: #198754;
            opacity: 1;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        .application-item {
            padding: 1rem;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            transition: all var(--transition-speed);
        }

        .application-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .notifications-card {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 360px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            border: 1px solid #E5E7EB;
            display: none;
            z-index: 1050;
        }

        .notifications-card::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 16px;
            height: 16px;
            background: white;
            transform: rotate(45deg);
            border-left: 1px solid #E5E7EB;
            border-top: 1px solid #E5E7EB;
        }

        .notifications-card.show {
            display: block;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notifications-header {
            padding: 1rem;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .notifications-list {
            max-height: 400px;
            overflow-y: auto;
            background: white;
        }

        .notifications-footer {
            padding: 1rem;
            text-align: center;
            border-top: 1px solid #E5E7EB;
            background: white;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        /* Add overlay for notifications */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: transparent;
            display: none;
            z-index: 1040;
        }

        .overlay.show {
            display: block;
        }

        /* Message Card Styles */
        .messages-card {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 360px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            border: 1px solid #E5E7EB;
            display: none;
            z-index: 1050;
        }

        .messages-card::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 16px;
            height: 16px;
            background: white;
            transform: rotate(45deg);
            border-left: 1px solid #E5E7EB;
            border-top: 1px solid #E5E7EB;
        }

        .messages-card.show {
            display: block;
            animation: slideDown 0.2s ease-out;
        }

        .messages-header {
            padding: 1rem;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .messages-list {
            max-height: 400px;
            overflow-y: auto;
            background: white;
        }

        .message-item {
            padding: 1rem;
            border-bottom: 1px solid #E5E7EB;
            transition: background-color 0.2s;
            cursor: pointer;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .message-item:hover {
            background-color: #F9FAFB;
        }

        .message-item.unread {
            background-color: #EFF6FF;
        }

        .message-item.unread:hover {
            background-color: #DBEAFE;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .message-content {
            flex: 1;
        }

        .message-sender {
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 0.25rem;
        }

        .message-text {
            color: #6B7280;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .message-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #6B7280;
        }

        .messages-footer {
            padding: 1rem;
            text-align: center;
            border-top: 1px solid #E5E7EB;
            background: white;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }
    </style>
</head>
<body>

<!-- Left Sidebar -->
<div class="sidebar">
    <button class="sidebar-toggle">
        <i class="fas fa-chevron-left"></i>
    </button>
    <div class="logo">
        <i class="fas fa-tasks"></i>
        <span>TaskHelper</span>
    </div>
    <div class="menu">
        <a href="#" class="menu-item active">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="#" class="menu-item">
            <i class="fas fa-list"></i>
            <span>My Tasks</span>
        </a>
        <a href="#" class="menu-item">
            <i class="fas fa-search"></i>
            <span>Find Tasks</span>
        </a>
        <a href="#" class="menu-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="#" class="menu-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="php/logout.php" class="menu-item" onclick="return confirm('Are you sure you want to logout?');">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Filter and Notification Bar -->
    <div class="top-bar">
        <div class="filter-group">
            <select class="filter-select">
                <option value="">Filter by Status</option>
                <option value="open">Available Tasks</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
            </select>
            <select class="filter-select">
                <option value="">Sort by Date</option>
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
            </select>
            <select class="filter-select">
                <option value="">Filter by Budget</option>
                <option value="0-100">$0 - $100</option>
                <option value="101-500">$101 - $500</option>
                <option value="501-1000">$501 - $1000</option>
                <option value="1000+">$1000+</option>
            </select>
        </div>
        <div class="notification-group">
            <div class="notification-icon" id="notificationIcon">
                <i class="fas fa-envelope fa-lg text-muted"></i>
                <?php if ($counts['unread_messages'] > 0): ?>
                <span class="notification-badge">
                    <?php echo $counts['unread_messages']; ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="notification-icon" id="notificationIcon">
                <i class="fas fa-bell fa-lg text-muted"></i>
                <?php if ($counts['unread_notifications'] > 0): ?>
                <span class="notification-badge">
                    <?php echo $counts['unread_notifications']; ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-section">
            <div class="position-relative d-inline-block">
                <img src="<?php echo $user['profile_image'] ?? 'default-avatar.png'; ?>" class="profile-image" style="width: 40px; height: 40px;">
            </div>
            <div class="profile-info">
                <h5 class="profile-name"><?php echo htmlspecialchars($user['fullname']); ?></h5>
                <div class="profile-role">
                    <?php echo htmlspecialchars($user['user_type'] === 'helper' ? 'Helper' : 'Worker'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="quick-action-card">
            <div class="quick-action-icon blue">
                <i class="fas fa-search"></i>
            </div>
            <h6>Find Tasks</h6>
            <small class="text-muted">Browse available tasks</small>
        </div>
        <div class="quick-action-card">
            <div class="quick-action-icon green">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <h6>Active Tasks</h6>
            <small class="text-muted">View ongoing tasks</small>
        </div>
        <div class="quick-action-card">
            <div class="quick-action-icon purple">
                <i class="fas fa-wallet"></i>
            </div>
            <h6>My Earnings</h6>
            <small class="text-muted">Track your income</small>
        </div>
        <div class="quick-action-card" onclick="toggleAnalytics()">
            <div class="quick-action-icon orange">
                <i class="fas fa-chart-line"></i>
            </div>
            <h6>View Reports</h6>
            <small class="text-muted">Performance insights</small>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="analytics-grid" id="analyticsSection">
        <div class="analytics-card">
            <div class="analytics-header">
                <h6 class="mb-0">Total Earnings</h6>
                <i class="fas fa-dollar-sign text-success"></i>
            </div>
            <div class="analytics-value">$<?php echo number_format($task_stats['total_earnings'], 2); ?></div>
            <div class="analytics-change positive">
                <i class="fas fa-arrow-up"></i> This Month
            </div>
        </div>
        <div class="analytics-card">
            <div class="analytics-header">
                <h6 class="mb-0">Task Success Rate</h6>
                <i class="fas fa-chart-pie text-primary"></i>
            </div>
            <div class="analytics-value"><?php echo $completion_rate; ?>%</div>
            <div class="analytics-change positive">
                <i class="fas fa-arrow-up"></i> Overall
            </div>
        </div>
        <div class="analytics-card">
            <div class="analytics-header">
                <h6 class="mb-0">Active Tasks</h6>
                <i class="fas fa-tasks text-warning"></i>
            </div>
            <div class="analytics-value"><?php echo $task_stats['in_progress']; ?></div>
            <div class="analytics-change">
                In Progress
            </div>
        </div>
    </div>

    <!-- Tasks Grid Section -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Available Tasks</h5>
                <div class="btn-group">
                    <button class="view-toggle-btn active" data-view="grid">
                        <i class="fas fa-grid-2 me-1"></i> Grid
                    </button>
                    <button class="view-toggle-btn" data-view="list">
                        <i class="fas fa-list me-1"></i> List
                    </button>
                </div>
            </div>
            
            <div class="tasks-grid">
                <?php foreach ($available_tasks as $task): ?>
                <div class="task-grid-card">
                    <div class="task-main-content">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h6 class="mb-0"><?php echo htmlspecialchars($task['title']); ?></h6>
                            <span class="status-tag status-open">
                                <i class="fas fa-circle-dot me-1"></i>Open
                            </span>
                        </div>
                        
                        <p class="text-muted mb-3">
                            <?php echo htmlspecialchars(substr($task['description'], 0, 100)) . '...'; ?>
                        </p>
                        
                        <div class="location-budget">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted d-block mb-1">Location</small>
                                    <span><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($task['location']); ?></span>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block mb-1">Budget</small>
                                    <span class="text-primary fw-bold">$<?php echo number_format($task['budget'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="task-side-content">
                        <div class="client-section">
                            <div>
                                <small class="text-muted d-block mb-1">Posted by</small>
                                <span class="fw-500"><?php echo htmlspecialchars($task['client_name']); ?></span>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100 apply-btn" 
                                data-task-id="<?php echo $task['id']; ?>"
                                <?php echo in_array($task['id'], $applied_tasks) ? 'disabled' : ''; ?>>
                            <?php echo in_array($task['id'], $applied_tasks) ? 'Applied' : 'Apply Now'; ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add the notifications dropdown card -->
    <div class="notifications-card" id="notificationsCard">
        <div class="notifications-header">
            <h6 class="mb-0">Notifications</h6>
            <button class="btn btn-link btn-sm p-0 text-muted" id="markAllRead">
                <i class="fas fa-check-double"></i> Mark all as read
            </button>
        </div>
        <div class="notifications-list" id="notificationsList">
            <!-- Notifications will be loaded here dynamically -->
        </div>
        <div class="notifications-footer">
            <a href="#" class="text-primary">View All Notifications</a>
        </div>
    </div>

    <!-- Add the messages dropdown card -->
    <div class="messages-card" id="messagesCard">
        <div class="messages-header">
            <h6 class="mb-0">Messages</h6>
            <button class="btn btn-link btn-sm p-0 text-muted" id="markAllMessagesRead">
                <i class="fas fa-check-double"></i> Mark all as read
            </button>
        </div>
        <div class="messages-list" id="messagesList">
            <!-- Messages will be loaded here dynamically -->
        </div>
        <div class="messages-footer">
            <a href="#" class="text-primary">View All Messages</a>
        </div>
    </div>

    <!-- Add overlay for notifications -->
    <div class="overlay" id="notificationsOverlay"></div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="toastNotification" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Analytics toggle function
    window.toggleAnalytics = function() {
        const analyticsSection = document.getElementById('analyticsSection');
        analyticsSection.classList.toggle('show');
    }

    // View toggle functionality
    const viewToggleBtns = document.querySelectorAll('.view-toggle-btn');
    const tasksGrid = document.querySelector('.tasks-grid');

    viewToggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons
            viewToggleBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            btn.classList.add('active');
            
            // Toggle grid/list view
            if (btn.dataset.view === 'list') {
                tasksGrid.classList.add('list-view');
            } else {
                tasksGrid.classList.remove('list-view');
            }
        });
    });

    // Initialize toast
    const toastElement = document.getElementById('toastNotification');
    const toast = new bootstrap.Toast(toastElement);

    // Handle apply buttons
    document.querySelectorAll('.apply-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const taskId = this.dataset.taskId;
            const button = this;

            try {
                // Disable button and show loading state
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Applying...';

                // Send application request
                const response = await fetch('php/apply_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}`
                });

                const data = await response.json();

                if (data.status === 'success') {
                    // Update button state
                    button.innerHTML = 'Applied';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-success');
                    button.disabled = true;

                    // Show success message
                    document.querySelector('.toast-body').textContent = data.message;
                    document.querySelector('.toast-header').style.backgroundColor = '#d1e7dd';
                    toast.show();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                // Reset button state
                button.disabled = false;
                button.innerHTML = 'Apply Now';

                // Show error message
                document.querySelector('.toast-body').textContent = error.message;
                document.querySelector('.toast-header').style.backgroundColor = '#f8d7da';
                toast.show();
            }
        });
    });

    // Function to format relative time
    function getRelativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
        if (diffInSeconds < 604800) return Math.floor(diffInSeconds / 86400) + 'd ago';
        return date.toLocaleDateString();
    }

    // Notifications handling
    const notificationIcon = document.querySelector('.notification-icon:last-child');
    const notificationsCard = document.getElementById('notificationsCard');
    const notificationsOverlay = document.getElementById('notificationsOverlay');
    const markAllRead = document.getElementById('markAllRead');
    const notificationsList = document.getElementById('notificationsList');

    // Toggle notifications card
    notificationIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationsCard.classList.toggle('show');
        notificationsOverlay.classList.toggle('show');
        if (notificationsCard.classList.contains('show')) {
            loadNotifications();
        }
    });

    // Close notifications when clicking overlay
    notificationsOverlay.addEventListener('click', function() {
        notificationsCard.classList.remove('show');
        notificationsOverlay.classList.remove('show');
    });

    // Prevent closing when clicking inside the card
    notificationsCard.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Load notifications
    async function loadNotifications() {
        try {
            const response = await fetch('php/get_notifications.php');
            const data = await response.json();
            
            if (data.status === 'success') {
                notificationsList.innerHTML = data.notifications.length > 0 
                    ? data.notifications.map(notification => `
                        <div class="notification-item ${notification.is_read ? '' : 'unread'}">
                            <div class="notification-content">
                                ${notification.content}
                            </div>
                            <div class="notification-meta">
                                <span>${getRelativeTime(notification.created_at)}</span>
                                ${!notification.is_read ? '<span class="text-primary">New</span>' : ''}
                            </div>
                        </div>
                    `).join('')
                    : '<div class="text-center p-4 text-muted">No notifications</div>';
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            notificationsList.innerHTML = '<div class="text-center p-4 text-danger">Error loading notifications</div>';
        }
    }

    // Mark all notifications as read
    markAllRead.addEventListener('click', async function(e) {
        e.preventDefault();
        try {
            const response = await fetch('php/mark_notifications_read.php', {
                method: 'POST'
            });
            const data = await response.json();
            
            if (data.status === 'success') {
                // Remove unread class from all notifications
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                // Hide the notification badge
                const badge = notificationIcon.querySelector('.notification-badge');
                if (badge) {
                    badge.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error marking notifications as read:', error);
        }
    });

    // Update notifications periodically
    setInterval(loadNotifications, 30000);

    // Messages handling
    const messageIcon = document.querySelector('.notification-icon:first-child');
    const messagesCard = document.getElementById('messagesCard');
    const messagesList = document.getElementById('messagesList');
    const markAllMessagesRead = document.getElementById('markAllMessagesRead');

    // Toggle messages card
    messageIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        messagesCard.classList.toggle('show');
        notificationsOverlay.classList.toggle('show');
        if (messagesCard.classList.contains('show')) {
            loadMessages();
        }
    });

    // Close messages when clicking overlay
    notificationsOverlay.addEventListener('click', function() {
        messagesCard.classList.remove('show');
        notificationsOverlay.classList.remove('show');
    });

    // Prevent closing when clicking inside the card
    messagesCard.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Load messages
    function loadMessages() {
        fetch('php/get_messages.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const messagesList = document.querySelector('.messages-list');
                    messagesList.innerHTML = '';
                    
                    if (data.messages.length === 0) {
                        messagesList.innerHTML = '<div class="no-messages">No messages yet</div>';
                        return;
                    }

                    data.messages.forEach(message => {
                        const messageItem = document.createElement('div');
                        messageItem.className = `message-item ${message.is_read ? '' : 'unread'}`;
                        messageItem.innerHTML = `
                            <div class="message-avatar">
                                <img src="${message.sender_image || 'images/default-avatar.png'}" alt="${message.sender_name}">
                            </div>
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="message-sender">${message.sender_name}</span>
                                    <span class="message-time">${formatTime(message.created_at)}</span>
                                </div>
                                <div class="message-preview">
                                    <span class="task-title">${message.task_title}</span>
                                    <p>${message.message}</p>
                                </div>
                            </div>
                        `;
                        messagesList.appendChild(messageItem);
                    });

                    // Update badge count
                    const badge = document.querySelector('.message-badge');
                    if (badge) {
                        badge.textContent = data.unread_count;
                        badge.style.display = data.unread_count > 0 ? 'block' : 'none';
                    }
                } else {
                    console.error('Error loading messages:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading messages:', error);
            });
    }

    // Mark all messages as read
    markAllMessagesRead.addEventListener('click', async function(e) {
        e.preventDefault();
        try {
            const response = await fetch('php/mark_messages_read.php', {
                method: 'POST'
            });
            const data = await response.json();
            
            if (data.status === 'success') {
                // Remove unread class from all messages
                document.querySelectorAll('.message-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                // Hide the message badge
                const badge = messageIcon.querySelector('.notification-badge');
                if (badge) {
                    badge.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error marking messages as read:', error);
        }
    });

    // Update messages periodically
    setInterval(loadMessages, 30000);

    // Sidebar Toggle Functionality
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    if (sidebar && toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Save the sidebar state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
        
        // Check localStorage for saved sidebar state
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
        }
    }
});
</script>
</body>
</html> 