<?php
session_start();
session_destroy();

$page_title = 'Account Deleted';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deleted — FitFuel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
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
    </div>
</div>
<div id="app" style="opacity:0;transition:opacity .4s">
<div class="auth-page">
    <div class="auth-card" style="text-align: center;">
        <div style="font-size: 4rem; margin-bottom: 20px;">👋</div>
        <h1 class="auth-title">Account Deleted</h1>
        <p class="auth-subtitle">We're sorry to see you go. Your data has been permanently removed.</p>
        <div style="margin-top: 24px;">
            <a href="signin.php" class="btn btn-primary">Return to Sign In</a>
            <a href="register.php" class="btn btn-secondary" style="margin-left: 10px;">Create New Account</a>
        </div>
    </div>
</div>
</div>
<script src="assets/js/script.js"></script>
<script>
    window.addEventListener('load', () => {
        setTimeout(() => {
            document.getElementById('loader')?.classList.add('hidden');
            document.getElementById('app').style.opacity = '1';
        }, 300);
    });
</script>
</body>
</html>