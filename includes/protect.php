<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . BASE_URL . '/signin.php');
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];

// Fetch user profile
$stmt = $conn->prepare("
    SELECT u.user_id, u.username, u.email, u.created_at,
           p.* 
    FROM users u 
    LEFT JOIN user_profiles p ON u.user_id = p.user_id 
    WHERE u.user_id = ?
");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// If profile doesn't exist, create default profile
if (!$profile) {
    $stmt = $conn->prepare("
        INSERT INTO user_profiles (user_id, weight_kg, goal_weight_kg, height_cm, age, gender, goal_mode, activity_level) 
        VALUES (?, 70, 65, 170, 25, 'male', 'maintain', 'moderate')
    ");
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    
    // Fetch the newly created profile
    $stmt = $conn->prepare("
        SELECT u.user_id, u.username, u.email, u.created_at,
               p.* 
        FROM users u 
        LEFT JOIN user_profiles p ON u.user_id = p.user_id 
        WHERE u.user_id = ?
    ");
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
}
?>