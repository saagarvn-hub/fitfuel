<?php
$page_title = 'Change Password';
$active_page = 'settings';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($current) || empty($new) || empty($confirm)) {
            $error = 'Please fill in all fields.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param('i', $current_user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if (password_verify($current, $user['password_hash'])) {
                $new_hash = password_hash($new, PASSWORD_BCRYPT);
                $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $update->bind_param('si', $new_hash, $current_user_id);
                
                if ($update->execute()) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to update password.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🔐 Change Password</h1>
        <p class="page-subtitle">Update your account password</p>
    </div>
    <a href="settings.php" class="btn btn-secondary">← Back to Settings</a>
</div>

<div style="max-width: 500px; margin: 0 auto;">
    <?php if ($success): ?>
        <div class="flash flash-success"><span class="flash-icon">✓</span><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash flash-error"><span class="flash-icon">✕</span><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card">
        <form method="POST" action="">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
                <div class="form-hint">Minimum 6 characters</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-full">Update Password</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>