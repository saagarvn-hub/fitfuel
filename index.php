<?php
$page_title = 'Dashboard';
$active_page = 'dashboard';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

$today = date('Y-m-d');
$totals = getTodayTotals($conn, $current_user_id);
$water = getWaterLog($conn, $current_user_id);
$weekly = getWeeklyCalories($conn, $current_user_id);

// Today's meals grouped by meal_time
$stmt = $conn->prepare("SELECT * FROM meal_log WHERE user_id=? AND log_date=? ORDER BY FIELD(meal_time, 'breakfast', 'lunch', 'dinner', 'snack'), logged_at");
$stmt->bind_param('is', $current_user_id, $today);
$stmt->execute();
$meal_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$grouped = [];
foreach ($meal_rows as $m) {
    $grouped[$m['meal_time']][] = $m;
}

// Recommended recipes
$stmt2 = $conn->prepare("SELECT r.*, COALESCE(AVG(rr.rating),0) avg_rating 
    FROM recipes r 
    LEFT JOIN recipe_ratings rr ON r.recipe_id = rr.recipe_id 
    WHERE r.is_public = 1 
        AND r.recipe_id NOT IN (
            SELECT DISTINCT recipe_id FROM meal_log 
            WHERE user_id=? AND log_date=? AND recipe_id IS NOT NULL
        ) 
    GROUP BY r.recipe_id 
    ORDER BY avg_rating DESC, r.created_at DESC 
    LIMIT 4");
$stmt2->bind_param('is', $current_user_id, $today);
$stmt2->execute();
$recs = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// Smart nudge
$cal_remaining = $profile['daily_calorie_goal'] - $totals['cal'];
$hour = (int)date('H');
$nudge = null;
if ($hour >= 18 && $cal_remaining > 400) {
    $nudge = ['💡', "You still have <strong>{$cal_remaining} kcal</strong> left today. Consider a protein-rich dinner to hit your goal."];
} elseif ($totals['pro'] < $profile['protein_goal'] * 0.5 && $hour >= 14) {
    $remaining_protein = $profile['protein_goal'] - $totals['pro'];
    $nudge = ['🥩', "You're under halfway on protein. Need <strong>{$remaining_protein}g</strong> more — try Greek yogurt or eggs!"];
} elseif ($water < 4 && $hour >= 14) {
    $nudge = ['💧', "Only <strong>{$water}</strong> glasses of water logged. Staying hydrated supports metabolism!"];
} elseif ($totals['cal'] == 0 && $hour > 10) {
    $nudge = ['🍽️', "You haven't logged any meals today. Start tracking to see your progress!"];
}

updateStreak($current_user_id);

$bmi = calcBMI($profile['weight_kg'], $profile['height_cm']);
$bmi_info = bmiCategory($bmi);
$cal_pct = $profile['daily_calorie_goal'] > 0 ? round($totals['cal'] / $profile['daily_calorie_goal'] * 100) : 0;

$week_labels = array_column($weekly, 'day');
$week_data = array_column($weekly, 'calories');

$mt_icons = ['breakfast' => '☀️', 'lunch' => '🌿', 'dinner' => '🌙', 'snack' => '🍎'];

require_once 'includes/header.php';
?>

<!-- CSRF Token -->
<input type="hidden" id="csrf" value="<?= csrfToken() ?>">

<!-- Page Header with Log meal button ONLY (no mobile header here) -->
<div class="page-header">
    <div>
        <h1 class="page-title">Good <?= $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening') ?>, <?= e(explode('_', $profile['username'])[0]) ?> 👋</h1>
        <p class="page-subtitle"><?= date('l, F j, Y') ?></p>
    </div>
    <div class="page-actions">
        <a href="diary.php?log=1" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Log meal
        </a>
        <a href="export-data.php" class="btn btn-secondary btn-sm">Export CSV</a>
    </div>
</div>

<?php if ($nudge): ?>
<div class="smart-nudge">
    <div class="nudge-icon"><?= $nudge[0] ?></div>
    <div>
        <div class="nudge-title">Smart Tip</div>
        <div class="nudge-msg"><?= $nudge[1] ?></div>
    </div>
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="stat-grid">
    <div class="stat-card accent-card">
        <div class="stat-label">Calories today</div>
        <div class="stat-value"><?= number_format($totals['cal']) ?></div>
        <div class="stat-sub">of <?= number_format($profile['daily_calorie_goal']) ?> goal</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Remaining</div>
        <div class="stat-value" style="color:<?= $cal_remaining >= 0 ? '#10b981' : '#ef4444' ?>">
            <?= number_format(abs($cal_remaining)) ?>
        </div>
        <div class="stat-sub"><?= $cal_remaining >= 0 ? 'kcal left' : 'kcal over goal' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">BMI</div>
        <div class="stat-value"><?= $bmi ?></div>
        <div class="stat-sub">
            <span class="badge badge-<?= $bmi_info[1] === 'success' ? 'green' : ($bmi_info[1] === 'warning' ? 'amber' : 'red') ?>">
                <?= $bmi_info[0] ?>
            </span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Weight</div>
        <div class="stat-value"><?= $profile['weight_kg'] ?>kg</div>
        <div class="stat-sub">Goal: <?= $profile['goal_weight_kg'] ?>kg</div>
    </div>
</div>

<!-- Main Content Row -->
<div class="grid-2-3" style="margin-bottom:20px">
    
    <!-- Left Column: Daily Progress -->
    <div>
        <div class="card" style="margin-bottom:14px">
            <div class="card-header">
                <div>
                    <div class="card-title">Daily progress</div>
                    <div class="card-subtitle"><?= $cal_pct ?>% of daily goal consumed</div>
                </div>
                <div class="badge badge-<?= $cal_pct > 100 ? 'red' : 'purple' ?>"><?= $cal_pct ?>%</div>
            </div>
            
            <!-- Calories Progress -->
            <div class="progress-wrap">
                <div class="progress-header">
                    <span>Calories</span>
                    <span><?= number_format($totals['cal']) ?> / <?= number_format($profile['daily_calorie_goal']) ?> kcal</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill indigo" data-pct="<?= min($cal_pct, 100) ?>" style="width:0"></div>
                </div>
            </div>
            
            <!-- Macros Grid -->
            <div class="macro-grid" style="margin-top:14px">
                <?php
                $macros_data = [
                    'protein' => [$totals['pro'], $profile['protein_goal'], 'indigo', 'g', '#6366f1'],
                    'carbs' => [$totals['carbs'], $profile['carbs_goal'], 'blue', 'g', '#0ea5e9'],
                    'fats' => [$totals['fats'], $profile['fats_goal'], 'amber', 'g', '#f59e0b'],
                ];
                foreach ($macros_data as $name => [$cur, $goal_v, $cls, $unit, $color]):
                    $pct2 = $goal_v > 0 ? min(round($cur / $goal_v * 100), 100) : 0;
                ?>
                <div class="macro-pill">
                    <div class="macro-name"><?= ucfirst($name) ?></div>
                    <div class="macro-val" style="color:<?= $color ?>"><?= round($cur) ?><?= $unit ?></div>
                    <div class="macro-goal">of <?= $goal_v ?><?= $unit ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill <?= $cls ?>" data-pct="<?= $pct2 ?>" style="width:0"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Streak Banner -->
            <?php if ($profile['current_streak'] > 0): ?>
            <div class="streak-banner">
                <span class="streak-fire">🔥</span>
                <span class="streak-num"><?= $profile['current_streak'] ?></span>
                <span class="streak-label">day streak — keep it up!</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Water Tracker Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">💧 Water Tracker</div>
                <span id="waterCount" style="font-family:'Syne',sans-serif;font-weight:700;color:#0ea5e9"><?= $water ?></span>
                <span style="font-size:.8rem;color:var(--text3)"> / <?= $profile['water_goal'] ?> glasses</span>
            </div>
            <div class="water-glasses">
                <?php for ($i = 1; $i <= $profile['water_goal']; $i++): ?>
                <button class="glass-btn <?= $i <= $water ? 'filled' : '' ?>" 
                        onclick="setWater(<?= $i ?>, <?= $current_user_id ?>, '<?= $today ?>')" 
                        title="<?= $i ?> glass<?= $i > 1 ? 'es' : '' ?>">💧</button>
                <?php endfor; ?>
            </div>
            <div class="progress-bar" style="margin-top:8px">
                <div class="progress-fill blue" data-pct="<?= round($water / $profile['water_goal'] * 100) ?>" style="width:0"></div>
            </div>
        </div>
    </div>

    <!-- Right Column: Weekly Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Weekly calories</div>
            <span class="text-muted">Last 7 days</span>
        </div>
        <div class="chart-wrap">
            <canvas id="weekChart"></canvas>
        </div>
        <div style="margin-top:10px;font-size:.78rem;color:var(--text3);text-align:center">
            Avg: <strong><?= number_format(round(array_sum($week_data) / max(1, count(array_filter($week_data))))) ?></strong> kcal · 
            Goal: <strong><?= number_format($profile['daily_calorie_goal']) ?></strong> kcal
        </div>
    </div>
</div>

<!-- Today's Meals Section -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <div class="card-title">Today's meals</div>
        <a href="diary.php" class="btn btn-secondary btn-sm">View diary</a>
    </div>
    <?php if (empty($meal_rows)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🍽️</div>
            <h3>No meals logged yet</h3>
            <p>Start by logging your breakfast!</p>
            <a href="diary.php?log=1" class="btn btn-primary btn-sm" style="margin-top:10px">+ Log your first meal</a>
        </div>
    <?php else: ?>
        <?php foreach (['breakfast', 'lunch', 'dinner', 'snack'] as $mt): 
            if (empty($grouped[$mt])) continue; ?>
            <div class="meal-group mt-<?= $mt ?>">
                <div class="meal-group-header">
                    <div class="meal-group-dot"></div>
                    <span class="meal-group-title"><?= $mt_icons[$mt] ?> <?= ucfirst($mt) ?></span>
                    <span class="meal-group-cals"><?= array_sum(array_column($grouped[$mt], 'calories')) ?> kcal</span>
                </div>
                <?php foreach ($grouped[$mt] as $m): ?>
                <div class="meal-item">
                    <?php if ($m['image_path'] && file_exists(UPLOAD_PATH . 'meals/' . $m['image_path'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/meals/<?= e($m['image_path']) ?>" 
                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px;">
                    <?php else: ?>
                        <div style="width: 50px; height: 50px; background: var(--surface2); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            🍽️
                        </div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0">
                        <div class="meal-item-name"><?= e($m['meal_name']) ?></div>
                        <div class="meal-item-meta">
                            <?= date('g:i A', strtotime($m['logged_at'])) ?> · 
                            P:<?= round($m['protein']) ?>g C:<?= round($m['carbs']) ?>g F:<?= round($m['fats']) ?>g
                        </div>
                    </div>
                    <div class="meal-item-cal"><?= $m['calories'] ?> kcal</div>
                    <div class="meal-item-actions">
                        <button class="meal-action-btn" onclick="uploadMealImage(<?= $m['log_id'] ?>)" title="Add photo">
                            📷
                        </button>
                        <a href="remove.php?type=meal&id=<?= $m['log_id'] ?>&from=dashboard" 
                           class="meal-action-btn del" 
                           onclick="return confirm('Remove this meal?')" 
                           title="Delete">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6l-1 14H6L5 6"/>
                                <path d="M10 11v6M14 11v6"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Recommended Recipes -->
<?php if (!empty($recs)): ?>
<div style="margin-bottom:8px" class="flex-between">
    <h2 style="font-size:1rem; font-weight:700">Recommended for you</h2>
    <a href="dishes.php" class="text-muted" style="font-size:.82rem">View all →</a>
</div>
<div class="recipe-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-bottom:20px">
    <?php foreach ($recs as $r): ?>
    <a href="view.php?id=<?= $r['recipe_id'] ?>" class="recipe-card mt-<?= $r['meal_type'] ?>" data-type="<?= $r['meal_type'] ?>" data-title="<?= e(strtolower($r['title'])) ?>">
        <div class="recipe-img">
            <?php if ($r['image_path'] && file_exists(RECIPE_UPLOAD . $r['image_path'])): ?>
                <img src="<?= BASE_URL ?>/uploads/recipes/<?= e($r['image_path']) ?>" alt="<?= e($r['title']) ?>">
            <?php else: ?>
                <div class="recipe-type-dot"></div>
                <div class="recipe-img-placeholder">
                    <?= ['breakfast'=>'🍳', 'lunch'=>'🥗', 'dinner'=>'🍽️', 'snack'=>'🍎', 'smoothie'=>'🥤', 'protein_shake'=>'💪'][$r['meal_type']] ?? '🍴' ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="recipe-body">
            <div class="recipe-title"><?= e($r['title']) ?></div>
            <div class="recipe-meta">
                <span><?= $r['calories'] ?> kcal</span>
                <?php if ($r['avg_rating'] > 0): ?>
                <span style="color:#f59e0b">★ <?= round($r['avg_rating'], 1) ?></span>
                <?php endif; ?>
            </div>
            <div class="recipe-macros">
                <span class="recipe-macro" style="color:#6366f1">P <?= round($r['protein']) ?>g</span>
                <span class="recipe-macro" style="color:#0ea5e9">C <?= round($r['carbs']) ?>g</span>
                <span class="recipe-macro" style="color:#f59e0b">F <?= round($r['fats']) ?>g</span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
// Render weekly chart
renderWeekChart(
    <?= json_encode($week_labels) ?>,
    <?= json_encode($week_data) ?>,
    <?= $profile['daily_calorie_goal'] ?>
);

// Meal image upload function
function uploadMealImage(mealId) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp';
    input.onchange = async function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('image', file);
        formData.append('meal_id', mealId);
        
        const csrfInput = document.getElementById('csrf');
        if (csrfInput) {
            formData.append('csrf', csrfInput.value);
        }
        
        try {
            const response = await fetch('upload-meal-image.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + (result.error || 'Upload failed'));
            }
        } catch (error) {
            alert('Error uploading image. Please try again.');
        }
    };
    input.click();
}
</script>

<?php require_once 'includes/footer.php'; ?>