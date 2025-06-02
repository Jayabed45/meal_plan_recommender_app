<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
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
    <title>Manage Users - Admin Dashboard</title>
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
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">User Management</h2>
                        <p class="text-gray-600 mt-2">Manage and monitor user accounts</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search users..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <button onclick="showAddUserModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center">
                            <i class="fas fa-user-plus mr-2"></i>
                            Add User
                        </button>
                    </div>
                </div>
            </header>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
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
                        <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
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
                                            <button onclick="editUser(<?= $user['id'] ?>)" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </button>
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
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add New User</h3>
                    <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="addUserForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="username" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Add User
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

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTableBody tr');
            
            rows.forEach(row => {
                const username = row.querySelector('td:first-child').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                
                if (username.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Modal functions
        function showAddUserModal() {
            document.getElementById('addUserModal').classList.remove('hidden');
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.add('hidden');
        }

        // User management functions
        function editUser(userId) {
            window.location.href = `edit_user.php?id=${userId}`;
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = `delete_user.php?id=${userId}`;
            }
        }

        // Add user form submission
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('add_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error adding user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding user');
            });
        });
    </script>
</body>
</html> 