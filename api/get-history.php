<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

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
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT log_id, meal_name, calories, protein, carbs, fats, meal_time, log_date, logged_at 
    FROM meal_log 
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
    ORDER BY log_date DESC, logged_at DESC
");
$stmt->bind_param('iss', $user_id, $start_date, $end_date);
$stmt->execute();
$meals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'meals' => $meals
]);
?>