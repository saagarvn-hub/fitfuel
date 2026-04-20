<?php
$page_title = 'Weight Tracker';
$active_page = 'weight-tracker';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

// Get weight history
$stmt = $conn->prepare("
    SELECT weight_kg, log_date, DATE_FORMAT(log_date, '%b %d') as label 
    FROM weight_log 
    WHERE user_id = ? 
    ORDER BY log_date ASC
");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$weight_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get last 30 days for chart
$chart_data = [];
$chart_labels = [];
$start_date = date('Y-m-d', strtotime('-30 days'));
$stmt2 = $conn->prepare("
    SELECT log_date, weight_kg 
    FROM weight_log 
    WHERE user_id = ? AND log_date >= ?
    ORDER BY log_date ASC
");
$stmt2->bind_param('is', $current_user_id, $start_date);
$stmt2->execute();
$recent = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($recent as $w) {
    $chart_labels[] = date('M d', strtotime($w['log_date']));
    $chart_data[] = (float)$w['weight_kg'];
}

$start_weight = !empty($weight_history) ? $weight_history[0]['weight_kg'] : $profile['weight_kg'];
$current_weight = $profile['weight_kg'];
$goal_weight = $profile['goal_weight_kg'];
$total_lost = $start_weight - $current_weight;
$to_goal = $current_weight - $goal_weight;

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⚖️ Weight Tracker</h1>
        <p class="page-subtitle">Track your progress over time</p>
    </div>
    <a href="settings.php" class="btn btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Update Weight
    </a>
</div>

<!-- Summary Stats -->
<div class="stat-grid" style="margin-bottom: 20px;">
    <div class="stat-card <?= $total_lost > 0 ? 'accent-card' : '' ?>">
        <div class="stat-label">Total Lost</div>
        <div class="stat-value" style="color: <?= $total_lost > 0 ? '#10b981' : ($total_lost < 0 ? '#ef4444' : 'inherit') ?>">
            <?= $total_lost > 0 ? '-' : ($total_lost < 0 ? '+' : '') ?><?= abs($total_lost) ?> kg
        </div>
        <div class="stat-sub">from <?= $start_weight ?> kg</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Current Weight</div>
        <div class="stat-value"><?= $current_weight ?> kg</div>
        <div class="stat-sub">as of today</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Goal Weight</div>
        <div class="stat-value"><?= $goal_weight ?> kg</div>
        <div class="stat-sub"><?= $to_goal > 0 ? $to_goal . ' kg to go' : ($to_goal < 0 ? abs($to_goal) . ' kg below' : 'Goal reached!') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Entries</div>
        <div class="stat-value"><?= count($weight_history) ?></div>
        <div class="stat-sub">total weigh-ins</div>
    </div>
</div>

<!-- Weight Chart -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <div class="card-title">📈 Weight Progress (Last 30 Days)</div>
    </div>
    <div class="chart-wrap" style="height: 300px;">
        <canvas id="weightChart"></canvas>
    </div>
</div>

<!-- Weight History Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">📋 Weight History</div>
    </div>
    
    <?php if (empty($weight_history)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">⚖️</div>
            <h3>No weight entries yet</h3>
            <p><a href="settings.php">Update your weight</a> to start tracking progress</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Weight (kg)</th>
                        <th>Change</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $prev = null;
                    foreach (array_reverse($weight_history) as $w): 
                        $change = $prev ? $w['weight_kg'] - $prev : 0;
                        $change_class = $change > 0 ? 'danger' : ($change < 0 ? 'success' : 'muted');
                        $change_symbol = $change > 0 ? '+' : ($change < 0 ? '-' : '');
                    ?>
                    <tr>
                        <td><?= date('F j, Y', strtotime($w['log_date'])) ?></td>
                        <td><strong><?= $w['weight_kg'] ?> kg</strong></td>
                        <td class="text-<?= $change_class ?>">
                            <?php if ($change != 0): ?>
                                <?= $change_symbol ?><?= abs($change) ?> kg
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        $prev = $w['weight_kg'];
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
// Render the weight chart
const chartLabels = <?= json_encode($chart_labels) ?>;
const chartData = <?= json_encode($chart_data) ?>;
const goalWeight = <?= $goal_weight ?>;

const ctx = document.getElementById('weightChart');
if (ctx && chartLabels.length > 0) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Weight (kg)',
                data: chartData,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79,70,229,.08)',
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#4f46e5',
                fill: true,
                tension: 0.35,
            }, {
                label: 'Goal',
                data: Array(chartLabels.length).fill(goalWeight),
                borderColor: '#10b981',
                borderDash: [6, 4],
                borderWidth: 1.5,
                pointRadius: 0,
                fill: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { font: { family: "'Plus Jakarta Sans'" }, boxWidth: 12 }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Plus Jakarta Sans'" }, color: '#9794ac' }
                },
                y: {
                    grid: { color: '#f0eff6' },
                    ticks: { font: { family: "'Plus Jakarta Sans'" }, color: '#9794ac' }
                }
            },
            animation: { duration: 800 }
        }
    });
} else if (ctx) {
    ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📊</div><h3>Not enough data</h3><p>Add more weight entries to see the chart</p></div>';
}
</script>

<?php require_once 'includes/footer.php'; ?>