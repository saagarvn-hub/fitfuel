<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/signin.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/settings.php');
}

if (!verifyCsrf($_POST['csrf'] ?? '')) {
    flash('error', 'Invalid request.');
    redirect(BASE_URL . '/settings.php');
}

$user_id = (int)$_SESSION['user_id'];

if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $upload = uploadImage($_FILES['profile_photo'], PROFILE_UPLOAD, 2097152); // 2MB
    
    if (isset($upload['error'])) {
        flash('error', $upload['error']);
    } else {
        // Get old photo
        $stmt = $conn->prepare("SELECT profile_photo FROM user_profiles WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        
        if ($old['profile_photo'] && file_exists(PROFILE_UPLOAD . $old['profile_photo'])) {
            unlink(PROFILE_UPLOAD . $old['profile_photo']);
        }
        
        $update = $conn->prepare("UPDATE user_profiles SET profile_photo = ? WHERE user_id = ?");
        $update->bind_param('si', $upload['name'], $user_id);
        
        if ($update->execute()) {
            flash('success', 'Profile photo updated!');
        } else {
            flash('error', 'Failed to save photo.');
        }
    }
} else {
    flash('error', 'Please select a file to upload.');
}

redirect(BASE_URL . '/settings.php');
?>