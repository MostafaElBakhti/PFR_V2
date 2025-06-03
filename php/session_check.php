<?php
// Start session
session_start();

// Get session information
$session_info = [
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_status' => session_status(),
    'session_save_path' => session_save_path(),
    'session_cookie_params' => session_get_cookie_params(),
    'session_data' => $_SESSION
];

// Output session information
header('Content-Type: application/json');
echo json_encode($session_info, JSON_PRETTY_PRINT);
?> 