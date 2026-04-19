<?php
$page_title = 'About';
$active_page = 'account';
require_once 'includes/config.php';
require_once 'includes/utils.php';

// Check if logged in for header
$is_logged_in = !empty($_SESSION['user_id']);
if ($is_logged_in) {
    $current_user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT u.username, p.* FROM users u LEFT JOIN user_profiles p ON u.user_id=p.user_id WHERE u.user_id=?");
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
}

require_once 'includes/header.php';
?>

<div class="about-hero">
    <h1>FitFuel</h1>
    <p style="font-size: 1.1rem; max-width: 500px; margin: 0 auto;">Your personal nutrition companion for a healthier lifestyle</p>
</div>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <div class="card-title">Our Mission</div>
        </div>
        <p>FitFuel was created to help people take control of their nutrition without the complexity. We believe that tracking what you eat should be simple, insightful, and motivating.</p>
    </div>
    
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <div class="card-title">Features</div>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <div class="feature-title">Calorie Tracking</div>
                <div class="feature-desc">Log meals and track daily calories</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🥗</div>
                <div class="feature-title">Recipe Library</div>
                <div class="feature-desc">Save and share healthy recipes</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💧</div>
                <div class="feature-title">Water Tracking</div>
                <div class="feature-desc">Stay hydrated throughout the day</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚖️</div>
                <div class="feature-title">Weight Progress</div>
                <div class="feature-desc">Track your weight loss journey</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <div class="feature-title">Achievements</div>
                <div class="feature-desc">Earn badges for consistency</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <div class="feature-title">Meal Planner</div>
                <div class="feature-desc">Plan your week ahead</div>
            </div>
        </div>
    </div>
    
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <div class="card-title">Technology</div>
        </div>
        <p>Built with PHP, MySQL, HTML5, CSS3, and JavaScript. Uses Chart.js for beautiful data visualization and follows modern responsive design principles.</p>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">Contact</div>
        </div>
        <p>Have questions or suggestions? Reach out to us at <strong>support@fitfuel.com</strong> or use the <a href="feedback.php">feedback form</a>.</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>