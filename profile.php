<?php
$page_title = 'My Profile';
$active_page = 'profile';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

// Get user's recent activity
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_meals, 
           SUM(calories) as total_calories,
           COUNT(DISTINCT log_date) as active_days
    FROM meal_log 
    WHERE user_id = ?
");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent weight entries
$weight_stmt = $conn->prepare("
    SELECT weight_kg, log_date 
    FROM weight_log 
    WHERE user_id = ? 
    ORDER BY log_date DESC 
    LIMIT 5
");
$weight_stmt->bind_param('i', $current_user_id);
$weight_stmt->execute();
$weight_history = $weight_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get achievements
$ach_stmt = $conn->prepare("
    SELECT badge_key, earned_at 
    FROM achievements 
    WHERE user_id = ? 
    ORDER BY earned_at DESC
");
$ach_stmt->bind_param('i', $current_user_id);
$ach_stmt->execute();
$achievements = $ach_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$bmi = calcBMI($profile['weight_kg'], $profile['height_cm']);
$bmi_info = bmiCategory($bmi);
$weight_change = $profile['weight_kg'] - $profile['goal_weight_kg'];
$weight_status = $weight_change > 0 ? "{$weight_change}kg to go" : ($weight_change < 0 ? abs($weight_change) . "kg below goal" : "At goal weight");

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">👤 My Profile</h1>
        <p class="page-subtitle">Your fitness journey at a glance</p>
    </div>
    <a href="settings.php" class="btn btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
        Settings
    </a>
</div>

<!-- Profile Header - Fixed image size -->
<div class="card" style="text-align: center; margin-bottom: 20px;">
    <div style="position: relative; width: 100px; height: 100px; margin: 0 auto 16px;">
        <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--accent); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; overflow: hidden; margin: 0 auto;">
            <?php if ($profile['profile_photo'] && file_exists(PROFILE_UPLOAD . $profile['profile_photo'])): ?>
                <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($profile['profile_photo']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <?= strtoupper(substr($profile['username'], 0, 2)) ?>
            <?php endif; ?>
        </div>
        <a href="settings.php#photo" style="position: absolute; bottom: 0; right: 0; width: 28px; height: 28px; background: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem; border: 2px solid var(--surface); text-decoration: none;">📷</a>
    </div>
    <h2 style="margin-bottom: 4px;"><?= e($profile['username']) ?></h2>
    <p class="text-muted">Member since <?= date('F Y', strtotime($profile['created_at'])) ?></p>
    
    <div style="display: flex; justify-content: center; gap: 20px; margin-top: 16px; flex-wrap: wrap;">
        <div>
            <div class="stat-value" style="font-size: 1.4rem;"><?= $profile['current_streak'] ?></div>
            <div class="stat-sub">Day Streak 🔥</div>
        </div>
        <div>
            <div class="stat-value" style="font-size: 1.4rem;"><?= $stats['total_meals'] ?? 0 ?></div>
            <div class="stat-sub">Total Meals</div>
        </div>
        <div>
            <div class="stat-value" style="font-size: 1.4rem;"><?= $stats['active_days'] ?? 0 ?></div>
            <div class="stat-sub">Active Days</div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 20px;">
    <!-- Current Statistics -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">📊 Current Statistics</div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div class="flex-between">
                <span class="text-muted">Weight</span>
                <strong><?= $profile['weight_kg'] ?> kg</strong>
            </div>
            <div class="flex-between">
                <span class="text-muted">Goal Weight</span>
                <strong><?= $profile['goal_weight_kg'] ?> kg</strong>
            </div>
            <div class="flex-between">
                <span class="text-muted">Progress</span>
                <strong class="<?= $weight_change > 0 ? 'text-warning' : 'text-success' ?>"><?= $weight_status ?></strong>
            </div>
            <div class="flex-between">
                <span class="text-muted">BMI</span>
                <strong><?= $bmi ?> (<?= $bmi_info[0] ?>)</strong>
            </div>
            <div class="flex-between">
                <span class="text-muted">Daily Calorie Goal</span>
                <strong><?= number_format($profile['daily_calorie_goal']) ?> kcal</strong>
            </div>
            <div class="flex-between">
                <span class="text-muted">Daily Water Goal</span>
                <strong><?= $profile['water_goal'] ?> glasses</strong>
            </div>
        </div>
    </div>

    <!-- Recent Weight History -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚖️ Recent Weight History</div>
            <a href="weight-tracker.php" class="text-muted" style="font-size:.75rem;">View all →</a>
        </div>
        <?php if (empty($weight_history)): ?>
            <div class="text-muted text-center" style="padding: 20px;">No weight entries yet</div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($weight_history as $w): ?>
                <div class="flex-between" style="padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <span><?= date('M d, Y', strtotime($w['log_date'])) ?></span>
                    <strong><?= $w['weight_kg'] ?> kg</strong>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div style="margin-top: 12px;">
            <a href="settings.php" class="btn btn-secondary btn-sm w-full" style="text-align: center;">Update Weight</a>
        </div>
    </div>
</div>

<!-- Achievements -->
<div class="card">
    <div class="card-header">
        <div class="card-title">🏆 Achievements</div>
        <span class="badge badge-purple"><?= count($achievements) ?> earned</span>
    </div>
    
    <?php if (empty($achievements)): ?>
        <div class="text-muted text-center" style="padding: 20px;">
            No achievements yet. Start logging meals to earn badges!
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px;">
            <?php foreach ($achievements as $ach): 
                $badge = badgeInfo($ach['badge_key']);
            ?>
            <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: var(--radius); padding: 12px; text-align: center;">
                <div style="font-size: 2rem;"><?= $badge[0] ?></div>
                <div style="font-weight: 700; font-size: 0.8rem;"><?= $badge[1] ?></div>
                <div style="font-size: 0.7rem; color: var(--text3);"><?= $badge[2] ?></div>
                <div class="text-muted" style="font-size: 0.65rem; margin-top: 6px;">
                    <?= date('M j, Y', strtotime($ach['earned_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>