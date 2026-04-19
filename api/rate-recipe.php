<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['recipe_id']) || !isset($data['rating'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

$recipeId = intval($data['recipe_id']);
$rating = intval($data['rating']);
$userId = $_SESSION['user_id'];

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid rating']);
    exit();
}

if (rateRecipe($userId, $recipeId, $rating)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>