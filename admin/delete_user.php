<?php
session_start();
require '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    // Don't allow deleting yourself
    if ($user_id === $_SESSION['user_id']) {
        header("Location: manage_users.php?error=self_delete");
        exit;
    }

    // Check if user exists and is not an admin
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    if ($stmt === false) {
        die("Error preparing user check query: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: manage_users.php?error=user_not_found");
        exit;
    }

    $user = $result->fetch_assoc();
    if ($user['role'] === 'admin') {
        header("Location: manage_users.php?error=cannot_delete_admin");
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete user's notifications
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        if ($stmt === false) {
            throw new Exception("Error preparing notifications delete query: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete user's meal plan associations
        $stmt = $conn->prepare("DELETE FROM user_meal_plans WHERE user_id = ?");
        if ($stmt === false) {
            throw new Exception("Error preparing user_meal_plans delete query: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete user's surveys
        $stmt = $conn->prepare("DELETE FROM surveys WHERE user_id = ?");
        if ($stmt === false) {
            throw new Exception("Error preparing surveys delete query: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete admin notifications related to user's surveys
        $stmt = $conn->prepare("DELETE FROM admin_notifications WHERE survey_id IN (SELECT id FROM surveys WHERE user_id = ?)");
        if ($stmt === false) {
            throw new Exception("Error preparing admin notifications delete query: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Error preparing user delete query: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // If we got here, commit the transaction
        $conn->commit();
        header("Location: manage_users.php?success=user_deleted");
    } catch (Exception $e) {
        // If there was an error, rollback the transaction
        $conn->rollback();
        die("Error deleting user: " . $e->getMessage());
    }
    exit;
}

// If no ID provided
header("Location: manage_users.php?error=no_id");
exit;
