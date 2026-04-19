<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/signin.php');
}

if (!verifyCsrf($_GET['csrf'] ?? '')) {
    flash('error', 'Invalid request');
    redirect(BASE_URL . '/settings.php');
}

$user_id = (int)$_SESSION['user_id'];

// Get current profile photo
$stmt = $conn->prepare("SELECT profile_photo FROM user_profiles WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if ($profile['profile_photo'] && file_exists(PROFILE_UPLOAD . $profile['profile_photo'])) {
    unlink(PROFILE_UPLOAD . $profile['profile_photo']);
}

// Remove from database
$update = $conn->prepare("UPDATE user_profiles SET profile_photo = NULL WHERE user_id = ?");
$update->bind_param('i', $user_id);
$update->execute();

flash('success', 'Profile photo removed successfully');
redirect(BASE_URL . '/settings.php');
?>