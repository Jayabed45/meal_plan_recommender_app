<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$meal_plan_user_id = intval($_GET['id'] ?? 0);

if (!$meal_plan_user_id) {
    die("Invalid meal plan ID.");
}

// Get meal plan user_meal_plan record to confirm ownership and get meal plan times
$stmt = $conn->prepare("
    SELECT ump.id AS ump_id, mp.title, mp.description,
           mp.breakfast_start, mp.breakfast_end,
           mp.lunch_start, mp.lunch_end,
           mp.dinner_start, mp.dinner_end
    FROM user_meal_plans ump
    JOIN meal_plans mp ON ump.meal_plan_id = mp.id
    WHERE ump.id = ? AND ump.user_id = ?
");
$stmt->bind_param("ii", $meal_plan_user_id, $user_id);
$stmt->execute();
$meal_plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$meal_plan) {
    die("Meal plan not found or unauthorized.");
}

// Mark meal plan as viewed only if not already viewed
$stmt = $conn->prepare("UPDATE user_meal_plans SET is_viewed = 1 WHERE id = ?");
$stmt->bind_param("i", $meal_plan_user_id);
$stmt->execute();
$stmt->close();

// If the user is viewing a meal plan, it means they are no longer awaiting a new one
if (isset($_SESSION['awaiting_new_plan'])) {
    unset($_SESSION['awaiting_new_plan']);
}

// Helper function to format time if valid
function formatTime($time) {
    if (!$time || $time === '00:00:00') {
        return null;
    }
    return date("h:i A", strtotime($time));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Meal Plan Details</title>
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
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Add SweetAlert2 for beautiful notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <div class="p-6 border-b">
                <h1 class="text-2xl font-bold text-primary">M P R A</h1>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="user_dashboard.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-light-blue hover:text-primary rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="view_meal_plan.php?id=<?= $meal_plan['ump_id'] ?>" class="flex items-center px-4 py-3 text-primary bg-light-blue rounded-lg">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Meal Plans
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile -->
            <div class="p-4 border-t">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white">
                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($_SESSION['username']) ?></p>
                        <a href="../logout.php" class="text-xs text-gray-500 hover:text-primary">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <header class="mb-6 sm:mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Meal Plan Details</h2>
                        <p class="text-gray-600 mt-1 sm:mt-2 text-sm sm:text-base">Your personalized nutrition guide</p>
                    </div>
                    <a href="user_dashboard.php" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-primary hover:bg-secondary text-white rounded-lg transition-colors duration-200 font-medium text-sm sm:text-base">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Dashboard
                    </a>
                </div>
            </header>

            <!-- Meal Plan Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-primary to-secondary p-4 sm:p-6">
                    <div class="flex items-center">
                        <div class="bg-white/20 rounded-full p-2 sm:p-3 mr-3 sm:mr-4">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-3xl font-bold text-white">
                                <?=htmlspecialchars($meal_plan['title'])?>
                            </h1>
                        </div>
                    </div>
                </div>

                <!-- Meal Times -->
                <div class="p-4 sm:p-6 border-b border-gray-100">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4">Meal Schedule</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                        <?php
                        $breakfast_start = formatTime($meal_plan['breakfast_start']);
                        $breakfast_end = formatTime($meal_plan['breakfast_end']);
                        $lunch_start = formatTime($meal_plan['lunch_start']);
                        $lunch_end = formatTime($meal_plan['lunch_end']);
                        $dinner_start = formatTime($meal_plan['dinner_start']);
                        $dinner_end = formatTime($meal_plan['dinner_end']);
                        ?>

                        <?php if ($breakfast_start && $breakfast_end): ?>
                        <div class="bg-light-blue rounded-lg p-3 sm:p-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <h4 class="font-medium text-gray-800">Breakfast</h4>
                            </div>
                            <p class="text-sm text-gray-600"><?= $breakfast_start ?> - <?= $breakfast_end ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($lunch_start && $lunch_end): ?>
                        <div class="bg-light-blue rounded-lg p-3 sm:p-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <h4 class="font-medium text-gray-800">Lunch</h4>
                            </div>
                            <p class="text-sm text-gray-600"><?= $lunch_start ?> - <?= $lunch_end ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($dinner_start && $dinner_end): ?>
                        <div class="bg-light-blue rounded-lg p-3 sm:p-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <h4 class="font-medium text-gray-800">Dinner</h4>
                            </div>
                            <p class="text-sm text-gray-600"><?= $dinner_start ?> - <?= $dinner_end ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Description -->
                <?php if ($meal_plan['description']): ?>
                <div class="p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4">Description</h3>
                    <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
                        <?= nl2br(htmlspecialchars($meal_plan['description'])) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Age Group and Dietary Information -->
                <?php
                // Get user's survey information
                $stmt = $conn->prepare("
                    SELECT s.age, s.health_conditions, s.dietary_restrictions, s.food_allergies
                    FROM surveys s
                    JOIN user_meal_plans ump ON s.user_id = ump.user_id
                    WHERE ump.id = ?
                ");
                $stmt->bind_param("i", $meal_plan_user_id);
                $stmt->execute();
                $survey = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($survey) {
                    // Determine age group
                    $age = $survey['age'] ?? 0;
                    $age_group = '';
                    if ($age > 0 && $age <= 12) {
                        $age_group = 'child';
                    } elseif ($age > 12 && $age <= 60) {
                        $age_group = 'mid_age';
                    } elseif ($age > 60) {
                        $age_group = 'old';
                    }
                }
                    // Get health conditions and dietary restrictions
                    $health_conditions = array_filter(explode(',', $survey['health_conditions'] ?? ''));
                    $dietary_restrictions = array_filter(explode(',', $survey['dietary_restrictions'] ?? ''));
                    $food_allergies = $survey['food_allergies'] ?? '';

                    if ($age_group || !empty($health_conditions) || !empty($dietary_restrictions) || $food_allergies):
                ?>
                <div class="p-4 sm:p-6 border-t border-gray-100">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4">Special Considerations</h3>
                    <div class="space-y-4">
                        <?php if ($age_group): ?>
                        <div class="bg-light-blue rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <h4 class="font-medium text-gray-800">Age Group Recommendations</h4>
                            </div>
                            <p class="text-sm text-gray-600">
                                <?php
                                if ($age_group === 'child') {
                                    echo "This meal plan has been tailored for children, with appropriate portion sizes and child-friendly options.";
                                } elseif ($age_group === 'old') {
                                    echo "This meal plan has been modified for older adults, focusing on nutrient density and ease of preparation.";
                                }
                                ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($health_conditions)): ?>
                        <div class="bg-light-blue rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <h4 class="font-medium text-gray-800">Health Conditions</h4>
                            </div>
                            <p class="text-sm text-gray-600">
                                This meal plan has been customized to accommodate: 
                                <?= htmlspecialchars(implode(', ', $health_conditions)) ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($dietary_restrictions)): ?>
                        <div class="bg-light-blue rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <h4 class="font-medium text-gray-800">Dietary Restrictions</h4>
                            </div>
                            <p class="text-sm text-gray-600">
                                This meal plan has been modified to accommodate: 
                                <?= htmlspecialchars(implode(', ', $dietary_restrictions)) ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <?php if ($food_allergies): ?>
                        <div class="bg-red-50 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <h4 class="font-medium text-red-800">Food Allergies</h4>
                            </div>
                            <p class="text-sm text-red-600">
                                Please be aware of the following food allergies: 
                                <?= htmlspecialchars($food_allergies) ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$meal_plan['is_viewed']): ?>
    <script>
        // Show welcome notification when viewing meal plan for the first time
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Welcome to Your Meal Plan!',
                text: 'Your personalized meal plan has been created based on your preferences and goals. Take a moment to review it.',
                icon: 'info',
                confirmButtonText: 'Got it!',
                confirmButtonColor: '#3085d6'
            });
        });
    </script>
    <?php endif; ?>

    <script>
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
</body>
</html>
