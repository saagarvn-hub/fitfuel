<?php
$page_title = 'Achievements';
$active_page = 'profile';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

// Get all achievements for user
$stmt = $conn->prepare("
    SELECT badge_key, earned_at 
    FROM achievements 
    WHERE user_id = ? 
    ORDER BY earned_at DESC
");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$earned = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$earned_map = [];
foreach ($earned as $e) {
    $earned_map[$e['badge_key']] = $e['earned_at'];
}

// All possible badges
$all_badges = [
    'first_log' => ['🍽️', 'First Bite', 'Logged your first meal'],
    'streak_7' => ['🔥', '7-Day Streak', 'Logged meals 7 days in a row'],
    'streak_10' => ['⚡', '10-Day Streak', 'Logged meals 10 days in a row'],
    'streak_30' => ['🏆', '30-Day Streak', 'Logged meals 30 days in a row'],
    'logged_10_meals' => ['📋', 'Meal Tracker', 'Logged 10 total meals'],
    'logged_50_meals' => ['🥗', 'Nutrition Pro', 'Logged 50 total meals'],
    'logged_100_meals' => ['💪', 'Century Club', 'Logged 100 total meals'],
];

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🏆 Achievements</h1>
        <p class="page-subtitle"><?= count($earned) ?> / <?= count($all_badges) ?> badges earned</p>
    </div>
</div>

<!-- Progress Bar -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <div class="card-title">Your Journey Progress</div>
    </div>
    <div class="progress-wrap">
        <div class="progress-header">
            <span>Badges Collected</span>
            <span><?= count($earned) ?> / <?= count($all_badges) ?></span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill indigo" data-pct="<?= round(count($earned) / count($all_badges) * 100) ?>" style="width: 0;"></div>
        </div>
    </div>
</div>

<!-- Badges Grid -->
<div class="badges-grid" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));">
    <?php foreach ($all_badges as $key => $badge): 
        $earned_at = $earned_map[$key] ?? null;
        $is_locked = !$earned_at;
    ?>
        <div class="badge-card <?= $is_locked ? 'locked' : '' ?>">
            <div class="badge-emoji"><?= $badge[0] ?></div>
            <div class="badge-name"><?= $badge[1] ?></div>
            <div class="badge-desc"><?= $badge[2] ?></div>
            <?php if ($earned_at): ?>
                <div class="text-muted" style="font-size: .65rem; margin-top: 8px;">
                    Earned <?= date('M j, Y', strtotime($earned_at)) ?>
                </div>
            <?php else: ?>
                <div class="text-muted" style="font-size: .65rem; margin-top: 8px;">
                    🔒 Not yet earned
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>