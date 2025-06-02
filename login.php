<?php
session_start();
require 'config.php'; // database connection

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username_or_email) $errors[] = "Username or email is required.";
    if (!$password) $errors[] = "Password is required.";

    if (!$errors) {
        // Fetch user by username or email
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $username, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // Successful login
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                // Redirect based on role
                if ($role === 'admin') {
                    header("Location: admin/admin_dashboard.php");
                } else {
                    header("Location: user/user_dashboard.php");
                }
                exit;
            } else {
                $errors[] = "Invalid password.";
            }
        } else {
            $errors[] = "User not found.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Meal Plan Recommender - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-utensils text-2xl text-blue-600"></i>
                        <span class="ml-2 text-xl font-bold text-gray-800">MealPlan</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="openLoginModal()" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Login</button>
                    <button onclick="openRegisterModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200 text-sm font-medium">Create Account</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Sign In</h3>
                    <button onclick="closeLoginModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php if ($errors): ?>
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded relative">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?=htmlspecialchars($error)?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="modal_username_or_email">
                            Username or Email
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input
                                id="modal_username_or_email"
                                type="text"
                                name="username_or_email"
                                value="<?=htmlspecialchars($username_or_email ?? '')?>"
                                required
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your username or email"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="modal_password">
                            Password
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input
                                id="modal_password"
                                type="password"
                                name="password"
                                required
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your password"
                            >
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Sign In
                        </button>
                    </div>
                </form>
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <button onclick="closeLoginModal(); openRegisterModal()" class="font-medium text-blue-600 hover:text-blue-500">
                            Register now
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create Account</h3>
                    <button onclick="closeRegisterModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="register.php" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="modal_username">
                            Username
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input
                                id="modal_username"
                                type="text"
                                name="username"
                                required
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Choose a username"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="modal_email">
                            Email
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input
                                id="modal_email"
                                type="email"
                                name="email"
                                required
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter your email"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="modal_password">
                            Password
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input
                                id="modal_password"
                                type="password"
                                name="password"
                                required
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Create a password"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="modal_confirm_password">
                            Confirm Password
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input
                                id="modal_confirm_password"
                                type="password"
                                name="confirm_password"
                                required
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Confirm your password"
                            >
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <i class="fas fa-user-plus mr-2"></i>
                            Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                            <span class="block">Transform Your</span>
                            <span class="block text-blue-600">Eating Habits</span>
                        </h1>
                        <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            Get personalized meal plans tailored to your goals, whether it's weight loss, muscle gain, or maintaining a healthy lifestyle.
                        </p>
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-md shadow">
                                <button onclick="openLoginModal()" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10 transition-all duration-300 transform hover:scale-105">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Sign In Now
                                </button>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <a href="#features" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 md:py-4 md:text-lg md:px-10 transition-all duration-300">
                                    Learn More
                                </a>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <!-- Decorative background pattern -->
        <div class="hidden lg:block lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-indigo-50 transform translate-x-1/2"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-blue-100 to-indigo-100 opacity-20"></div>
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAzNGMwLTIuMjA5IDEuNzkxLTQgNC00czQgMS43OTEgNCA0LTEuNzkxIDQtNCA0LTQtMS43OTEtNC00eiIgZmlsbD0iIzRGNjVBNiIgZmlsbC1vcGFjaXR5PSIuMSIvPjwvZz48L3N2Zz4=')] opacity-10"></div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Features</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Everything you need for a healthy lifestyle
                </p>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                    Our comprehensive platform provides all the tools you need to achieve your health and fitness goals.
                </p>
            </div>

            <div class="mt-10">
                <div class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
                    <div class="relative group">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white transform transition-transform duration-300 group-hover:scale-110">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Personalized Goals</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Set and track your fitness goals with customized meal plans.
                        </p>
                    </div>

                    <div class="relative group">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white transform transition-transform duration-300 group-hover:scale-110">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Expert Nutrition</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Get professional meal recommendations based on your dietary needs.
                        </p>
                    </div>

                    <div class="relative group">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white transform transition-transform duration-300 group-hover:scale-110">
                            <i class="fas fa-clock"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Time Management</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Plan your meals efficiently with our smart scheduling system.
                        </p>
                    </div>

                    <div class="relative group">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white transform transition-transform duration-300 group-hover:scale-110">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Progress Tracking</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Monitor your journey with detailed progress reports and analytics.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        function openLoginModal() {
            document.getElementById('loginModal').classList.remove('hidden');
        }

        function closeLoginModal() {
            document.getElementById('loginModal').classList.add('hidden');
        }

        function openRegisterModal() {
            document.getElementById('registerModal').classList.remove('hidden');
        }

        function closeRegisterModal() {
            document.getElementById('registerModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const loginModal = document.getElementById('loginModal');
            const registerModal = document.getElementById('registerModal');
            if (event.target === loginModal) {
                closeLoginModal();
            }
            if (event.target === registerModal) {
                closeRegisterModal();
            }
        }
    </script>
</body>
</html>
