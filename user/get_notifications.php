<?php
session_start();
require '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get unread notifications, ordered by most recent first
$stmt = $conn->prepare("
    SELECT id, message, notify_time 
    FROM notifications 
    WHERE user_id = ? 
    AND is_read = 0 
    ORDER BY notify_time DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'notify_time' => $row['notify_time']
    ];
}

echo json_encode(['notifications' => $notifications]);
$stmt->close();
