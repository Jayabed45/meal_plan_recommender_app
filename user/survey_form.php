<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Optional: Fetch existing survey data to pre-fill the form if the user wants to update
$stmt = $conn->prepare("SELECT * FROM surveys WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$survey_data = $result->fetch_assoc();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Survey</title>
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
    </script>
</head>
<body class="bg-gray-50 min-h-screen text-gray-800 font-sans">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm sticky top-0 z-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-primary">Meal Plan Recommender App</h1>
                <a href="user_dashboard.php" 
                   class="inline-flex items-center px-3 sm:px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors duration-200 font-medium text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="hidden sm:inline">Back to Dashboard</span>
                    <span class="sm:hidden">Back</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">Complete Your Nutrition Survey</h2>
            <form method="POST" action="submit_survey.php" class="space-y-4 sm:space-y-6">
                <div>
                    <label for="goal" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Goal</label>
                    <select name="goal" id="goal" required 
                            class="w-full px-3 py-2 text-base sm:text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                        <option value="">-- Select your goal --</option>
                        <option value="lose_weight" <?= ($survey_data['goal'] ?? '') === 'lose_weight' ? 'selected' : '' ?>>Lose Weight</option>
                        <option value="muscle_gain" <?= ($survey_data['goal'] ?? '') === 'muscle_gain' ? 'selected' : '' ?>>Muscle Gain</option>
                        <option value="maintain_weight" <?= ($survey_data['goal'] ?? '') === 'maintain_weight' ? 'selected' : '' ?>>Maintain Weight</option>
                        <option value="other" <?= ($survey_data['goal'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label for="age" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Age</label>
                        <input type="number" name="age" id="age" min="10" max="100" required 
                               class="w-full px-3 py-2 text-base sm:text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors" value="<?= $survey_data['age'] ?? '' ?>">
                    </div>
                    <div>
                        <label for="height_cm" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Height (cm)</label>
                        <input type="number" name="height_cm" id="height_cm" min="50" max="300" required 
                               class="w-full px-3 py-2 text-base sm:text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors" value="<?= $survey_data['height_cm'] ?? '' ?>">
                    </div>
                </div>

                <div>
                    <label for="weight_kg" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Weight (kg)</label>
                    <input type="number" step="0.1" name="weight_kg" id="weight_kg" min="20" max="300" required 
                           class="w-full px-3 py-2 text-base sm:text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors" value="<?= $survey_data['weight_kg'] ?? '' ?>">
                </div>

                <div>
                    <label for="activity_level" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Activity Level</label>
                    <select name="activity_level" id="activity_level" required 
                            class="w-full px-3 py-2 text-base sm:text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                        <option value="">-- Select activity level --</option>
                        <option value="sedentary" <?= ($survey_data['activity_level'] ?? '') === 'sedentary' ? 'selected' : '' ?>>Sedentary</option>
                        <option value="light" <?= ($survey_data['activity_level'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
                        <option value="moderate" <?= ($survey_data['activity_level'] ?? '') === 'moderate' ? 'selected' : '' ?>>Moderate</option>
                        <option value="active" <?= ($survey_data['activity_level'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="very_active" <?= ($survey_data['activity_level'] ?? '') === 'very_active' ? 'selected' : '' ?>>Very Active</option>
                    </select>
                </div>

                <div>
                    <label for="health_conditions" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Health Conditions (Select all that apply)</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="health_conditions[]" id="diabetes" value="diabetes" 
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                   <?= (isset($survey_data['health_conditions']) && strpos($survey_data['health_conditions'], 'diabetes') !== false) ? 'checked' : '' ?>>
                            <label for="diabetes" class="ml-2 text-sm text-gray-700">Diabetes</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="health_conditions[]" id="hypertension" value="hypertension"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                   <?= (isset($survey_data['health_conditions']) && strpos($survey_data['health_conditions'], 'hypertension') !== false) ? 'checked' : '' ?>>
                            <label for="hypertension" class="ml-2 text-sm text-gray-700">Hypertension</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="health_conditions[]" id="heart_disease" value="heart_disease"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                   <?= (isset($survey_data['health_conditions']) && strpos($survey_data['health_conditions'], 'heart_disease') !== false) ? 'checked' : '' ?>>
                            <label for="heart_disease" class="ml-2 text-sm text-gray-700">Heart Disease</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="health_conditions[]" id="celiac" value="celiac"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                   <?= (isset($survey_data['health_conditions']) && strpos($survey_data['health_conditions'], 'celiac') !== false) ? 'checked' : '' ?>>
                            <label for="celiac" class="ml-2 text-sm text-gray-700">Celiac Disease</label>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="dietary_restrictions" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Dietary Restrictions (Select all that apply)</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="dietary_restrictions[]" id="vegetarian" value="vegetarian"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                   <?= (isset($survey_data['dietary_restrictions']) && strpos($survey_data['dietary_restrictions'], 'vegetarian') !== false) ? 'checked' : '' ?>>
                            <label for="vegetarian" class="ml-2 text-sm text-gray-700">Vegetarian</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="dietary_restrictions[]" id="vegan" value="vegan"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                   <?= (isset($survey_data['dietary_restrictions']) && strpos($survey_data['dietary_restrictions'], 'vegan') !== false) ? 'checked' : '' ?>>
                            <label for="vegan" class="ml-2 text-sm text-gray-700">Vegan</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="dietary_restrictions[]" id="gluten_free" value="gluten_free"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                   <?= (isset($survey_data['dietary_restrictions']) && strpos($survey_data['dietary_restrictions'], 'gluten_free') !== false) ? 'checked' : '' ?>>
                            <label for="gluten_free" class="ml-2 text-sm text-gray-700">Gluten-Free</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="dietary_restrictions[]" id="lactose_free" value="lactose_free"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                   <?= (isset($survey_data['dietary_restrictions']) && strpos($survey_data['dietary_restrictions'], 'lactose_free') !== false) ? 'checked' : '' ?>>
                            <label for="lactose_free" class="ml-2 text-sm text-gray-700">Lactose-Free</label>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="food_allergies" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Food Allergies (optional)</label>
                    <textarea name="food_allergies" id="food_allergies" rows="2" 
                              class="w-full px-3 py-2 text-base sm:text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none" 
                              placeholder="List any food allergies (e.g., nuts, shellfish, eggs)..."><?= htmlspecialchars($survey_data['food_allergies'] ?? '') ?></textarea>
                </div>

                <div>
                    <label for="additional_notes" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Additional Notes (optional)</label>
                    <textarea name="additional_notes" id="additional_notes" rows="3" 
                              class="w-full px-3 py-2 text-base sm:text-lg border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none" placeholder="Any other information you'd like to share..."><?= htmlspecialchars($survey_data['additional_notes'] ?? '') ?></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-primary hover:bg-secondary text-white py-3 px-4 rounded-lg font-semibold text-base sm:text-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Submit Survey
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html> 