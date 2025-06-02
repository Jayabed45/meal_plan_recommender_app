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

$survey_id = intval($_GET['survey_id'] ?? 0);
$notif_id = intval($_GET['notif_id'] ?? 0);

if (!$survey_id || !$notif_id) {
    die("Invalid parameters.");
}

// Fetch survey info + user id
$stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$survey) {
    die("Survey not found.");
}

// Define meal plan descriptions based on goals
$meal_plan_descriptions = [
    'weight_loss' => "This meal plan is designed for healthy weight loss:

Breakfast (7:00 AM - 8:00 AM):
- Greek yogurt with berries and a sprinkle of nuts
- Oatmeal with cinnamon and apple slices
- Green tea or black coffee

Lunch (12:00 PM - 1:00 PM):
- Grilled chicken breast with mixed vegetables
- Quinoa or brown rice (1/2 cup)
- Large salad with olive oil dressing
- Water or herbal tea

Dinner (6:00 PM - 7:00 PM):
- Baked fish (salmon or tilapia)
- Steamed vegetables
- Sweet potato or small portion of whole grain
- Herbal tea

Snacks (if needed):
- Apple with 1 tbsp almond butter
- Carrot sticks with hummus
- Handful of mixed nuts

Tips:
- Stay hydrated with water throughout the day
- Limit processed foods and added sugars
- Focus on protein and fiber-rich foods
- Practice portion control",

    'muscle_gain' => "This meal plan is optimized for muscle growth:

Breakfast (7:00 AM - 8:00 AM):
- 3-4 whole eggs with whole grain toast
- Protein smoothie (banana, protein powder, milk, peanut butter)
- Oatmeal with honey and nuts

Lunch (12:00 PM - 1:00 PM):
- Grilled chicken breast or lean beef
- Brown rice or sweet potato
- Mixed vegetables
- Greek yogurt

Dinner (6:00 PM - 7:00 PM):
- Salmon or lean meat
- Quinoa or brown rice
- Steamed vegetables
- Avocado or olive oil

Pre/Post Workout:
- Protein shake
- Banana or apple
- Greek yogurt with honey

Tips:
- Eat every 3-4 hours
- Include protein with every meal
- Stay hydrated
- Focus on whole foods
- Consider protein timing around workouts",

    'maintenance' => "This meal plan maintains your current weight:

Breakfast (7:00 AM - 8:00 AM):
- Whole grain toast with avocado
- Scrambled eggs or Greek yogurt
- Fresh fruit
- Green tea or coffee

Lunch (12:00 PM - 1:00 PM):
- Grilled chicken or fish
- Mixed salad with olive oil
- Whole grain wrap or brown rice
- Fresh vegetables

Dinner (6:00 PM - 7:00 PM):
- Lean protein (chicken, fish, or tofu)
- Roasted vegetables
- Quinoa or sweet potato
- Small portion of healthy fats

Snacks:
- Greek yogurt with berries
- Handful of nuts
- Apple with almond butter

Tips:
- Balance protein, carbs, and healthy fats
- Stay active throughout the day
- Listen to your hunger cues
- Stay hydrated",

    'general_health' => "This meal plan promotes overall health:

Breakfast (7:00 AM - 8:00 AM):
- Overnight oats with fruits and nuts
- Greek yogurt with honey
- Green smoothie (spinach, banana, berries)
- Herbal tea

Lunch (12:00 PM - 1:00 PM):
- Mediterranean bowl (quinoa, chickpeas, vegetables)
- Grilled chicken or fish
- Large mixed salad
- Olive oil dressing

Dinner (6:00 PM - 7:00 PM):
- Baked salmon or lean protein
- Roasted vegetables
- Brown rice or sweet potato
- Herbal tea

Snacks:
- Fresh fruits
- Raw vegetables with hummus
- Mixed nuts and seeds

Tips:
- Include a variety of colorful vegetables
- Choose whole grains over refined
- Stay hydrated with water
- Limit processed foods
- Practice mindful eating"
];

// Get the appropriate description based on the goal
$default_description = $meal_plan_descriptions[$survey['goal']] ?? $meal_plan_descriptions['general_health'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $breakfast_start = $_POST['breakfast_start'] ?? null;
    $breakfast_end = $_POST['breakfast_end'] ?? null;
    $lunch_start = $_POST['lunch_start'] ?? null;
    $lunch_end = $_POST['lunch_end'] ?? null;
    $dinner_start = $_POST['dinner_start'] ?? null;
    $dinner_end = $_POST['dinner_end'] ?? null;

    if (!$title) {
        $error = "Title is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO meal_plans 
            (admin_id, title, description, breakfast_start, breakfast_end, lunch_start, lunch_end, dinner_start, dinner_end) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssss", $admin_id, $title, $description, 
            $breakfast_start, $breakfast_end, $lunch_start, $lunch_end, $dinner_start, $dinner_end);

        if ($stmt->execute()) {
            $meal_plan_id = $stmt->insert_id;
            
            // Insert into user_meal_plans table
            $user_meal_plan_stmt = $conn->prepare("INSERT INTO user_meal_plans (user_id, meal_plan_id) VALUES (?, ?)");
            $user_meal_plan_stmt->bind_param("ii", $survey['user_id'], $meal_plan_id);
            $user_meal_plan_stmt->execute();
            $user_meal_plan_stmt->close();
            
            // Create notification for user about the new meal plan
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, notify_time) VALUES (?, ?, NOW())");
            $message = "A new meal plan has been recommended for your " . ucwords(str_replace('_', ' ', $survey['goal'])) . " goal!";
            $notif_stmt->bind_param("is", $survey['user_id'], $message);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            // Mark admin notification as read
            $update_stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
            $update_stmt->bind_param("i", $notif_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Failed to recommend meal plan.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Recommend Meal Plan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen font-sans">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg">
        <div class="flex flex-col h-full">
            <div class="p-6 border-b">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                        <i class="fas fa-utensils text-white text-xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">Admin Panel</h1>
                </div>
            </div>
            <nav class="flex-1 p-4">
                <a href="admin_dashboard.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl mb-2 transition-all duration-200">
                    <i class="fas fa-chart-line w-5"></i>
                    <span class="ml-3 font-medium">Dashboard</span>
                </a>
                <a href="../logout.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-all duration-200">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span class="ml-3 font-medium">Logout</span>
                </a>
            </nav>
            <div class="p-4 border-t">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-user text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 capitalize"><?= htmlspecialchars($username) ?></p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <div class="max-w-4xl mx-auto">
            <div class="mb-8">
                <div class="flex items-center space-x-3">
                    <a href="admin_dashboard.php" class="text-gray-600 hover:text-gray-800 transition-colors duration-200">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">Recommend Meal Plan</h2>
                        <p class="text-gray-600 mt-2">Create a personalized meal plan for the user</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start space-x-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-1"></i>
                    <p class="text-red-600 font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Survey Details -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-gray-800">Survey Details</h3>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                            <i class="fas fa-clipboard-list text-white"></i>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">User ID</p>
                                <p class="font-medium text-gray-800"><?= htmlspecialchars($survey['user_id']) ?></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Goal</p>
                                <p class="font-medium text-gray-800 capitalize"><?= htmlspecialchars($survey['goal']) ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Age</p>
                                <p class="font-medium text-gray-800"><?= htmlspecialchars($survey['age']) ?></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Height</p>
                                <p class="font-medium text-gray-800"><?= htmlspecialchars($survey['height_cm']) ?> cm</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Weight</p>
                                <p class="font-medium text-gray-800"><?= htmlspecialchars($survey['weight_kg']) ?> kg</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Activity Level</p>
                                <p class="font-medium text-gray-800 capitalize"><?= htmlspecialchars($survey['activity_level']) ?></p>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-sm text-gray-500">Dietary Restrictions</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($survey['dietary_restrictions']) ?></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-sm text-gray-500">Additional Notes</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($survey['additional_notes']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Meal Plan Form -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-gray-800">Create Meal Plan</h3>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                            <i class="fas fa-utensils text-white"></i>
                        </div>
                    </div>
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-heading text-blue-500 mr-2"></i>Meal Plan Title
                            </label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                placeholder="Enter meal plan title"
                                value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                            >
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-align-left text-blue-500 mr-2"></i>Description
                            </label>
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="4" 
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                placeholder="Enter meal plan description"
                            ><?= htmlspecialchars($_POST['description'] ?? $default_description) ?></textarea>
                            <p class="mt-2 text-sm text-gray-500">
                                <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                                Description is automatically generated based on the user's goal
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center space-x-2 mb-3">
                                    <i class="fas fa-sun text-yellow-500"></i>
                                    <h4 class="font-medium text-gray-800">Breakfast Time</h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Start Time</label>
                                        <input 
                                            type="time" 
                                            name="breakfast_start" 
                                            required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                            value="<?= htmlspecialchars($_POST['breakfast_start'] ?? '') ?>"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">End Time</label>
                                        <input 
                                            type="time" 
                                            name="breakfast_end" 
                                            required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                            value="<?= htmlspecialchars($_POST['breakfast_end'] ?? '') ?>"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center space-x-2 mb-3">
                                    <i class="fas fa-cloud-sun text-orange-500"></i>
                                    <h4 class="font-medium text-gray-800">Lunch Time</h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Start Time</label>
                                        <input 
                                            type="time" 
                                            name="lunch_start" 
                                            required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                            value="<?= htmlspecialchars($_POST['lunch_start'] ?? '') ?>"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">End Time</label>
                                        <input 
                                            type="time" 
                                            name="lunch_end" 
                                            required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                            value="<?= htmlspecialchars($_POST['lunch_end'] ?? '') ?>"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center space-x-2 mb-3">
                                    <i class="fas fa-moon text-indigo-500"></i>
                                    <h4 class="font-medium text-gray-800">Dinner Time</h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Start Time</label>
                                        <input 
                                            type="time" 
                                            name="dinner_start" 
                                            required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                            value="<?= htmlspecialchars($_POST['dinner_start'] ?? '') ?>"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">End Time</label>
                                        <input 
                                            type="time" 
                                            name="dinner_end" 
                                            required 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                            value="<?= htmlspecialchars($_POST['dinner_end'] ?? '') ?>"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-all duration-200 flex items-center space-x-2"
                            >
                                <i class="fas fa-paper-plane"></i>
                                <span>Send Meal Plan</span>
                            </button>
                            <a href="admin_dashboard.php" 
                               class="text-gray-600 hover:text-gray-800 font-medium transition-colors duration-200 flex items-center space-x-2"
                            >
                                <i class="fas fa-times"></i>
                                <span>Cancel</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
