<?php
$page_title = 'Water Tracker';
$active_page = 'water-tracker';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));

$stmt = $conn->prepare("SELECT log_date, glasses FROM water_log WHERE user_id = ? AND log_date BETWEEN ? AND ? ORDER BY log_date");
$stmt->bind_param('iss', $current_user_id, $start_date, $end_date);
$stmt->execute();
$water_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$water_map = [];
foreach ($water_data as $w) {
    $water_map[$w['log_date']] = $w['glasses'];
}

$days_in_month = date('t', strtotime($start_date));
$first_day = date('N', strtotime($start_date));

$prev_month = date('Y-m', strtotime("$year-$month-01 -1 month"));
$next_month = date('Y-m', strtotime("$year-$month-01 +1 month"));
$can_next = strtotime($next_month . '-01') <= strtotime(date('Y-m-d'));

$current_month_display = date('F Y', strtotime($start_date));
$prev_month_display = date('M Y', strtotime($prev_month));
$next_month_display = date('M Y', strtotime($next_month));

require_once 'includes/header.php';
?>

<input type="hidden" id="csrf" value="<?= csrfToken() ?>">

<div class="page-header">
    <div>
        <h1 class="page-title">💧 Water Tracker</h1>
        <p class="page-subtitle">Track your daily water intake</p>
    </div>
</div>

<!-- Month Navigation - Centered Design -->
<div style="display: flex; justify-content: center; align-items: baseline; gap: 24px; margin-bottom: 28px; flex-wrap: wrap;">
    <a href="?year=<?= date('Y', strtotime($prev_month)) ?>&month=<?= date('m', strtotime($prev_month)) ?>" style="color: var(--text3); text-decoration: none; font-size: 1rem;">
        ← <?= $prev_month_display ?>
    </a>
    <span style="font-size: 1.3rem; font-weight: 700; color: var(--text); font-family: 'Syne', sans-serif;">
        <?= $current_month_display ?>
    </span>
    <a href="?year=<?= date('Y', strtotime($next_month)) ?>&month=<?= date('m', strtotime($next_month)) ?>" style="color: var(--text3); text-decoration: none; font-size: 1rem; <?= !$can_next ? 'opacity:0.4; pointer-events:none;' : '' ?>">
        <?= $next_month_display ?> →
    </a>
</div>

<!-- Stats Cards -->
<div class="stat-grid" style="margin-bottom: 24px;">
    <div class="stat-card" style="text-align: center;">
        <div class="stat-label">TOTAL</div>
        <div class="stat-value"><?= array_sum(array_column($water_data, 'glasses')) ?></div>
        <div class="stat-sub">glasses this month</div>
    </div>
    <div class="stat-card" style="text-align: center;">
        <div class="stat-label">AVERAGE</div>
        <div class="stat-value"><?= round(array_sum(array_column($water_data, 'glasses')) / max(1, count($water_data)), 1) ?></div>
        <div class="stat-sub">glasses/day</div>
    </div>
    <div class="stat-card" style="text-align: center;">
        <div class="stat-label">BEST DAY</div>
        <div class="stat-value"><?= max(array_column($water_data, 'glasses')) ?: 0 ?></div>
        <div class="stat-sub">glasses</div>
    </div>
    <div class="stat-card" style="text-align: center;">
        <div class="stat-label">GOAL</div>
        <div class="stat-value"><?= $profile['water_goal'] ?></div>
        <div class="stat-sub">glasses/day</div>
    </div>
</div>

<!-- Calendar -->
<div class="card" style="padding: 16px;">
    <div class="card-header">
        <div class="card-title">📅 Hydration Calendar</div>
    </div>
    
    <!-- Weekday Headers -->
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin-bottom: 12px;">
        <div style="text-align: center; padding: 8px; font-weight: 700; color: var(--text2); font-size: 0.8rem;">Mon</div>
        <div style="text-align: center; padding: 8px; font-weight: 700; color: var(--text2); font-size: 0.8rem;">Tue</div>
        <div style="text-align: center; padding: 8px; font-weight: 700; color: var(--text2); font-size: 0.8rem;">Wed</div>
        <div style="text-align: center; padding: 8px; font-weight: 700; color: var(--text2); font-size: 0.8rem;">Thu</div>
        <div style="text-align: center; padding: 8px; font-weight: 700; color: var(--text2); font-size: 0.8rem;">Fri</div>
        <div style="text-align: center; padding: 8px; font-weight: 700; color: var(--text2); font-size: 0.8rem;">Sat</div>
        <div style="text-align: center; padding: 8px; font-weight: 700; color: var(--text2); font-size: 0.8rem;">Sun</div>
    </div>
    
    <!-- Calendar Grid -->
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px;">
        <?php for ($i = 1; $i < $first_day; $i++): ?>
            <div style="background: var(--surface2); border-radius: 12px; min-height: 85px;"></div>
        <?php endfor; ?>
        
        <?php for ($day = 1; $day <= $days_in_month; $day++): 
            $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
            $glasses = $water_map[$date] ?? 0;
            $percentage = $profile['water_goal'] > 0 ? round($glasses / $profile['water_goal'] * 100) : 0;
            $is_today = ($date == date('Y-m-d'));
        ?>
            <div style="border: 1px solid <?= $is_today ? 'var(--accent)' : 'var(--border)' ?>; border-radius: 12px; padding: 8px; text-align: center; background: <?= $percentage >= 100 ? '#d1fae5' : ($percentage > 0 ? '#eff6ff' : 'var(--surface)') ?>;">
                <div style="font-weight: 700; font-size: 0.9rem; <?= $is_today ? 'color: var(--accent);' : '' ?>"><?= $day ?></div>
                <div style="font-size: 1.2rem; margin: 4px 0;">💧</div>
                <div style="font-size: 0.7rem; font-weight: 600;"><?= $glasses ?>/<?= $profile['water_goal'] ?></div>
                <div style="background: var(--surface2); border-radius: 99px; height: 4px; margin: 6px 0; overflow: hidden;">
                    <div style="width: <?= $percentage ?>%; height: 100%; background: var(--info);"></div>
                </div>
                <button onclick="addWater('<?= $date ?>', <?= $glasses ?>)" style="margin-top: 6px; padding: 4px 8px; font-size: 0.65rem; background: var(--accent); color: white; border: none; border-radius: 20px; cursor: pointer; width: 100%;">+1</button>
            </div>
        <?php endfor; ?>
    </div>
</div>

<script>
function addWater(date, currentGlasses) {
    let newGlasses = currentGlasses + 1;
    if (newGlasses > <?= $profile['water_goal'] ?>) {
        newGlasses = <?= $profile['water_goal'] ?>;
    }
    const csrf = document.getElementById('csrf')?.value || '<?= csrfToken() ?>';
    
    fetch('water.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `glasses=${newGlasses}&date=${date}&csrf=${csrf}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success === true) {
            location.reload();
        } else {
            alert('Error: ' + (data.message || data.error || 'Failed to update'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error. Please try again.');
    });
}
</script>

<style>
@media (max-width: 768px) {
    .stat-value {
        font-size: 1.3rem !important;
    }
    .stat-card {
        padding: 10px !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>