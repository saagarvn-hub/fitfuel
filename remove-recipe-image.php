<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Please log in']);
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

// Delete image file
$upload_dir = __DIR__ . '/uploads/recipes/';
if ($recipe['image_path'] && file_exists($upload_dir . $recipe['image_path'])) {
    unlink($upload_dir . $recipe['image_path']);
}

// Update database
$update = $conn->prepare("UPDATE recipes SET image_path = NULL WHERE recipe_id = ?");
$update->bind_param('i', $recipe_id);

if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Image removed']);
} else {
    echo json_encode(['error' => 'Database update failed']);
}
?>