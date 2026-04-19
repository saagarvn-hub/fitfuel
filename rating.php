<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'unauthorized',
        'message' => 'Please log in to rate recipes'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'invalid_method',
        'message' => 'Only POST requests are allowed'
    ]);
    exit;
}

// Verify CSRF token
if (!verifyCsrf($_POST['csrf'] ?? '')) {
    echo json_encode([
        'success' => false,
        'error' => 'invalid_csrf',
        'message' => 'Security validation failed. Please refresh the page and try again.'
    ]);
    exit;
}

// Get and validate input
$user_id = (int)$_SESSION['user_id'];
$recipe_id = (int)($_POST['recipe_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);

if ($recipe_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'invalid_recipe',
        'message' => 'Invalid recipe ID'
    ]);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'error' => 'invalid_rating',
        'message' => 'Rating must be between 1 and 5 stars'
    ]);
    exit;
}

// Check if recipe exists and is accessible (public OR owned by user)
$check = $conn->prepare("SELECT recipe_id, title FROM recipes WHERE recipe_id = ? AND (is_public = 1 OR user_id = ?)");
$check->bind_param('ii', $recipe_id, $user_id);
$check->execute();
$recipe = $check->get_result()->fetch_assoc();

if (!$recipe) {
    echo json_encode([
        'success' => false,
        'error' => 'recipe_not_found',
        'message' => 'Recipe not found or you don\'t have permission to rate it'
    ]);
    exit;
}

// Insert or update rating
$stmt = $conn->prepare("
    INSERT INTO recipe_ratings (recipe_id, user_id, rating) 
    VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE rating = VALUES(rating), created_at = CURRENT_TIMESTAMP
");
$stmt->bind_param('iii', $recipe_id, $user_id, $rating);

if ($stmt->execute()) {
    // Get updated average rating and count
    $avg_stmt = $conn->prepare("
        SELECT COALESCE(AVG(rating), 0) as avg_rating, 
               COUNT(*) as rating_count 
        FROM recipe_ratings 
        WHERE recipe_id = ?
    ");
    $avg_stmt->bind_param('i', $recipe_id);
    $avg_stmt->execute();
    $avg_result = $avg_stmt->get_result()->fetch_assoc();
    
    // Get user's rating to confirm
    $user_stmt = $conn->prepare("SELECT rating FROM recipe_ratings WHERE recipe_id = ? AND user_id = ?");
    $user_stmt->bind_param('ii', $recipe_id, $user_id);
    $user_stmt->execute();
    $user_rating = $user_stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => '⭐ Rating saved successfully!',
        'recipe_id' => $recipe_id,
        'recipe_title' => $recipe['title'],
        'user_rating' => $user_rating['rating'],
        'avg_rating' => round($avg_result['avg_rating'], 1),
        'rating_count' => (int)$avg_result['rating_count']
    ]);
} else {
    error_log("Rating save failed: " . $conn->error);
    echo json_encode([
        'success' => false,
        'error' => 'database_error',
        'message' => 'Failed to save rating. Please try again.'
    ]);
}
?>