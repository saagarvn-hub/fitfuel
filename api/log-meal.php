<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/utils.php';

// API Key Authentication
$api_key = $_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
if (empty($api_key)) {
    echo json_encode(['error' => 'API key required']);
    exit;
}

$stmt = $conn->prepare("SELECT user_id FROM users WHERE api_key = ?");
$stmt->bind_param('s', $api_key);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$user_id = $user['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

$meal_name = $input['meal_name'] ?? '';
$calories = (int)($input['calories'] ?? 0);
$protein = (float)($input['protein'] ?? 0);
$carbs = (float)($input['carbs'] ?? 0);
$fats = (float)($input['fats'] ?? 0);
$meal_time = $input['meal_time'] ?? 'snack';
$log_date = $input['log_date'] ?? date('Y-m-d');

if (empty($meal_name) || $calories <= 0) {
    echo json_encode(['error' => 'Meal name and calories required']);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO meal_log (user_id, meal_name, calories, protein, carbs, fats, meal_time, log_date) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('isidddss', $user_id, $meal_name, $calories, $protein, $carbs, $fats, $meal_time, $log_date);

if ($stmt->execute()) {
    updateStreak($conn, $user_id);
    echo json_encode(['success' => true, 'message' => 'Meal logged successfully', 'log_id' => $conn->insert_id]);
} else {
    echo json_encode(['error' => 'Failed to log meal']);
}
?>