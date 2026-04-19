<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

if (empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/signin.php');
}

$user_id = (int)$_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $password = $_POST['password'] ?? '';
        
        // Verify password
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($password, $user['password_hash'])) {
            // Delete user data (cascading deletes will handle related tables)
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param('i', $user_id);
            
            if ($stmt->execute()) {
                session_destroy();
                redirect(BASE_URL . '/account-deleted.php');
            } else {
                $error = 'Failed to delete account. Please try again.';
            }
        } else {
            $error = 'Incorrect password.';
        }
    }
}

$page_title = 'Delete Account';
require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⚠️ Delete Account</h1>
        <p class="page-subtitle">Permanently remove your account and all data</p>
    </div>
</div>

<div style="max-width: 500px; margin: 0 auto;">
    <?php if ($error): ?>
        <div class="flash flash-error"><span class="flash-icon">✕</span><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card" style="border-color: #fecaca; background: #fef2f2;">
        <div class="card-header">
            <div class="card-title" style="color: #dc2626;">DANGER ZONE</div>
        </div>
        
        <p><strong>This action cannot be undone.</strong> This will permanently delete:</p>
        <ul style="margin: 12px 0 12px 20px; color: var(--text2);">
            <li>Your profile and all personal information</li>
            <li>All meal logs and tracking history</li>
            <li>All recipes you've created</li>
            <li>Your weight and water tracking history</li>
            <li>All achievements and badges</li>
        </ul>
        
        <form method="POST" action="" onsubmit="return confirm('ARE YOU ABSOLUTELY SURE? This will permanently delete ALL your data.')">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
            <div class="form-group">
                <label class="form-label">Enter your password to confirm</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-danger w-full">Permanently Delete My Account</button>
        </form>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="settings.php" class="btn btn-secondary">Cancel — Keep My Account</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>