<?php
require '../config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the timezone to Asia/Manila (Philippines)
date_default_timezone_set('Asia/Manila');

// Log file for debugging
$log_file = __DIR__ . '/notification_log.txt';
function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

function createMealNotification($user_id, $meal_type, $meal_time) {
    global $conn;
    
    writeLog("Attempting to create notification for user $user_id, meal: $meal_type");
    
    // Check if notification already exists for this meal time today
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT id FROM notifications 
        WHERE user_id = ? 
        AND message LIKE ? 
        AND DATE(notify_time) = ?
    ");
    $message_pattern = "Time for your {$meal_type}%";
    $stmt->bind_param("iss", $user_id, $message_pattern, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Only create notification if one doesn't exist for today
    if ($result->num_rows === 0) {
        $message = "Time for your {$meal_type}! Please check your meal plan.";
        $notify_time = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, notify_time) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $message, $notify_time);
        $success = $stmt->execute();
        
        if ($success) {
            writeLog("Successfully created notification for user $user_id, meal: $meal_type");
        } else {
            writeLog("Failed to create notification for user $user_id, meal: $meal_type. Error: " . $stmt->error);
        }
        return $success;
    } else {
        writeLog("Notification already exists for user $user_id, meal: $meal_type today");
    }
    return false;
}

function checkNewMealPlans() {
    global $conn;
    
    writeLog("Checking for new meal plans");
    
    // Get unviewed meal plans that haven't been notified yet
    $stmt = $conn->prepare("
        SELECT ump.id, ump.user_id, mp.title, mp.description, ump.created_at, s.goal
        FROM user_meal_plans ump
        JOIN meal_plans mp ON ump.meal_plan_id = mp.id
        JOIN surveys s ON ump.user_id = s.user_id
        WHERE ump.is_viewed = 0 
        AND NOT EXISTS (
            SELECT 1 FROM notifications n 
            WHERE n.user_id = ump.user_id 
            AND n.message LIKE 'New meal plan%'
            AND DATE(n.notify_time) = DATE(ump.created_at)
        )
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        // Create a more detailed message including the goal
        $goal_text = ucwords(str_replace('_', ' ', $row['goal']));
        $message = "New meal plan recommended for your {$goal_text} goal: " . htmlspecialchars($row['title']);
        $notify_time = date('Y-m-d H:i:s');
        
        $insert_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, notify_time) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iss", $row['user_id'], $message, $notify_time);
        if ($insert_stmt->execute()) {
            $count++;
            writeLog("Created notification for new meal plan: {$row['title']} for user {$row['user_id']}");
        } else {
            writeLog("Failed to create notification for new meal plan. Error: " . $insert_stmt->error);
        }
        $insert_stmt->close();
    }
    
    writeLog("Found and processed $count new meal plans");
    $stmt->close();
}

// First check for new meal plans
checkNewMealPlans();

// Then check meal times
$stmt = $conn->prepare("
    SELECT ump.user_id, mp.breakfast_start, mp.lunch_start, mp.dinner_start
    FROM user_meal_plans ump
    JOIN meal_plans mp ON ump.meal_plan_id = mp.id
    WHERE mp.breakfast_start IS NOT NULL 
       OR mp.lunch_start IS NOT NULL 
       OR mp.dinner_start IS NOT NULL
");
$stmt->execute();
$result = $stmt->get_result();

// Get current time in PHP
$now = new DateTime();
$current_hour = $now->format('H');
$current_minute = $now->format('i');

writeLog("Current time: $current_hour:$current_minute");

$meal_count = 0;
while ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];
    
    // Check breakfast time
    if ($row['breakfast_start']) {
        $breakfast_time = new DateTime($row['breakfast_start']);
        $breakfast_hour = $breakfast_time->format('H');
        $breakfast_minute = $breakfast_time->format('i');
        
        writeLog("Checking breakfast time for user $user_id: $breakfast_hour:$breakfast_minute");
        
        // Check if current time matches breakfast time (within 1 minute)
        if ($current_hour == $breakfast_hour && abs($current_minute - $breakfast_minute) <= 1) {
            if (createMealNotification($user_id, 'breakfast', $row['breakfast_start'])) {
                $meal_count++;
            }
        }
    }
    
    // Check lunch time
    if ($row['lunch_start']) {
        $lunch_time = new DateTime($row['lunch_start']);
        $lunch_hour = $lunch_time->format('H');
        $lunch_minute = $lunch_time->format('i');
        
        writeLog("Checking lunch time for user $user_id: $lunch_hour:$lunch_minute");
        
        // Check if current time matches lunch time (within 1 minute)
        if ($current_hour == $lunch_hour && abs($current_minute - $lunch_minute) <= 1) {
            if (createMealNotification($user_id, 'lunch', $row['lunch_start'])) {
                $meal_count++;
            }
        }
    }
    
    // Check dinner time
    if ($row['dinner_start']) {
        $dinner_time = new DateTime($row['dinner_start']);
        $dinner_hour = $dinner_time->format('H');
        $dinner_minute = $dinner_time->format('i');
        
        writeLog("Checking dinner time for user $user_id: $dinner_hour:$dinner_minute");
        
        // Check if current time matches dinner time (within 1 minute)
        if ($current_hour == $dinner_hour && abs($current_minute - $dinner_minute) <= 1) {
            if (createMealNotification($user_id, 'dinner', $row['dinner_start'])) {
                $meal_count++;
            }
        }
    }
}

writeLog("Created $meal_count meal time notifications");
$stmt->close();
?> 