<?php
require '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Not authorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $notif_id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);

    echo json_encode(['success' => true]);
}
