<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'unauthorized', 'message' => 'Please log in']);
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'invalid_method', 'message' => 'Use POST method']);
    exit;
}

// Verify CSRF token
$csrf = $_POST['csrf'] ?? '';
if (!verifyCsrf($csrf)) {
    echo json_encode(['error' => 'invalid_csrf', 'message' => 'Security check failed']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$meal_id = (int)($_POST['meal_id'] ?? 0);

if ($meal_id <= 0) {
    echo json_encode(['error' => 'invalid_meal', 'message' => 'Invalid meal ID']);
    exit;
}

// Verify meal belongs to user
$check = $conn->prepare("SELECT user_id, image_path FROM meal_log WHERE log_id = ?");
$check->bind_param('i', $meal_id);
$check->execute();
$meal = $check->get_result()->fetch_assoc();

if (!$meal) {
    echo json_encode(['error' => 'not_found', 'message' => 'Meal not found']);
    exit;
}

if ($meal['user_id'] != $user_id) {
    echo json_encode(['error' => 'unauthorized', 'message' => 'You don\'t own this meal']);
    exit;
}

// Create upload directory if not exists
$upload_dir = __DIR__ . '/uploads/meals/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'no_file', 'message' => 'Please select an image']);
    exit;
}

// Upload the image
$upload = uploadImage($_FILES['image'], $upload_dir, 2097152); // 2MB max

if (isset($upload['error'])) {
    echo json_encode(['error' => 'upload_failed', 'message' => $upload['error']]);
    exit;
}

// Delete old image if exists
if ($meal['image_path'] && file_exists($upload_dir . $meal['image_path'])) {
    unlink($upload_dir . $meal['image_path']);
}

// Update database
$update = $conn->prepare("UPDATE meal_log SET image_path = ? WHERE log_id = ?");
$update->bind_param('si', $upload['name'], $meal_id);

if ($update->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Image uploaded successfully!',
        'image_path' => BASE_URL . '/uploads/meals/' . $upload['name']
    ]);
} else {
    echo json_encode(['error' => 'database_error', 'message' => 'Failed to save image path']);
}
?>