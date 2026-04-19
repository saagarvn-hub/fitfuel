<?php
$page_title = 'Health Report';
$active_page = 'analytics';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

// Get last 30 days of data
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Get meal summary
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(calories), 0) as total_calories,
        COALESCE(SUM(protein), 0) as total_protein,
        COALESCE(SUM(carbs), 0) as total_carbs,
        COALESCE(SUM(fats), 0) as total_fats,
        COUNT(*) as meal_count,
        COUNT(DISTINCT log_date) as active_days
    FROM meal_log 
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
");
$stmt->bind_param('iss', $current_user_id, $start_date, $end_date);
$stmt->execute();
$meal_stats = $stmt->get_result()->fetch_assoc();

// Ensure values are not null
$meal_stats['total_calories'] = $meal_stats['total_calories'] ?? 0;
$meal_stats['total_protein'] = $meal_stats['total_protein'] ?? 0;
$meal_stats['total_carbs'] = $meal_stats['total_carbs'] ?? 0;
$meal_stats['total_fats'] = $meal_stats['total_fats'] ?? 0;
$meal_stats['meal_count'] = $meal_stats['meal_count'] ?? 0;
$meal_stats['active_days'] = $meal_stats['active_days'] ?? 0;

// Get average daily calories
$avg_daily = $meal_stats['active_days'] > 0 ? round($meal_stats['total_calories'] / $meal_stats['active_days']) : 0;

// Get water stats
$stmt2 = $conn->prepare("
    SELECT COALESCE(SUM(glasses), 0) as total_glasses, COALESCE(AVG(glasses), 0) as avg_glasses
    FROM water_log 
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
");
$stmt2->bind_param('iss', $current_user_id, $start_date, $end_date);
$stmt2->execute();
$water_stats = $stmt2->get_result()->fetch_assoc();

$water_stats['total_glasses'] = $water_stats['total_glasses'] ?? 0;
$water_stats['avg_glasses'] = $water_stats['avg_glasses'] ?? 0;

// Get weight change (first and last weight in period)
$stmt3 = $conn->prepare("
    SELECT weight_kg, log_date 
    FROM weight_log 
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
    ORDER BY log_date ASC
    LIMIT 1
");
$stmt3->bind_param('iss', $current_user_id, $start_date, $end_date);
$stmt3->execute();
$first_weight = $stmt3->get_result()->fetch_assoc();

$stmt4 = $conn->prepare("
    SELECT weight_kg, log_date 
    FROM weight_log 
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
    ORDER BY log_date DESC
    LIMIT 1
");
$stmt4->bind_param('iss', $current_user_id, $start_date, $end_date);
$stmt4->execute();
$last_weight = $stmt4->get_result()->fetch_assoc();

$weight_change = 0;
if ($first_weight && $last_weight) {
    $weight_change = $last_weight['weight_kg'] - $first_weight['weight_kg'];
} elseif ($first_weight) {
    $weight_change = $profile['weight_kg'] - $first_weight['weight_kg'];
}

// Get streak info
$streak = $profile['current_streak'] ?? 0;
$longest = $profile['longest_streak'] ?? 0;

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📊 Health Report</h1>
        <p class="page-subtitle">Last 30 days summary (<?= date('M d', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?>)</p>
    </div>
    <a href="export-data.php" class="btn btn-secondary">📥 Export Full Data</a>
</div>

<!-- Summary Cards -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Avg Daily Calories</div>
        <div class="stat-value"><?= number_format($avg_daily) ?></div>
        <div class="stat-sub">Goal: <?= number_format($profile['daily_calorie_goal']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Days</div>
        <div class="stat-value"><?= $meal_stats['active_days'] ?></div>
        <div class="stat-sub">out of 30 days</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Meals</div>
        <div class="stat-value"><?= number_format($meal_stats['meal_count']) ?></div>
        <div class="stat-sub">logged this month</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Weight Change</div>
        <div class="stat-value" style="color: <?= $weight_change <= 0 ? '#10b981' : '#ef4444' ?>">
            <?php if ($weight_change > 0): ?>+<?php endif; ?><?= number_format($weight_change, 1) ?> kg
        </div>
        <div class="stat-sub">since first log</div>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 20px;">
    <!-- Nutrition Summary -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">🥗 Nutrition Summary (30 days)</div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div class="flex-between">
                <span>Total Calories</span>
                <strong><?= number_format($meal_stats['total_calories']) ?> kcal</strong>
            </div>
            <div class="flex-between">
                <span>Total Protein</span>
                <strong><?= number_format($meal_stats['total_protein'], 1) ?>g</strong>
            </div>
            <div class="flex-between">
                <span>Total Carbs</span>
                <strong><?= number_format($meal_stats['total_carbs'], 1) ?>g</strong>
            </div>
            <div class="flex-between">
                <span>Total Fats</span>
                <strong><?= number_format($meal_stats['total_fats'], 1) ?>g</strong>
            </div>
        </div>
    </div>
    
    <!-- Hydration Summary -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">💧 Hydration Summary</div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div class="flex-between">
                <span>Total Glasses</span>
                <strong><?= number_format($water_stats['total_glasses']) ?></strong>
            </div>
            <div class="flex-between">
                <span>Daily Average</span>
                <strong><?= number_format($water_stats['avg_glasses'], 1) ?> glasses</strong>
            </div>
            <div class="flex-between">
                <span>Goal</span>
                <strong><?= $profile['water_goal'] ?> glasses/day</strong>
            </div>
        </div>
    </div>
</div>

<!-- Streak & Achievements -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <div class="card-title">🔥 Consistency Report</div>
    </div>
    <div class="flex-between" style="margin-bottom: 10px;">
        <span>Current Streak</span>
        <strong><?= $streak ?> days</strong>
    </div>
    <div class="progress-bar" style="margin-bottom: 16px;">
        <div class="progress-fill indigo" data-pct="<?= min(($streak / 30) * 100, 100) ?>" style="width: 0;"></div>
    </div>
    <div class="flex-between">
        <span>Longest Streak</span>
        <strong><?= $longest ?> days</strong>
    </div>
</div>

<!-- Recommendations -->
<div class="card">
    <div class="card-header">
        <div class="card-title">💡 Personalized Recommendations</div>
    </div>
    
    <?php
    $recommendations = [];
    
    if ($meal_stats['active_days'] == 0) {
        $recommendations[] = "🍽️ You haven't logged any meals in the last 30 days. Start logging to see personalized insights!";
    } else {
        if ($avg_daily > $profile['daily_calorie_goal'] * 1.1) {
            $recommendations[] = "🔴 You're exceeding your calorie goal by about " . round($avg_daily - $profile['daily_calorie_goal']) . " kcal/day. Consider portion control or increasing activity.";
        } elseif ($avg_daily < $profile['daily_calorie_goal'] * 0.8 && $profile['goal_mode'] == 'gain') {
            $recommendations[] = "🟡 You're under-eating for your muscle gain goal. Try adding healthy calorie-dense foods like nuts, avocado, or protein shakes.";
        } elseif ($avg_daily < $profile['daily_calorie_goal'] * 0.7 && $profile['goal_mode'] == 'lose') {
            $recommendations[] = "⚠️ You're eating too few calories! This can slow metabolism. Try to stay within 500 calories of your goal.";
        }
        
        if ($water_stats['avg_glasses'] < $profile['water_goal'] * 0.7) {
            $recommendations[] = "💧 You're not meeting your water goal. Try keeping a water bottle at your desk and setting reminders.";
        }
        
        if ($streak < 5 && $meal_stats['active_days'] > 10) {
            $recommendations[] = "🔥 You had a streak of " . $streak . " days. Can you reach 7 days for a badge?";
        }
    }
    
    if (empty($recommendations)) {
        $recommendations[] = "✅ Great job! You're on track with your health goals. Keep up the consistent work!";
    }
    ?>
    
    <?php foreach ($recommendations as $rec): ?>
        <div class="smart-nudge" style="margin-bottom: 10px;">
            <div class="nudge-icon">📋</div>
            <div class="nudge-msg" style="font-size: .85rem;"><?= $rec ?></div>
        </div>
    <?php endforeach; ?>
</div>

<script>
// Animate progress bars
document.querySelectorAll('.progress-fill[data-pct]').forEach(el => {
    const pct = Math.min(parseFloat(el.dataset.pct) || 0, 100);
    setTimeout(() => { el.style.width = pct + '%'; }, 100);
});
</script>

<?php require_once 'includes/footer.php'; ?>