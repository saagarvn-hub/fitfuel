<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

if (!isset($_SESSION['user_id'])) {
    redirect(BASE_URL . '/signin.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/meal-planner.php');
}

if (!verifyCsrf($_POST['csrf'] ?? '')) {
    flash('error', 'Invalid request.');
    redirect(BASE_URL . '/meal-planner.php');
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$plan_date = $_POST['plan_date'] ?? '';
$meal_time = $_POST['meal_time'] ?? '';

if (empty($plan_date) || empty($meal_time)) {
    flash('error', 'Invalid date or meal time.');
    redirect(BASE_URL . '/meal-planner.php');
}

if ($action === 'remove') {
    // Remove from plan
    $stmt = $conn->prepare("DELETE FROM meal_plan WHERE user_id = ? AND plan_date = ? AND meal_time = ?");
    $stmt->bind_param('iss', $user_id, $plan_date, $meal_time);
    $stmt->execute();
    flash('success', 'Meal removed from plan.');
} else {
    // Save to plan
    $recipe_id = !empty($_POST['recipe_id']) ? (int)$_POST['recipe_id'] : null;
    $custom_name = trim($_POST['custom_name'] ?? '');
    $custom_calories = (int)($_POST['custom_calories'] ?? 0);
    
    if ($recipe_id) {
        // Get recipe info
        $stmt = $conn->prepare("SELECT title, calories FROM recipes WHERE recipe_id = ?");
        $stmt->bind_param('i', $recipe_id);
        $stmt->execute();
        $recipe = $stmt->get_result()->fetch_assoc();
        $meal_name = $recipe['title'];
        $calories = $recipe['calories'];
    } elseif (!empty($custom_name) && $custom_calories > 0) {
        $meal_name = $custom_name;
        $calories = $custom_calories;
        $recipe_id = null;
    } else {
        flash('error', 'Please select a recipe or enter a custom meal.');
        redirect(BASE_URL . '/meal-planner.php');
    }
    
    // Upsert into meal_plan
    $stmt = $conn->prepare("
        INSERT INTO meal_plan (user_id, recipe_id, meal_name, plan_date, meal_time, calories) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            recipe_id = VALUES(recipe_id),
            meal_name = VALUES(meal_name),
            calories = VALUES(calories)
    ");
    $stmt->bind_param('iisssi', $user_id, $recipe_id, $meal_name, $plan_date, $meal_time, $calories);
    
    if ($stmt->execute()) {
        flash('success', 'Meal plan updated!');
    } else {
        flash('error', 'Failed to save plan.');
    }
}

redirect(BASE_URL . '/meal-planner.php?week=' . $plan_date);
?>