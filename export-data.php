<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/signin.php');
}

$user_id = (int)$_SESSION['user_id'];

// Get user profile
$stmt = $conn->prepare("SELECT u.username, u.email, u.created_at, p.* FROM users u JOIN user_profiles p ON u.user_id=p.user_id WHERE u.user_id=?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Get all meals
$meals = $conn->query("SELECT * FROM meal_log WHERE user_id = $user_id ORDER BY log_date DESC")->fetch_all(MYSQLI_ASSOC);

// Get all recipes
$recipes = $conn->query("SELECT * FROM recipes WHERE user_id = $user_id ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get weight logs
$weight_logs = $conn->query("SELECT * FROM weight_log WHERE user_id = $user_id ORDER BY log_date DESC")->fetch_all(MYSQLI_ASSOC);

// Get water logs
$water_logs = $conn->query("SELECT * FROM water_log WHERE user_id = $user_id ORDER BY log_date DESC")->fetch_all(MYSQLI_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="fitfuel_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Section 1: User Info
fputcsv($output, ['=== FITFUEL DATA EXPORT ===']);
fputcsv($output, ['Export Date:', date('Y-m-d H:i:s')]);
fputcsv($output, []);
fputcsv($output, ['=== USER PROFILE ===']);
fputcsv($output, ['Username:', $profile['username']]);
fputcsv($output, ['Email:', $profile['email']]);
fputcsv($output, ['Member Since:', $profile['created_at']]);
fputcsv($output, ['Current Weight:', $profile['weight_kg'] . ' kg']);
fputcsv($output, ['Goal Weight:', $profile['goal_weight_kg'] . ' kg']);
fputcsv($output, ['Height:', $profile['height_cm'] . ' cm']);
fputcsv($output, ['Daily Calorie Goal:', $profile['daily_calorie_goal'] . ' kcal']);
fputcsv($output, ['Protein Goal:', $profile['protein_goal'] . ' g']);
fputcsv($output, ['Carbs Goal:', $profile['carbs_goal'] . ' g']);
fputcsv($output, ['Fats Goal:', $profile['fats_goal'] . ' g']);
fputcsv($output, ['Current Streak:', $profile['current_streak'] . ' days']);
fputcsv($output, ['Longest Streak:', $profile['longest_streak'] . ' days']);
fputcsv($output, []);

// Section 2: Meal Logs
fputcsv($output, ['=== MEAL LOGS ===']);
fputcsv($output, ['Log ID', 'Date', 'Meal Time', 'Meal Name', 'Calories', 'Protein (g)', 'Carbs (g)', 'Fats (g)', 'Fiber (g)', 'Servings', 'Notes']);
foreach ($meals as $meal) {
    fputcsv($output, [
        $meal['log_id'],
        $meal['log_date'],
        $meal['meal_time'],
        $meal['meal_name'],
        $meal['calories'],
        $meal['protein'],
        $meal['carbs'],
        $meal['fats'],
        $meal['fiber'],
        $meal['servings'],
        $meal['notes']
    ]);
}
fputcsv($output, []);

// Section 3: Recipes
fputcsv($output, ['=== MY RECIPES ===']);
fputcsv($output, ['Recipe ID', 'Title', 'Meal Type', 'Calories', 'Protein (g)', 'Carbs (g)', 'Fats (g)', 'Fiber (g)', 'Public', 'Created At']);
foreach ($recipes as $recipe) {
    fputcsv($output, [
        $recipe['recipe_id'],
        $recipe['title'],
        $recipe['meal_type'],
        $recipe['calories'],
        $recipe['protein'],
        $recipe['carbs'],
        $recipe['fats'],
        $recipe['fiber'],
        $recipe['is_public'] ? 'Yes' : 'No',
        $recipe['created_at']
    ]);
}
fputcsv($output, []);

// Section 4: Weight Logs
fputcsv($output, ['=== WEIGHT LOGS ===']);
fputcsv($output, ['Date', 'Weight (kg)', 'Notes']);
foreach ($weight_logs as $weight) {
    fputcsv($output, [
        $weight['log_date'],
        $weight['weight_kg'],
        $weight['notes'] ?? ''
    ]);
}
fputcsv($output, []);

// Section 5: Water Logs
fputcsv($output, ['=== WATER LOGS ===']);
fputcsv($output, ['Date', 'Glasses']);
foreach ($water_logs as $water) {
    fputcsv($output, [
        $water['log_date'],
        $water['glasses']
    ]);
}

fclose($output);
exit;
?>