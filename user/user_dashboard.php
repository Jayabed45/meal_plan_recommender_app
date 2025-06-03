<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Check if user has already submitted a survey
$stmt = $conn->prepare("SELECT * FROM surveys WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$survey = $result->fetch_assoc();
$stmt->close();

// Check for survey submission flag from session, in case database read is delayed/cached
$survey_submitted_in_session = isset($_SESSION['survey_submitted']) && $_SESSION['survey_submitted'] === true;

// Get user's meal plan if exists
$stmt = $conn->prepare("
    SELECT ump.id AS ump_id, mp.title, mp.description, ump.is_viewed,
           mp.breakfast_start, mp.breakfast_end,
           mp.lunch_start, mp.lunch_end,
           mp.dinner_start, mp.dinner_end,
           ump.created_at
    FROM user_meal_plans ump
    JOIN meal_plans mp ON ump.meal_plan_id = mp.id
    WHERE ump.user_id = ?
    ORDER BY ump.created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meal_plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Explicitly set $meal_plan to false if no result
if (!$meal_plan) {
    $meal_plan = false;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Added to troubleshoot mobile visibility */
        .take-survey-button, a[href="survey_form.php"] {
            display: inline-flex !important;
        }

        /* Ensure popup is visible on small screens */
        @media (max-width: 1023px) {
            #surveyPopup {
                z-index: 9999 !important;
                display: block !important; /* Ensure it's not hidden by a parent */
                /* Added for further mobile troubleshooting */
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                overflow: auto !important;
            }
             #surveyPopup > div {
                 /* Target the inner container to ensure content is centered */
                 min-height: 100vh;
                 display: flex;
                 align-items: center;
                 justify-content: center;
                 padding: 1rem; /* Add some padding */
             }
        }

    </style>
    <script>
    // Tailwind config
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

    document.addEventListener("DOMContentLoaded", function () {
        // Notification toggle
        const notifBtn = document.getElementById("notif-btn");
        const notifDropdown = document.getElementById("notif-dropdown");
        const notifDot = document.getElementById("notif-dot");
        const notifList = document.getElementById("notif-list");

        notifBtn?.addEventListener("click", function (e) {
            e.stopPropagation();
            notifDropdown.classList.toggle("hidden");
        });

        // Hide dropdown when clicking outside
        document.addEventListener("click", function (e) {
            if (!notifDropdown.classList.contains("hidden") && !notifBtn.contains(e.target)) {
                notifDropdown.classList.add("hidden");
            }
        });

        // Function to check for new notifications
        function checkNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.notifications && data.notifications.length > 0) {
                        notifDot.classList.remove("hidden");
                        notifList.innerHTML = ''; // Clear existing notifications
                        
                        data.notifications.forEach(notification => {
                            const item = document.createElement("li");
                            item.className = "p-4 hover:bg-light-blue cursor-pointer text-sm";
                            
                            const notifyTime = new Date(notification.notify_time);
                            const timeString = notifyTime.toLocaleString();
                            
                            item.innerHTML = `
                                <strong class="block mb-1 text-primary">${notification.message}</strong>
                                <span class="text-gray-600 text-xs">${timeString}</span>
                            `;
                            
                            // Add click handler to mark as read
                            item.addEventListener('click', () => {
                                markNotificationAsRead(notification.id);
                            });
                            
                            notifList.appendChild(item);
                        });
                    } else {
                        notifDot.classList.add("hidden");
                    }
                })
                .catch(error => console.error('Error checking notifications:', error));
        }

        // Function to mark notification as read
        function markNotificationAsRead(notificationId) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    checkNotifications(); // Refresh notifications
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }

        // Check for notifications every minute
        checkNotifications();
        setInterval(checkNotifications, 60000);
    });

    // Add event listener for the Cancel button in the survey popup
    document.addEventListener('DOMContentLoaded', function() {
        const cancelSurveyButton = document.getElementById('cancelSurveyPopup');
        const surveyButtonContainer = document.querySelector('#surveyPopup .flex-col.sm\:flex-row.gap-3.sm\:gap-4.w-full'); // Select the button container div

        if (surveyButtonContainer) {
            surveyButtonContainer.addEventListener('click', function(event) {
                // Check if the clicked element or its parent is the cancel button
                if (event.target.id === 'cancelSurveyPopup' || event.target.closest('#cancelSurveyPopup')) {
                    event.preventDefault(); // Prevent default if it's a link or other clickable element
                    closeSurveyPopup();
                }
            });
        }
    });

    // Add event listener to close the survey popup
    function closeSurveyPopup() {
        console.log('closeSurveyPopup function called'); // Debugging line
        const surveyPopup = document.getElementById('surveyPopup');
        if (surveyPopup) {
            surveyPopup.classList.add('hidden');
            // Check for overlay and handle if it exists
            const overlay = document.getElementById('overlay');
            if (overlay) {
                overlay.classList.add('hidden');
            }
            // Remove aggressive mobile styles on close
            if (window.innerWidth < 1024) {
                surveyPopup.style.position = '';
                surveyPopup.style.top = '';
                surveyPopup.style.left = '';
                surveyPopup.style.width = '';
                surveyPopup.style.height = '';
                surveyPopup.style.overflow = '';
            }
            document.body.style.overflow = ''; // Restore scrolling
        }
    }

    // Add event listener for the 'View Meal Plan' action when no plan exists
    document.getElementById('viewMealPlanAction')?.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default link behavior
        document.getElementById('waitingPopup')?.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling behind popup
    });

    // Add event listener to close the waiting popup
    document.getElementById('closeWaitingPopup')?.addEventListener('click', function() {
        document.getElementById('waitingPopup')?.classList.add('hidden');
        document.body.style.overflow = ''; // Restore scrolling
    });

    // Add this to your existing JavaScript
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
                <h1 class="text-xl sm:text-2xl font-bold text-primary">M P R A</h1>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 p-3 sm:p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="user_dashboard.php" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-primary bg-light-blue rounded-lg">
                            <svg class="w-5 h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span class="text-sm sm:text-base">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <?php if ($meal_plan && !$meal_plan['is_viewed']): ?>
                        <a href="view_meal_plan.php?id=<?= $meal_plan['ump_id'] ?>" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-gray-600 hover:bg-light-blue hover:text-primary rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="text-sm sm:text-base">Meal Plans</span>
                            <span class="ml-2 text-xs bg-green-200 text-green-800 px-2 py-1 rounded-full">New</span>
                        </a>
                        <?php elseif ($meal_plan): ?>
                        <a href="view_meal_plan.php?id=<?= $meal_plan['ump_id'] ?>" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-gray-600 hover:bg-light-blue hover:text-primary rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="text-sm sm:text-base">Meal Plans</span>
                        </a>
                        <?php elseif ($survey && !$meal_plan): // Survey done, but no meal plan ?>
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
            <!-- Top Navigation -->
            <nav class="bg-white shadow-sm">
                <div class="px-4 sm:px-8 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl sm:text-2xl font-semibold text-gray-800">Dashboard</h2>
                        
                        <!-- Notification Button -->
                        <div class="relative">
                            <button id="notif-btn" class="relative p-2 text-gray-600 hover:text-primary focus:outline-none">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <span id="notif-dot" class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full hidden"></span>
                            </button>
                            
                            <!-- Notification Dropdown -->
                            <div id="notif-dropdown" class="absolute right-0 mt-2 w-[280px] sm:w-80 bg-white rounded-lg shadow-lg border border-gray-100 hidden z-50">
                                <div class="p-3 sm:p-4 border-b border-gray-100">
                                    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Notifications</h3>
                                </div>
                                <ul id="notif-list" class="max-h-[60vh] sm:max-h-96 overflow-y-auto">
                                    <!-- Notifications will be populated here -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="p-4 sm:p-8">
                <!-- Welcome Section with Stats -->
                <div class="mb-6 sm:mb-8 bg-gradient-to-r from-primary to-secondary rounded-xl sm:rounded-2xl p-4 sm:p-8 text-white">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-bold mb-2">
                                Welcome back, <?=htmlspecialchars($username)?>! 👋
                            </h2>
                            <p class="text-blue-100 text-sm sm:text-base">Ready to continue your nutrition journey?</p>
                        </div>
                        <div class="mt-4 md:mt-0 flex flex-wrap gap-3 sm:gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-lg sm:rounded-xl p-3 sm:p-4 text-center min-w-[100px] sm:min-w-[120px]">
                                <div class="text-xl sm:text-2xl font-bold"><?= $survey ? '1' : '0' ?></div>
                                <div class="text-xs sm:text-sm text-blue-100">Survey Done</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-lg sm:rounded-xl p-3 sm:p-4 text-center min-w-[100px] sm:min-w-[120px]">
                                <div class="text-xl sm:text-2xl font-bold"><?= $meal_plan ? '1' : '0' ?></div>
                                <div class="text-xs sm:text-sm text-blue-100">Meal Plans</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mb-6 sm:mb-8">
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-3 sm:mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                        <?php if ($meal_plan): ?>
                        <a href="view_meal_plan.php?id=<?= $meal_plan['ump_id'] ?>" class="bg-white p-4 sm:p-6 rounded-lg sm:rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                            <div class="flex items-center">
                                <div class="bg-light-blue rounded-lg p-2 sm:p-3 group-hover:bg-primary group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                                <div class="ml-3 sm:ml-4">
                                    <h4 class="font-medium text-gray-800 text-sm sm:text-base">View Meal Plan</h4>
                                    <p class="text-xs sm:text-sm text-gray-500">Check your nutrition guide</p>
                                </div>
                            </div>
                        </a>
                        <?php else: ?>
                        <button id="viewMealPlanAction" class="bg-white p-4 sm:p-6 rounded-lg sm:rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group w-full text-left cursor-pointer">
                            <div class="flex items-center">
                                <div class="bg-gray-200 rounded-lg p-2 sm:p-3 group-hover:bg-gray-300 transition-colors">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600 group-hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                                <div class="ml-3 sm:ml-4">
                                    <h4 class="font-medium text-gray-600 text-sm sm:text-base">View Meal Plan</h4>
                                    <p class="text-xs sm:text-sm text-gray-500">Check your nutrition guide</p>
                                </div>
                            </div>
                        </button>
                        <?php endif; ?>
                        
                        <?php if (!$survey): // Show Take Survey if survey is pending ?>
                        <a href="survey_form.php" class="bg-primary p-4 sm:p-6 rounded-lg sm:rounded-xl shadow-sm border border-primary-dark hover:bg-primary-dark transition-colors group flex items-center justify-center">
                            <div class="flex items-center text-white">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <h4 class="font-medium text-sm sm:text-base">Take Survey</h4>
                            </div>
                        </a>
                        <?php else: // Show Update Survey if survey is completed ?>
                        <a href="survey_form.php" class="bg-white p-4 sm:p-6 rounded-lg sm:rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                             <div class="flex items-center">
                                 <div class="bg-light-blue rounded-lg p-2 sm:p-3 group-hover:bg-primary group-hover:text-white transition-colors">
                                     <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                     </svg>
                                 </div>
                                 <div class="ml-3 sm:ml-4">
                                     <h4 class="font-medium text-gray-800 text-sm sm:text-base">Update Survey</h4>
                                     <p class="text-xs sm:text-sm text-gray-500">Modify your preferences</p>
                                 </div>
                             </div>
                         </a>
                         <?php endif; ?>

                        <a href="track_progress.php" class="bg-white p-4 sm:p-6 rounded-lg sm:rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                            <div class="flex items-center">
                                <div class="bg-light-blue rounded-lg p-2 sm:p-3 group-hover:bg-primary group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="ml-3 sm:ml-4">
                                    <h4 class="font-medium text-gray-800 text-sm sm:text-base">Track Progress</h4>
                                    <p class="text-xs sm:text-sm text-gray-500">Monitor your journey</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Dashboard Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Meal Plan Card -->
                    <div class="mb-6 sm:mb-8">
                        <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-3 sm:mb-4">Your Meal Plan</h3>
                        <?php if ($meal_plan): ?>
                        <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
                                <div>
                                    <h4 class="font-medium text-gray-800 text-sm sm:text-base">Current Plan</h4>
                                    <p class="text-xs sm:text-sm text-gray-500">Created on <?= date('F j, Y', strtotime($meal_plan['created_at'])) ?></p>
                                </div>
                                <a href="view_meal_plan.php?id=<?= $meal_plan['ump_id'] ?>" class="inline-flex items-center px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors">
                                    View Details
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 ml-1 sm:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <?php elseif ($survey): // Survey done, but no meal plan yet ?>
                        <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
                                <div>
                                    <h4 class="font-medium text-gray-800 text-sm sm:text-base">Meal Plan Pending</h4>
                                    <p class="text-xs sm:text-sm text-gray-500">Your survey has been submitted. Please wait for an admin to recommend a meal plan.</p>
                                </div>
                                <span class="inline-flex items-center px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-yellow-800 bg-yellow-100 rounded-lg">
                                    Waiting
                                </span>
                            </div>
                        </div>
                        <?php else: // Survey not done ?>
                        <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
                                <div>
                                    <h4 class="font-medium text-gray-800 text-sm sm:text-base">No Meal Plan Yet</h4>
                                    <p class="text-xs sm:text-sm text-gray-500">Complete the survey to get your personalized meal plan</p>
                                </div>
                                <a href="survey_form.php" class="inline-flex items-center px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors">
                                    Take Survey
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 ml-1 sm:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Survey Status Card -->
                    <div class="mb-6 sm:mb-8">
                        <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-3 sm:mb-4">Survey Status</h3>
                        <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
                                <div>
                                    <h4 class="font-medium text-gray-800 text-sm sm:text-base"><?= $survey ? 'Survey Completed' : 'Survey Pending' ?></h4>
                                    <p class="text-xs sm:text-sm text-gray-500">
                                        <?= $survey ? 'Last updated on ' . (isset($survey['submitted_at']) && $survey['submitted_at'] ? date('F j, Y', strtotime($survey['submitted_at'])) : 'N/A') : 'Complete the survey to get started' ?>
                                    </p>
                                </div>
                                <?php if (!$survey): // Only show Take Survey if survey is pending ?>
                                <a href="survey_form.php" class="inline-flex items-center px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors">
                                    Take Survey
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 ml-1 sm:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                                <?php else: // Show Update Survey if survey is completed ?>
                                <a href="survey_form.php" class="inline-flex items-center px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                    Update Survey
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 ml-1 sm:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips Section -->
                <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Nutrition Tips</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-light-blue rounded-lg p-4">
                            <div class="text-primary mb-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <h4 class="font-medium text-gray-800 mb-1">Stay Hydrated</h4>
                            <p class="text-sm text-gray-600">Drink at least 8 glasses of water daily for optimal health.</p>
                        </div>
                        <div class="bg-light-blue rounded-lg p-4">
                            <div class="text-primary mb-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h4 class="font-medium text-gray-800 mb-1">Regular Meals</h4>
                            <p class="text-sm text-gray-600">Eat at consistent times to maintain energy levels.</p>
                        </div>
                        <div class="bg-light-blue rounded-lg p-4">
                            <div class="text-primary mb-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                            <h4 class="font-medium text-gray-800 mb-1">Balanced Diet</h4>
                            <p class="text-sm text-gray-600">Include proteins, carbs, and healthy fats in your meals.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Survey Popup -->
    <div id="surveyPopup" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="min-h-screen px-4 text-center">
            <div class="fixed inset-0" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="inline-block h-screen align-middle" aria-hidden="true">&#8203;</span>
            <div class="inline-block w-full max-w-md p-6 my-8 text-left align-middle transition-all transform bg-white shadow-xl rounded-lg sm:rounded-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-medium text-gray-900">Complete Survey First</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeSurveyPopup()">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-2">
                    <p class="text-sm sm:text-base text-gray-500">
                        Please complete the survey first to get your personalized meal plan.
                    </p>
                </div>
                <div class="mt-4 sm:mt-6 flex flex-col sm:flex-row gap-3 sm:gap-4">
                    <!-- Added z-index for mobile clickability -->
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 w-full" style="z-index: 10000;">
                        <button type="button" id="cancelSurveyPopup" class="inline-flex justify-center px-4 py-2 text-sm sm:text-base font-medium text-gray-700 bg-gray-100 border border-transparent rounded-lg hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-500">
                            Cancel
                        </button>
                        <a href="survey_form.php" class="inline-flex justify-center px-4 py-2 text-sm sm:text-base font-medium text-white bg-primary border border-transparent rounded-lg hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary">
                            Take Survey
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Waiting Popup -->
    <div id="waitingPopup" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="min-h-screen px-4 text-center">
            <div class="fixed inset-0" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="inline-block h-screen align-middle" aria-hidden="true">&#8203;</span>
            <div class="inline-block w-full max-w-md p-6 my-8 text-left align-middle transition-all transform bg-white shadow-xl rounded-lg sm:rounded-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-medium text-gray-900">Meal Plan in Progress</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeWaitingPopup()">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-2">
                    <p class="text-sm sm:text-base text-gray-500">
                        Your meal plan is being generated. Please check back later.
                    </p>
                </div>
                <div class="mt-4 sm:mt-6">
                    <button type="button" onclick="closeWaitingPopup()" class="inline-flex justify-center w-full px-4 py-2 text-sm sm:text-base font-medium text-white bg-primary border border-transparent rounded-lg hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Dropdown -->
    <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-[280px] sm:w-[320px] bg-white rounded-lg shadow-lg border border-gray-100 z-50">
        <div class="p-3 sm:p-4 border-b border-gray-100">
            <h3 class="text-sm sm:text-base font-medium text-gray-900">Notifications</h3>
        </div>
        <div class="max-h-[300px] overflow-y-auto">
            <?php if (empty($notifications)): ?>
            <div class="p-4 text-center text-sm sm:text-base text-gray-500">
                No new notifications
            </div>
            <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
            <div class="p-3 sm:p-4 border-b border-gray-100 hover:bg-gray-50">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-light-blue flex items-center justify-center">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3 sm:ml-4 flex-1">
                        <p class="text-xs sm:text-sm text-gray-900"><?= htmlspecialchars($notification['message']) ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?= date('M d, Y H:i', strtotime($notification['created_at'])) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($notifications)): ?>
        <div class="p-3 sm:p-4 border-t border-gray-100">
            <button onclick="markAllAsRead()" class="w-full text-center text-xs sm:text-sm text-primary hover:text-primary-dark">
                Mark all as read
            </button>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>