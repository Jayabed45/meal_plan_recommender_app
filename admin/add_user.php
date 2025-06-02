<?php
session_start();
require '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    $errors = [];

    // Validate input
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = "Invalid role.";
    }

    if (empty($errors)) {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Username or email already exists.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'User added successfully']);
                exit;
            } else {
                $errors[] = "Error adding user: " . $conn->error;
            }
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
        exit;
    }
}

// If not POST request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit; 