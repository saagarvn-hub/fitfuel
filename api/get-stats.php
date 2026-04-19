<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

if (empty($_GET['api_key'])) {
    echo json_encode(['error' => 'API key required']);
    exit;
}

// Simple API key check (you can expand this)
$api_key = $_GET['api_key'];
$stmt = $conn->prepare("SELECT user_id FROM users WHERE api_key = ?");
$stmt->bind_param('s', $api_key);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$user_id = $user['user_id'];
$today = date('Y-m-d');

$totals = getTodayTotals($conn, $user_id, $today);
$profile_stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$profile_stmt->bind_param('i', $user_id);
$profile_stmt->execute();
$profile = $profile_stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'date' => $today,
    'calories' => [
        'consumed' => $totals['cal'],
        'goal' => $profile['daily_calorie_goal'],
        'remaining' => $profile['daily_calorie_goal'] - $totals['cal']
    ],
    'macros' => [
        'protein' => ['current' => $totals['pro'], 'goal' => $profile['protein_goal']],
        'carbs' => ['current' => $totals['carbs'], 'goal' => $profile['carbs_goal']],
        'fats' => ['current' => $totals['fats'], 'goal' => $profile['fats_goal']]
    ],
    'streak' => $profile['current_streak']
]);
?>