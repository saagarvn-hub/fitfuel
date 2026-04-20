<?php
// ============================================
// MAIN HEADER + SIDEBAR LAYOUT
// ============================================
if (!isset($page_title)) $page_title = 'FitFuel';
if (!isset($active_page)) $active_page = 'dashboard';

// Check if we should show back button
$show_back_button = false;
$pages_with_back = ['planner', 'view', 'recipe-detail', 'edit-recipe', 'add-recipe', 'settings', 'profile', 'achievements', 'feedback', 'contact', 'health-report', 'report'];
if (in_array($active_page, $pages_with_back)) {
    $show_back_button = true;
}

// Get user data for sidebar if logged in
$sidebar_user = null;
if (isset($current_user_id) && isset($profile) && $profile) {
    $sidebar_user = $profile;
}

// Get CSS version for cache busting (forces fresh load on all devices)
$css_file = $_SERVER['DOCUMENT_ROOT'] . '/assets/css/style.css';
$css_version = file_exists($css_file) ? filemtime($css_file) : '1';

// Define navigation items
$nav_items = [
    'dashboard' => ['📊', 'Dashboard', 'index.php'],
    'diary' => ['📋', 'Meal Diary', 'diary.php'],
    'dishes' => ['🥗', 'Recipes', 'dishes.php'],
    'planner' => ['📅', 'Meal Planner', 'meal-planner.php'],
    'weight-tracker' => ['⚖️', 'Weight Tracker', 'weightlog.php'],
    'water-tracker' => ['💧', 'Water Tracker', 'waterlog.php'],
];

$bottom_nav_items = [
    'profile' => ['👤', 'Profile', 'profile.php'],
    'settings' => ['⚙️', 'Settings', 'settings.php'],
    'achievements' => ['🏆', 'Achievements', 'achievements.php'],
    'feedback' => ['💬', 'Feedback', 'feedback.php'],
    'about' => ['ℹ️', 'About', 'about.php'],
    'contact' => ['📞', 'Contact', 'contact.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($page_title) ?> — FitFuel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS with cache busting - forces fresh load on all devices -->
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= $css_version ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('fitfuel_theme');
            if (savedTheme === 'dark') {
                const root = document.documentElement;
                root.style.setProperty('--bg', '#0f0f1a');
                root.style.setProperty('--surface', '#1a1a2e');
                root.style.setProperty('--surface2', '#16213e');
                root.style.setProperty('--border', '#2a2a4e');
                root.style.setProperty('--border2', '#3a3a5e');
                root.style.setProperty('--text', '#ffffff');
                root.style.setProperty('--text2', '#a0a0c0');
                root.style.setProperty('--text3', '#707090');
                root.style.setProperty('--accent-dim', '#2d2a5e');
            }
        })();
    </script>
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
        <div class="loader-text">Loading your wellness journey...</div>
    </div>
</div>

<div id="app" style="opacity:0; transition:opacity 0.4s">

<!-- Sidebar Overlay (mobile) -->
<div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <svg width="22" height="22" viewBox="0 0 40 40" fill="none">
                    <path d="M20 6C13 6 8 11 8 17c0 9 12 20 12 20s12-11 12-20c0-6-5-11-12-11z" fill="white"/>
                    <circle cx="20" cy="16" r="4" fill="#4f46e5"/>
                </svg>
            </div>
            <span class="logo-text">FitFuel</span>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">✕</button>
    </div>

    <?php if (isset($sidebar_user) && $sidebar_user): ?>
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php if (!empty($sidebar_user['profile_photo']) && file_exists(PROFILE_UPLOAD . $sidebar_user['profile_photo'])): ?>
                <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($sidebar_user['profile_photo']) ?>" alt="">
            <?php else: ?>
                <?= strtoupper(substr($sidebar_user['username'] ?? 'U', 0, 2)) ?>
            <?php endif; ?>
        </div>
        <div class="sidebar-user-info">
            <span class="sidebar-username"><?= htmlspecialchars($sidebar_user['username'] ?? 'User') ?></span>
            <span class="sidebar-goal"><?= ucfirst($sidebar_user['goal_mode'] ?? 'maintain') ?> weight</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="sidebar-nav">
        <div class="nav-section-label">MAIN</div>
        <?php foreach ($nav_items as $key => $item): ?>
            <a href="<?= BASE_URL ?>/<?= $item[2] ?>" class="nav-item <?= $active_page == $key ? 'active' : '' ?>">
                <span><?= $item[0] ?></span>
                <span><?= $item[1] ?></span>
            </a>
        <?php endforeach; ?>

        <div class="nav-section-label" style="margin-top: 12px;">ANALYTICS</div>
        <a href="<?= BASE_URL ?>/health-report.php" class="nav-item"><span>📈</span><span>Health Report</span></a>
        <a href="<?= BASE_URL ?>/report.php" class="nav-item"><span>📊</span><span>Progress</span></a>

        <div class="nav-section-label" style="margin-top: 12px;">ACCOUNT</div>
        <?php foreach ($bottom_nav_items as $key => $item): ?>
            <a href="<?= BASE_URL ?>/<?= $item[2] ?>" class="nav-item <?= $active_page == $key ? 'active' : '' ?>">
                <span><?= $item[0] ?></span>
                <span><?= $item[1] ?></span>
            </a>
        <?php endforeach; ?>

        <div class="nav-section-label" style="margin-top: 12px;">APPEARANCE</div>
        <button class="nav-item" onclick="toggleTheme()" style="width:100%; text-align:left; background:none; border:none; cursor:pointer;">
            <span>🌓</span><span>Dark / Light Mode</span>
        </button>

        <div class="nav-section-label" style="margin-top: 12px;">HELP</div>
        <a href="<?= BASE_URL ?>/contact.php" class="nav-item"><span>📞</span><span>Contact</span></a>
        <a href="<?= BASE_URL ?>/logout.php" class="nav-item nav-logout" onclick="return confirm('Log out?')"><span>🚪</span><span>Logout</span></a>
    </div>
</aside>

<div class="main-content">
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="mobile-logo">
            <?php if ($show_back_button): ?>
            <button class="mobile-back-btn" onclick="history.back()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <?php endif; ?>
            <button class="mobile-hamburger" onclick="toggleSidebar()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:1rem; margin-left:8px;">FitFuel</span>
        </div>
    </div>

    <?= renderFlash() ?>