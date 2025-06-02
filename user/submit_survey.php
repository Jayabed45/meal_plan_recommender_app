<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal = $_POST['goal'] ?? '';
    $age = intval($_POST['age'] ?? 0);
    $height_cm = intval($_POST['height_cm'] ?? 0);
    $weight_kg = floatval($_POST['weight_kg'] ?? 0);
    $activity_level = $_POST['activity_level'] ?? '';
    $dietary_restrictions = $_POST['dietary_restrictions'] ?? null;
    $additional_notes = $_POST['additional_notes'] ?? null;

    // Basic validation
    $errors = [];
    $valid_goals = ['lose_weight', 'muscle_gain', 'maintain_weight', 'other'];
    $valid_activities = ['sedentary', 'light', 'moderate', 'active', 'very_active'];

    if (!in_array($goal, $valid_goals)) $errors[] = "Invalid goal selected.";
    if ($age < 10 || $age > 100) $errors[] = "Invalid age.";
    if ($height_cm < 50 || $height_cm > 300) $errors[] = "Invalid height.";
    if ($weight_kg < 20 || $weight_kg > 300) $errors[] = "Invalid weight.";
    if (!in_array($activity_level, $valid_activities)) $errors[] = "Invalid activity level.";

    if (!$errors) {
        // Check if survey exists for this user
        $stmt = $conn->prepare("SELECT id FROM surveys WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing_survey = $result->fetch_assoc();
        $stmt->close();

        if ($existing_survey) {
            // Update existing survey
            $stmt = $conn->prepare("UPDATE surveys SET goal = ?, age = ?, height_cm = ?, weight_kg = ?, activity_level = ?, dietary_restrictions = ?, additional_notes = ? WHERE user_id = ?");
            $stmt->bind_param("siiisssi", $goal, $age, $height_cm, $weight_kg, $activity_level, $dietary_restrictions, $additional_notes, $user_id);
            if ($stmt->execute()) {
                 // Insert notification for admin on survey update
                 $stmt->close();
                 $stmt2 = $conn->prepare("INSERT INTO admin_notifications (survey_id) VALUES (?)");
                 $stmt2->bind_param("i", $existing_survey['id']); // Use existing survey ID
                 $stmt2->execute();
                 $stmt2->close();

                 // Create notification for user
                 $stmt3 = $conn->prepare("INSERT INTO notifications (user_id, message, notify_time) VALUES (?, ?, NOW())");
                 $message = "Your survey has been submitted. Waiting for admin to recommend a meal plan.";
                 $stmt3->bind_param("is", $user_id, $message);
                 $stmt3->execute();
                 $stmt3->close();

                 $_SESSION['survey_submitted'] = true; // Keep this session variable to indicate survey presence
                 $_SESSION['awaiting_new_plan'] = true; // Set session variable to indicate waiting for new plan
                 header("Location: user_dashboard.php");
                 exit;
            } else {
                 $errors[] = "Failed to update survey.";
                 $stmt->close();
            }

        } else {
            // Insert new survey
            $stmt = $conn->prepare("INSERT INTO surveys (user_id, goal, age, height_cm, weight_kg, activity_level, dietary_restrictions, additional_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isiiisss", $user_id, $goal, $age, $height_cm, $weight_kg, $activity_level, $dietary_restrictions, $additional_notes);
            if ($stmt->execute()) {
                $survey_id = $stmt->insert_id;

                // Insert notification for admin only on initial survey submission
                $stmt->close();
                $stmt2 = $conn->prepare("INSERT INTO admin_notifications (survey_id) VALUES (?)");
                $stmt2->bind_param("i", $survey_id);
                $stmt2->execute();
                $stmt2->close();

                // Create notification for user
                $stmt3 = $conn->prepare("INSERT INTO notifications (user_id, message, notify_time) VALUES (?, ?, NOW())");
                $message = "Your survey has been submitted. Waiting for admin to recommend a meal plan.";
                $stmt3->bind_param("is", $user_id, $message);
                $stmt3->execute();
                $stmt3->close();

                $_SESSION['survey_submitted'] = true;
                $_SESSION['awaiting_new_plan'] = true; // Set session variable to indicate waiting for new plan
                header("Location: user_dashboard.php");
                exit;
            } else {
                $errors[] = "Failed to submit survey.";
                $stmt->close(); // Close the stmt if insert failed
            }
        }
    }
} else {
    $errors[] = "Invalid request method.";
}

if ($errors) {
    $_SESSION['survey_errors'] = $errors;
    header("Location: user_dashboard.php");
    exit;
}

// If the request method was not POST or there were no errors after processing (shouldn't happen with redirects above)
// We might need a fallback, but given the current structure, the redirects handle success and failure.
// For robustness, you might add a generic error redirect here if no headers have been sent.
if (!headers_sent() && empty($errors)) {
     // This part should ideally not be reached with the current logic
     // If somehow reached, it might indicate an issue or a direct GET request without a form submission
     // Decide on appropriate action, e.g., redirecting to dashboard or showing an error.
     // header("Location: user_dashboard.php");
     // exit;
}
?>
