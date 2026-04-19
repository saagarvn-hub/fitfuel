<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

// If already logged in, go to dashboard
if (!empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        
        if (empty($email) || empty($pass)) {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $conn->prepare("SELECT user_id, password_hash FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if ($user && password_verify($pass, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                
                $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/index.php';
                unset($_SESSION['redirect_after_login']);
                redirect($redirect);
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

$page_title = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sign In — FitFuel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="margin: 0; padding: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg);">

<div id="loader">
    <div class="loader-inner">
        <div class="loader-logo">
            <svg width="48" height="48" viewBox="0 0 40 40" fill="none">
                <rect width="40" height="40" rx="12" fill="#4f46e5"/>
                <path d="M20 8C14.5 8 10 12.5 10 18c0 7 10 18 10 18s10-11 10-18c0-5.5-4.5-10-10-10z" fill="white"/>
                <circle cx="20" cy="17" r="4" fill="#4f46e5"/>
            </svg>
        </div>
        <div class="loader-bar"><div class="loader-fill"></div></div>
        <div class="loader-text">Welcome back...</div>
    </div>
</div>

<div id="app" style="opacity:0; transition:opacity 0.4s; width: 100%; display: flex; justify-content: center; align-items: center; min-height: 100vh;">
<div class="auth-page" style="width: 100%; display: flex; justify-content: center; align-items: center; padding: 24px;">
    <div class="auth-card" style="max-width: 480px; width: 100%; margin: 0 auto;">
        <div class="auth-logo">
            <div class="auth-logo-icon">
                <svg width="26" height="26" viewBox="0 0 40 40" fill="none">
                    <path d="M20 6C13 6 8 11 8 17c0 9 12 20 12 20s12-11 12-20c0-6-5-11-12-11z" fill="white"/>
                    <circle cx="20" cy="16" r="4" fill="#4f46e5"/>
                </svg>
            </div>
            <span style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;color:var(--text)">FitFuel</span>
        </div>
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-subtitle">Sign in to your account to continue tracking.</p>
        
        <?php if ($error): ?>
            <div class="flash flash-error">
                <span class="flash-icon">✕</span><?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
            <div class="form-group">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;margin-top:4px">
                Sign in
            </button>
        </form>
        
        <div class="auth-footer">
            Don't have an account? <a href="<?= BASE_URL ?>/register.php">Create one</a>
        </div>
        <div class="auth-footer" style="margin-top:6px;font-size:.75rem">
            Demo: demo@fitfuel.com / Demo@123
        </div>
    </div>
</div>
</div>

<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script>
    window.addEventListener('load', () => {
        setTimeout(() => {
            const loader = document.getElementById('loader');
            const app = document.getElementById('app');
            if (loader) loader.classList.add('hidden');
            if (app) app.style.opacity = '1';
        }, 300);
    });
</script>
</body>
</html>