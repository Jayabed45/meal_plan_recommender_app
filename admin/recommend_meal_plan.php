<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Debug information
error_log("GET parameters: " . print_r($_GET, true));

// Validate parameters
if (!isset($_GET['survey_id']) || !isset($_GET['notif_id'])) {
    error_log("Missing parameters - Survey ID: " . (isset($_GET['survey_id']) ? $_GET['survey_id'] : 'not set') . 
              ", Notification ID: " . (isset($_GET['notif_id']) ? $_GET['notif_id'] : 'not set'));
    $_SESSION['error'] = "Missing required parameters. Please select a survey from the dashboard.";
    header("Location: admin_dashboard.php");
    exit;
}

$survey_id = intval($_GET['survey_id']);
$notif_id = intval($_GET['notif_id']);

if ($survey_id <= 0 || $notif_id <= 0) {
    error_log("Invalid parameters - Survey ID: $survey_id, Notification ID: $notif_id");
    $_SESSION['error'] = "Invalid survey or notification ID.";
    header("Location: admin_dashboard.php");
    exit;
}

// Fetch survey information
$stmt = $conn->prepare("
    SELECT s.*, u.username 
    FROM surveys s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");

if ($stmt === false) {
    error_log("Error preparing survey query: " . $conn->error);
    $_SESSION['error'] = "Database error occurred. Please try again.";
    header("Location: admin_dashboard.php");
    exit;
}

$stmt->bind_param("i", $survey_id);

if (!$stmt->execute()) {
    error_log("Error executing survey query: " . $stmt->error);
    $_SESSION['error'] = "Error fetching survey information.";
    header("Location: admin_dashboard.php");
    exit;
}

$result = $stmt->get_result();
$survey = $result->fetch_assoc();
$stmt->close();

if (!$survey) {
    error_log("Survey not found - ID: $survey_id");
    $_SESSION['error'] = "Survey not found.";
    header("Location: admin_dashboard.php");
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

// Define meal plan descriptions based on goals, health conditions, and age groups
$meal_plan_descriptions = [
    'weight_loss' => "This meal plan is designed for healthy weight loss:\n\nBreakfast (7:00 AM - 8:00 AM):\n- Greek yogurt with berries and a sprinkle of nuts\n- Oatmeal with cinnamon and apple slices\n- Green tea or black coffee\n\nLunch (12:00 PM - 1:00 PM):\n- Grilled chicken breast with mixed vegetables\n- Quinoa or brown rice (1/2 cup)\n- Large salad with olive oil dressing\n- Water or herbal tea\n\nDinner (6:00 PM - 7:00 PM):\n- Baked fish (salmon or tilapia)\n- Steamed vegetables\n- Sweet potato or small portion of whole grain\n- Herbal tea\n\nSnacks (if needed):\n- Apple with 1 tbsp almond butter\n- Carrot sticks with hummus\n- Handful of mixed nuts\n\nTips:\n- Stay hydrated with water throughout the day\n- Limit processed foods and added sugars\n- Focus on protein and fiber-rich foods\n- Practice portion control",

    'muscle_gain' => "This meal plan is optimized for muscle growth:\n\nBreakfast (7:00 AM - 8:00 AM):\n- 3-4 whole eggs with whole grain toast\n- Protein smoothie (banana, protein powder, milk, peanut butter)\n- Oatmeal with honey and nuts\n\nLunch (12:00 PM - 1:00 PM):\n- Grilled chicken breast or lean beef\n- Brown rice or sweet potato\n- Mixed vegetables\n- Greek yogurt\n\nDinner (6:00 PM - 7:00 PM):\n- Salmon or lean meat\n- Quinoa or brown rice\n- Steamed vegetables\n- Avocado or olive oil\n\nPre/Post Workout:\n- Protein shake\n- Banana or apple\n- Greek yogurt with honey\n\nTips:\n- Eat every 3-4 hours\n- Include protein with every meal\n- Stay hydrated\n- Focus on whole foods\n- Consider protein timing around workouts",

    'maintenance' => "This meal plan maintains your current weight:\n\nBreakfast (7:00 AM - 8:00 AM):\n- Whole grain toast with avocado\n- Scrambled eggs or Greek yogurt\n- Fresh fruit\n- Green tea or coffee\n\nLunch (12:00 PM - 1:00 PM):\n- Grilled chicken or fish\n- Mixed salad with olive oil\n- Whole grain wrap or brown rice\n- Fresh vegetables\n\nDinner (6:00 PM - 7:00 PM):\n- Lean protein (chicken, fish, or tofu)\n- Roasted vegetables\n- Quinoa or sweet potato\n- Small portion of healthy fats\n\nSnacks:\n- Greek yogurt with berries\n- Handful of nuts\n- Apple with almond butter\n\nTips:\n- Balance protein, carbs, and healthy fats\n- Stay active throughout the day\n- Listen to your hunger cues\n- Stay hydrated",

    'other' => "This meal plan promotes overall health:\n\nBreakfast (7:00 AM - 8:00 AM):\n- Overnight oats with fruits and nuts\n- Greek yogurt with honey\n- Green smoothie (spinach, banana, berries)\n- Herbal tea\n\nLunch (12:00 PM - 1:00 PM):\n- Mediterranean bowl (quinoa, chickpeas, vegetables)\n- Grilled chicken or fish\n- Large mixed salad\n- Olive oil dressing\n\nDinner (6:00 PM - 7:00 PM):\n- Baked salmon or lean protein\n- Roasted vegetables\n- Brown rice or sweet potato\n- Herbal tea\n\nSnacks:\n- Fresh fruits\n- Raw vegetables with hummus\n- Mixed nuts and seeds\n\nTips:\n- Include a variety of colorful vegetables\n- Choose whole grains over refined\n- Stay hydrated with water\n- Limit processed foods\n- Practice mindful eating",

    // Health Condition Specific Plans
    'diabetes' => "This meal plan is specifically designed for diabetes management:\n\nBreakfast (7:00 AM - 8:00 AM):\n- Steel-cut oats with cinnamon and berries\n- Greek yogurt with chia seeds\n- Whole grain toast with avocado\n- Green tea or black coffee (no sugar)\n\nLunch (12:00 PM - 1:00 PM):\n- Grilled chicken or fish\n- Large mixed salad with olive oil\n- Quinoa or brown rice (1/3 cup)\n- Steamed vegetables\n\nDinner (6:00 PM - 7:00 PM):\n- Baked fish or lean protein\n- Roasted vegetables\n- Small portion of whole grains\n- Herbal tea\n\nSnacks (if needed):\n- Apple with 1 tbsp almond butter\n- Raw vegetables with hummus\n- Handful of nuts\n\nTips:\n- Monitor carbohydrate intake\n- Choose low glycemic index foods\n- Stay hydrated with water\n- Regular meal timing\n- Limit processed foods and added sugars",

    'hypertension' => "This meal plan is designed for blood pressure management:\n\nBreakfast (7:00 AM - 8:00 AM):\n- Oatmeal with banana and walnuts\n- Greek yogurt with berries\n- Whole grain toast\n- Green tea\n\nLunch (12:00 PM - 1:00 PM):\n- Grilled chicken or fish\n- Large salad with olive oil\n- Quinoa or brown rice\n- Steamed vegetables\n\nDinner (6:00 PM - 7:00 PM):\n- Baked fish or lean protein\n- Roasted vegetables\n- Sweet potato\n- Herbal tea\n\nSnacks:\n- Fresh fruits\n- Raw vegetables\n- Unsalted nuts\n\nTips:\n- Limit sodium intake\n- Focus on potassium-rich foods\n- Stay hydrated\n- Regular meal timing\n- Include heart-healthy fats",

    'heart_disease' => "This heart-healthy meal plan:\n\nBreakfast (7:00 AM - 8:00 AM):\n- Oatmeal with berries and walnuts\n- Greek yogurt with honey\n- Whole grain toast with avocado\n- Green tea\n\nLunch (12:00 PM - 1:00 PM):\n- Grilled fish or lean protein\n- Large salad with olive oil\n- Quinoa or brown rice\n- Steamed vegetables\n\nDinner (6:00 PM - 7:00 PM):\n- Baked salmon or lean protein\n- Roasted vegetables\n- Sweet potato\n- Herbal tea\n\nSnacks:\n- Fresh fruits\n- Raw vegetables with hummus\n- Unsalted nuts\n\nTips:\n- Focus on omega-3 rich foods\n- Limit saturated fats\n- Include plenty of vegetables\n- Choose lean proteins\n- Stay hydrated",

    'celiac' => "This gluten-free meal plan:\n\nBreakfast (7:00 AM - 8:00 AM):\n- Gluten-free oats with berries\n- Greek yogurt with honey\n- Gluten-free toast with avocado\n- Green tea\n\nLunch (12:00 PM - 1:00 PM):\n- Grilled chicken or fish\n- Large salad with olive oil\n- Quinoa or brown rice\n- Steamed vegetables\n\nDinner (6:00 PM - 7:00 PM):\n- Baked fish or lean protein\n- Roasted vegetables\n- Sweet potato or quinoa\n- Herbal tea\n\nSnacks:\n- Fresh fruits\n- Raw vegetables with hummus\n- Gluten-free crackers with nut butter\n\nTips:\n- Always check food labels\n- Avoid cross-contamination\n- Focus on naturally gluten-free foods\n- Stay hydrated\n- Regular meal timing",
    
    // Age-Specific Notes/Modifications (can be appended to existing plans)
    'child_notes' => "\n\nNotes for Children:\n- Ensure portion sizes are appropriate for a child.\n- Offer variety and make meals visually appealing.\n- Include healthy snacks between meals.\n- Encourage adequate calcium intake from dairy or fortified alternatives.",
    
    'old_notes' => "\n\nNotes for Older Adults:\n- Focus on nutrient-dense foods.\n- Ensure adequate protein intake to maintain muscle mass.\n- Stay well-hydrated.\n- Consider softer textures if chewing or swallowing is difficult.\n- Ensure sufficient intake of Vitamin D, Calcium, and Vitamin B12.",

    // Vegan-specific meal plans
    'vegan_weight_loss' => "This vegan meal plan is designed for healthy weight loss:\n\nBreakfast (7:00 AM - 8:00 AM):\n- Overnight oats with almond milk, berries, and chia seeds\n- Tofu scramble with vegetables\n- Green tea or black coffee\n\nLunch (12:00 PM - 1:00 PM):\n- Buddha bowl with quinoa, roasted vegetables, and chickpeas\n- Large salad with tahini dressing\n- Water or herbal tea\n\nDinner (6:00 PM - 7:00 PM):\n- Lentil curry with brown rice\n- Steamed vegetables\n- Herbal tea\n\nSnacks (if needed):\n- Apple with 1 tbsp almond butter\n- Carrot sticks with hummus\n- Handful of mixed nuts\n\nTips:\n- Stay hydrated with water throughout the day\n- Focus on plant-based protein sources\n- Include a variety of vegetables\n- Practice portion control",

    'vegan_muscle_gain' => "This vegan meal plan is optimized for muscle growth:\n\nBreakfast (7:00 AM - 8:00 AM):\n- Protein smoothie (banana, plant-based protein powder, almond milk, peanut butter)\n- Tofu scramble with vegetables\n- Oatmeal with nuts and seeds\n\nLunch (12:00 PM - 1:00 PM):\n- Tempeh or seitan stir-fry with brown rice\n- Mixed vegetables\n- Hummus with whole grain pita\n\nDinner (6:00 PM - 7:00 PM):\n- Lentil or chickpea curry\n- Quinoa or brown rice\n- Roasted vegetables\n- Avocado or olive oil\n\nPre/Post Workout:\n- Plant-based protein shake\n- Banana or apple\n- Trail mix with nuts and seeds\n\nTips:\n- Eat every 3-4 hours\n- Include plant-based protein with every meal\n- Stay hydrated\n- Focus on whole foods\n- Consider protein timing around workouts",

    'vegan_maintenance' => "This vegan meal plan maintains your current weight:\n\nBreakfast (7:00 AM - 8:00 AM):\n- Whole grain toast with avocado\n- Tofu scramble or plant-based yogurt\n- Fresh fruit\n- Green tea or coffee\n\nLunch (12:00 PM - 1:00 PM):\n- Buddha bowl with quinoa, vegetables, and legumes\n- Mixed salad with tahini dressing\n- Whole grain wrap or brown rice\n\nDinner (6:00 PM - 7:00 PM):\n- Plant-based protein (tofu, tempeh, or seitan)\n- Roasted vegetables\n- Quinoa or sweet potato\n- Small portion of healthy fats\n\nSnacks:\n- Plant-based yogurt with berries\n- Handful of nuts\n- Apple with almond butter\n\nTips:\n- Balance protein, carbs, and healthy fats\n- Stay active throughout the day\n- Listen to your hunger cues\n- Stay hydrated",
];

// Determine age group
$age = $survey['age'] ?? 0; // Default to 0 if age is not set
$age_group = '';
if ($age > 0 && $age <= 12) {
    $age_group = 'child';
} elseif ($age > 12 && $age <= 60) {
    $age_group = 'mid_age';
} elseif ($age > 60) {
    $age_group = 'old';
}

// Get the appropriate description based on goal, health conditions, and age group
$health_conditions = explode(',', $survey['health_conditions'] ?? '');
$dietary_restrictions = explode(',', $survey['dietary_restrictions'] ?? '');

// Check for dietary restrictions first
$is_vegan = in_array('vegan', array_map('strtolower', $dietary_restrictions));
$is_vegetarian = in_array('vegetarian', array_map('strtolower', $dietary_restrictions));

// Start with an empty description
$default_description = '';

// First, try to find a meal plan that matches both dietary restrictions and health conditions
if (!empty($health_conditions)) {
    foreach ($health_conditions as $condition) {
        $condition_key = str_replace(' ', '_', strtolower(trim($condition)));
        if ($is_vegan && isset($meal_plan_descriptions['vegan_' . $condition_key])) {
            $default_description = $meal_plan_descriptions['vegan_' . $condition_key];
            break;
        } elseif ($is_vegetarian && isset($meal_plan_descriptions['vegetarian_' . $condition_key])) {
            $default_description = $meal_plan_descriptions['vegetarian_' . $condition_key];
            break;
        } elseif (isset($meal_plan_descriptions[$condition_key])) {
            $default_description = $meal_plan_descriptions[$condition_key];
            break;
        }
    }
}

// If no health condition specific plan, try to find a meal plan that matches dietary restrictions and goal
if (empty($default_description)) {
    if ($is_vegan && isset($meal_plan_descriptions['vegan_' . $survey['goal']])) {
        $default_description = $meal_plan_descriptions['vegan_' . $survey['goal']];
    } elseif ($is_vegetarian && isset($meal_plan_descriptions['vegetarian_' . $survey['goal']])) {
        $default_description = $meal_plan_descriptions['vegetarian_' . $survey['goal']];
    } else {
        $default_description = $meal_plan_descriptions[$survey['goal']] ?? $meal_plan_descriptions['maintenance'];
    }
}

// Add age-specific notes
if ($age_group === 'child' && isset($meal_plan_descriptions['child_notes'])) {
    $default_description .= $meal_plan_descriptions['child_notes'];
} elseif ($age_group === 'old' && isset($meal_plan_descriptions['old_notes'])) {
    $default_description .= $meal_plan_descriptions['old_notes'];
}

// Only add dietary restriction notes if we're using a non-restricted meal plan
if (!empty($dietary_restrictions) && !$is_vegan && !$is_vegetarian) {
    $modifications = [];
    foreach ($dietary_restrictions as $restriction) {
        $restriction_key = str_replace(' ', '_', strtolower(trim($restriction)));
        if (isset($meal_plan_descriptions[$restriction_key . '_notes'])) {
            $modifications[] = $meal_plan_descriptions[$restriction_key . '_notes'];
        } else if (in_array($restriction_key, ['vegetarian', 'vegan', 'lactose_free'])) {
            if ($restriction_key === 'vegetarian') {
                $modifications[] = "Note: This meal plan has been modified to be vegetarian. All meat options can be replaced with plant-based proteins like tofu, tempeh, or legumes.";
            } elseif ($restriction_key === 'vegan') {
                $modifications[] = "Note: This meal plan has been modified to be vegan. All animal products have been replaced with plant-based alternatives.";
            } elseif ($restriction_key === 'lactose_free') {
                $modifications[] = "Note: This meal plan has been modified to be lactose-free. Dairy products have been replaced with lactose-free alternatives.";
            }
        } else {
            $modifications[] = "Note: This meal plan should be adjusted to adhere to " . htmlspecialchars(trim($restriction)) . " dietary restrictions.";
        }
    }
    
    if (!empty($modifications)) {
        $default_description .= "\n\n" . implode("\n", $modifications);
    }
}

// Add food allergies warning if present
if (!empty($survey['food_allergies'])) {
    $default_description .= "\n\nIMPORTANT: Please be aware of the following food allergies: " . htmlspecialchars($survey['food_allergies']);
}

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
                            <p class="text-sm text-gray-500">Health Conditions</p>
                            <p class="font-medium text-gray-800"><?= !empty($survey['health_conditions']) ? htmlspecialchars($survey['health_conditions']) : 'None' ?></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-sm text-gray-500">Dietary Restrictions</p>
                            <p class="font-medium text-gray-800"><?= !empty($survey['dietary_restrictions']) ? htmlspecialchars($survey['dietary_restrictions']) : 'None' ?></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-sm text-gray-500">Food Allergies</p>
                            <p class="font-medium text-gray-800"><?= !empty($survey['food_allergies']) ? htmlspecialchars($survey['food_allergies']) : 'None' ?></p>
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
