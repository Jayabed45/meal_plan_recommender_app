<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$current_date = date('Y-m-d');
$attendance_message = '';

// Handle attendance check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_in'])) {
    // Check if user has already checked in today
    $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND attendance_date = ?");
    $stmt->bind_param("is", $user_id, $current_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // User hasn't checked in today, insert record
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO attendance (user_id, attendance_date) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $current_date);
        if ($stmt->execute()) {
            $attendance_message = "Checked in successfully for today!";
        } else {
            $attendance_message = "Error checking in.";
        }
    } else {
        $attendance_message = "You have already checked in today.";
    }
    $stmt->close();
}

// Fetch attendance history
$attendance_history = [];
$stmt = $conn->prepare("SELECT attendance_date, created_at FROM attendance WHERE user_id = ? ORDER BY attendance_date DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $attendance_history[] = $row;
}
$stmt->close();

// Check if user has checked in today for button state
$has_checked_in_today = false;
$stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND attendance_date = ?");
$stmt->bind_param("is", $user_id, $current_date);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $has_checked_in_today = true;
}
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Progress</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563EB',
                        secondary: '#1E40AF',
                        accent: '#60A5FA',
                        'light-blue': '#EFF6FF',
                        'dark-blue': '#1E3A8A'
                    }
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('sidebar');
            
            mobileMenuButton.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 1024 && // Only on mobile
                    !sidebar.contains(e.target) && 
                    !mobileMenuButton.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                }
            });
        });
    </script>
</head>
<body class="bg-gray-50 min-h-screen text-gray-800 font-sans">
    <!-- Mobile Menu Button -->
    <button id="mobile-menu-button" class="lg:hidden fixed top-4 left-4 z-50 p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40">
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="p-4 sm:p-6 border-b">
                <h1 class="text-xl sm:text-2xl font-bold text-primary">Meal Plan App</h1>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 p-3 sm:p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="user_dashboard.php" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-gray-600 hover:bg-light-blue hover:text-primary rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span class="text-sm sm:text-base">Dashboard</span>
                        </a>
                    </li>
                    <!-- Meal Plans Link (Dynamic) -->
                    <?php 
                        // Re-fetch meal plan data to determine link behavior
                        $stmt_meal_plan = $conn->prepare("
                            SELECT ump.id AS ump_id
                            FROM user_meal_plans ump
                            WHERE ump.user_id = ?
                            ORDER BY ump.id DESC
                            LIMIT 1
                        ");
                        $stmt_meal_plan->bind_param("i", $user_id);
                        $stmt_meal_plan->execute();
                        $user_has_meal_plan = $stmt_meal_plan->get_result()->fetch_assoc();
                        $stmt_meal_plan->close();

                        // Re-fetch survey data to determine link behavior
                        $stmt_survey = $conn->prepare("SELECT id FROM surveys WHERE user_id = ?");
                        $stmt_survey->bind_param("i", $user_id);
                        $stmt_survey->execute();
                        $user_has_survey = $stmt_survey->get_result()->fetch_assoc();
                        $stmt_survey->close();
                    ?>
                    <li>
                        <?php if ($user_has_meal_plan): ?>
                        <a href="view_meal_plan.php?id=<?= $user_has_meal_plan['ump_id'] ?>" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-gray-600 hover:bg-light-blue hover:text-primary rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="text-sm sm:text-base">Meal Plans</span>
                        </a>
                        <?php elseif ($user_has_survey): // Survey done, but no meal plan ?>
                        <span class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-gray-500 cursor-default">
                            <svg class="w-5 h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="text-sm sm:text-base">Meal Plans</span>
                            <span class="ml-2 text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded-full">Waiting</span>
                        </span>
                        <?php else: // Survey not done ?>
                        <span class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-gray-400 cursor-not-allowed">
                            <svg class="w-5 h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="text-sm sm:text-base">Meal Plans</span>
                            <span class="ml-2 text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded-full">Survey First</span>
                        </span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <a href="track_progress.php" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-primary bg-light-blue rounded-lg">
                            <svg class="w-5 h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm sm:text-base">Track Progress</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile -->
            <div class="p-3 sm:p-4 border-t">
                <div class="flex items-center">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-primary flex items-center justify-center text-white">
                        <?= strtoupper(substr($username, 0, 1)) ?>
                    </div>
                    <div class="ml-2 sm:ml-3">
                        <p class="text-xs sm:text-sm font-medium text-gray-700"><?= htmlspecialchars($username) ?></p>
                        <a href="../logout.php" class="text-xs text-gray-500 hover:text-primary">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Top Header/Breadcrumbs (Optional, keep simple for now) -->
            <div class="mb-6 sm:mb-8">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Track Progress</h2>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">

                <!-- Attendance Section -->
                <div class="mb-6 sm:mb-8">
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-3 sm:mb-4">Daily Check-in</h3>
                    <?php if ($attendance_message): ?>
                        <div class="bg-light-blue border border-primary text-primary px-3 sm:px-4 py-2 sm:py-3 rounded relative mb-3 sm:mb-4 text-sm sm:text-base" role="alert">
                            <span class="block sm:inline"><?= $attendance_message ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="track_progress.php">
                        <button type="submit" name="check_in" 
                                class="w-full sm:w-auto <?= $has_checked_in_today ? 'bg-gray-400 cursor-not-allowed' : 'bg-primary hover:bg-secondary' ?> text-white py-2 sm:py-3 px-4 sm:px-6 rounded-lg font-semibold transition-colors duration-200 text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                <?= $has_checked_in_today ? 'disabled' : '' ?>>
                            <?= $has_checked_in_today ? 'Checked In Today' : 'Check In for Today' ?>
                        </button>
                    </form>
                </div>

                <!-- Attendance History Section -->
                <div>
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-3 sm:mb-4">Attendance History</h3>
                    <?php if ($attendance_history): ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($attendance_history as $record): ?>
                                <li class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1 sm:gap-0 text-sm sm:text-base">
                                    <span class="text-gray-700"><?= date('M d, Y', strtotime($record['attendance_date'])) ?></span>
                                    <span class="text-gray-500 text-xs sm:text-sm">Checked in at <?= date('h:i A', strtotime($record['created_at'])) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-600 text-sm sm:text-base">No attendance records yet. Check in to start tracking!</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html> 