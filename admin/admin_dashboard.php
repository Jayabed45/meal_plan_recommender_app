<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

// Fetch admin username
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$username = $admin['username'];
$stmt->close();

// Fetch unread notifications
$stmt = $conn->prepare("
  SELECT an.id AS notification_id, s.id AS survey_id, u.username, s.goal, s.submitted_at
  FROM admin_notifications an
  JOIN surveys s ON an.survey_id = s.id
  JOIN users u ON s.user_id = u.id
  WHERE an.is_read = 0
  ORDER BY s.submitted_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch users excluding admins
$user_stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE role != 'admin'");
$user_stmt->execute();
$users_result = $user_stmt->get_result();
$users = $users_result->fetch_all(MYSQLI_ASSOC);
$user_stmt->close();

// Count total users (excluding admins)
$total_users = count($users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
    </script>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
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
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                        <i class="fas fa-utensils text-white text-xl"></i>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Admin Panel</h1>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 p-3 sm:p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="admin_dashboard.php" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-primary bg-light-blue rounded-lg">
                            <i class="fas fa-chart-line w-5 h-5 mr-2 sm:mr-3"></i>
                            <span class="text-sm sm:text-base">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_users.php" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-gray-600 hover:bg-light-blue hover:text-primary rounded-lg transition-colors">
                            <i class="fas fa-users w-5 h-5 mr-2 sm:mr-3"></i>
                            <span class="text-sm sm:text-base">Manage Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="../logout.php" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-gray-600 hover:bg-light-blue hover:text-primary rounded-lg transition-colors">
                            <i class="fas fa-sign-out-alt w-5 h-5 mr-2 sm:mr-3"></i>
                            <span class="text-sm sm:text-base">Logout</span>
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
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Top Navigation -->
            <header class="mb-6 sm:mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Welcome back, <span class="text-primary capitalize"><?= htmlspecialchars($username) ?></span></h2>
                        <p class="text-gray-600 mt-2">Here's what's happening with your meal plans today</p>
                    </div>
                </div>
            </header>

            <div class="space-y-6 sm:space-y-8">
                <!-- New Survey Submissions -->
                <section class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center relative">
                                <i class="fas fa-bell text-white"></i>
                                <?php if (count($notifications) > 0): ?>
                                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center text-xs text-white font-medium">
                                        <?= count($notifications) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="text-lg sm:text-xl font-semibold text-gray-800">New Survey Submissions</h3>
                                <p class="text-sm text-gray-500">Review and create meal plans for new submissions</p>
                            </div>
                        </div>
                        <?php if (count($notifications) > 0): ?>
                            <span class="px-4 py-2 bg-yellow-50 text-yellow-600 rounded-full text-sm font-medium flex items-center space-x-2">
                                <i class="fas fa-bell text-yellow-500"></i>
                                <span><?= count($notifications) ?> New</span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (count($notifications) === 0): ?>
                        <div class="text-center py-8 sm:py-12">
                            <div class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-bell text-2xl text-gray-400"></i>
                            </div>
                            <p class="text-gray-500">No new surveys submitted.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($notifications as $note): ?>
                                <div class="bg-gray-50 rounded-xl p-4 sm:p-5 hover:shadow-md transition-shadow">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                        <div class="space-y-3">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-800">
                                                        <span class="text-blue-600"><?= htmlspecialchars($note['username']) ?></span>
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        <i class="far fa-clock text-gray-400 mr-1"></i>
                                                        <?= htmlspecialchars($note['submitted_at']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="bg-white rounded-lg p-3">
                                                <p class="text-sm text-gray-600">
                                                    <i class="fas fa-bullseye text-blue-500 mr-2"></i>
                                                    Goal: <span class="font-medium"><?= htmlspecialchars($note['goal']) ?></span>
                                                </p>
                                            </div>
                                        </div>
                                        <a href="recommend_meal_plan.php?survey_id=<?= $note['survey_id'] ?>&notif_id=<?= $note['notification_id'] ?>" 
                                           class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all duration-200">
                                            <i class="fas fa-eye mr-2"></i>
                                            Review
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Analytics Section -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    <!-- Total Users Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-medium">
                                <i class="fas fa-arrow-up mr-1"></i>Active
                            </span>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500">Total Users</p>
                            <div class="flex items-baseline space-x-2">
                                <p class="text-2xl sm:text-3xl font-bold text-gray-800"><?= $total_users ?></p>
                                <p class="text-sm text-gray-500">users</p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Active Users</span>
                                <span class="text-blue-600 font-medium"><?= $total_users ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- New Surveys Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                                <i class="fas fa-clipboard-list text-white text-xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-green-50 text-green-600 rounded-full text-xs font-medium">
                                <i class="fas fa-clock mr-1"></i>Today
                            </span>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500">New Surveys</p>
                            <div class="flex items-baseline space-x-2">
                                <p class="text-2xl sm:text-3xl font-bold text-gray-800"><?= count($notifications) ?></p>
                                <p class="text-sm text-gray-500">submissions</p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Pending Review</span>
                                <span class="text-green-600 font-medium"><?= count($notifications) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Meal Plans Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-6 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                                <i class="fas fa-utensils text-white text-xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-purple-50 text-purple-600 rounded-full text-xs font-medium">
                                <i class="fas fa-check mr-1"></i>Active
                            </span>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500">Total Meal Plans</p>
                            <div class="flex items-baseline space-x-2">
                                <p class="text-2xl sm:text-3xl font-bold text-gray-800"><?= count($notifications) ?></p>
                                <p class="text-sm text-gray-500">plans</p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">This Month</span>
                                <span class="text-purple-600 font-medium"><?= count($notifications) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Management Section -->
                <section class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                <i class="fas fa-users text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg sm:text-xl font-semibold text-gray-800">User Management</h3>
                                <p class="text-sm text-gray-500">Manage and monitor user accounts</p>
                            </div>
                        </div>
                        <a href="manage_users.php" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all duration-200">
                            <i class="fas fa-cog mr-2"></i>
                            Manage Users
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">
                                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['username']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center space-x-3">
                                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button onclick="deleteUser(<?= $user['id'] ?>)" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>

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

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = `delete_user.php?id=${userId}`;
            }
        }
    </script>
</body>
</html>
