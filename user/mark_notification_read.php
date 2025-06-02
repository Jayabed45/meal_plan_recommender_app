<?php
require '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get the notification ID from the request body
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = intval($data['notification_id'] ?? 0);

if (!$notification_id) {
    echo json_encode(['error' => 'Invalid notification ID']);
    exit;
}

// Mark the notification as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success]);
?> 