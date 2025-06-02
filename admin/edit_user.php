<?php
session_start();
require '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success = false;

// Fetch user data
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_users.php?error=user_not_found");
    exit;
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $new_password = $_POST['new_password'] ?? '';

    // Validate input
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = "Invalid role.";
    }

    if (empty($errors)) {
        // Check if username or email already exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Username or email already exists.";
        } else {
            // Update user
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $username, $email, $hashed_password, $role, $user_id);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $username, $email, $role, $user_id);
            }

            if ($stmt->execute()) {
                $success = true;
                $user['username'] = $username;
                $user['email'] = $email;
                $user['role'] = $role;
            } else {
                $errors[] = "Error updating user: " . $conn->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Dashboard</title>
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
                        <a href="admin_dashboard.php" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-gray-600 hover:bg-light-blue hover:text-primary rounded-lg transition-colors">
                            <i class="fas fa-chart-line w-5 h-5 mr-2 sm:mr-3"></i>
                            <span class="text-sm sm:text-base">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_users.php" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-primary bg-light-blue rounded-lg">
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
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Top Navigation -->
            <header class="mb-6 sm:mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Edit User</h2>
                        <p class="text-gray-600 mt-2">Update user information</p>
                    </div>
                    <a href="manage_users.php" class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Users
                    </a>
                </div>
            </header>

            <?php if ($success): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded relative">
                    User updated successfully!
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded relative">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Edit User Form -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Password (leave blank to keep current)</label>
                        <input type="password" name="new_password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="manage_users.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('sidebar');
            
            mobileMenuButton.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 1024 && 
                    !sidebar.contains(e.target) && 
                    !mobileMenuButton.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                }
            });
        });
    </script>
</body>
</html>
