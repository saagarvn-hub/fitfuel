<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Please log in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

if (!verifyCsrf($_POST['csrf'] ?? '')) {
    echo json_encode(['error' => 'Security check failed']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$recipe_id = (int)($_POST['recipe_id'] ?? 0);

if ($recipe_id <= 0) {
    echo json_encode(['error' => 'Invalid recipe ID']);
    exit;
}

// Verify user owns this recipe
$check = $conn->prepare("SELECT user_id, image_path FROM recipes WHERE recipe_id = ?");
$check->bind_param('i', $recipe_id);
$check->execute();
$recipe = $check->get_result()->fetch_assoc();

if (!$recipe) {
    echo json_encode(['error' => 'Recipe not found']);
    exit;
}

if ($recipe['user_id'] != $user_id) {
    echo json_encode(['error' => 'You can only edit your own recipes']);
    exit;
}

// Create upload directory if not exists
$upload_dir = __DIR__ . '/uploads/recipes/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Please select an image']);
    exit;
}

$upload = uploadImage($_FILES['image'], $upload_dir, 2097152); // 2MB max

if (isset($upload['error'])) {
    echo json_encode(['error' => $upload['error']]);
    exit;
}

// Delete old image if exists
if ($recipe['image_path'] && file_exists($upload_dir . $recipe['image_path'])) {
    unlink($upload_dir . $recipe['image_path']);
}

// Update database
$update = $conn->prepare("UPDATE recipes SET image_path = ? WHERE recipe_id = ?");
$update->bind_param('si', $upload['name'], $recipe_id);

if ($update->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Image updated!',
        'image_path' => BASE_URL . '/uploads/recipes/' . $upload['name']
    ]);
} else {
    echo json_encode(['error' => 'Database update failed']);
}
?>