<?php
$page_title = 'Reports';
$active_page = 'analytics';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

$report_type = $_GET['type'] ?? 'weekly';

// Get date range
switch ($report_type) {
    case 'weekly':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case 'monthly':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        break;
    case 'yearly':
        $start_date = date('Y-m-d', strtotime('-365 days'));
        $end_date = date('Y-m-d');
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
}

// Get data
$stmt = $conn->prepare("
    SELECT log_date, 
           SUM(calories) as calories,
           SUM(protein) as protein,
           SUM(carbs) as carbs,
           SUM(fats) as fats
    FROM meal_log 
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
    GROUP BY log_date
    ORDER BY log_date
");
$stmt->bind_param('iss', $current_user_id, $start_date, $end_date);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$labels = [];
$calories_data = [];
foreach ($data as $row) {
    $labels[] = date('M d', strtotime($row['log_date']));
    $calories_data[] = (int)$row['calories'];
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📊 Reports</h1>
        <p class="page-subtitle">Analyze your nutrition trends</p>
    </div>
    <a href="export-data.php" class="btn btn-secondary btn-sm">📥 Export CSV</a>
</div>

<!-- Report Type Selector -->
<div class="filter-chips" style="margin-bottom: 24px;">
    <a href="report.php?type=weekly" class="chip <?= $report_type == 'weekly' ? 'active' : '' ?>">Last 7 Days</a>
    <a href="report.php?type=monthly" class="chip <?= $report_type == 'monthly' ? 'active' : '' ?>">Last 30 Days</a>
    <a href="report.php?type=yearly" class="chip <?= $report_type == 'yearly' ? 'active' : '' ?>">Last Year</a>
</div>

<!-- Calorie Trend Chart -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div class="card-title">📈 Calorie Trend</div>
    </div>
    <div class="chart-wrap" style="height: 300px;">
        <canvas id="reportChart"></canvas>
    </div>
    <div style="margin-top: 12px; text-align: center; font-size: 0.8rem; color: var(--text3);">
        Goal: <?= number_format($profile['daily_calorie_goal']) ?> kcal/day
    </div>
</div>

<!-- Daily Breakdown Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">📋 Daily Breakdown</div>
    </div>
    <div class="table-wrap">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Calories</th>
                    <th>Protein</th>
                    <th>Carbs</th>
                    <th>Fats</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No data available for this period</td>
                    </tr>
                <?php else: ?>
                    <?php foreach (array_reverse($data) as $row): ?>
                    <tr>
                        <td><?= date('F j, Y', strtotime($row['log_date'])) ?></td>
                        <td><strong><?= number_format($row['calories']) ?> kcal</strong></td>
                        <td><?= round($row['protein']) ?>g</td>
                        <td><?= round($row['carbs']) ?>g</td>
                        <td><?= round($row['fats']) ?>g</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Render the chart
const labels = <?= json_encode($labels) ?>;
const caloriesData = <?= json_encode($calories_data) ?>;
const calorieGoal = <?= $profile['daily_calorie_goal'] ?>;

const ctx = document.getElementById('reportChart');
if (ctx && labels.length > 0) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Calories',
                data: caloriesData,
                backgroundColor: 'rgba(79,70,229,0.7)',
                borderColor: '#4f46e5',
                borderWidth: 1.5,
                borderRadius: 6,
            }, {
                label: 'Goal',
                data: Array(labels.length).fill(calorieGoal),
                borderColor: '#10b981',
                borderWidth: 2,
                borderDash: [6, 4],
                type: 'line',
                fill: false,
                pointRadius: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { font: { family: "'Plus Jakarta Sans'" }, boxWidth: 12 }
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.y + ' kcal'
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Plus Jakarta Sans'" }, color: '#9794ac' }
                },
                y: {
                    grid: { color: '#f0eff6' },
                    ticks: { font: { family: "'Plus Jakarta Sans'" }, color: '#9794ac' },
                    title: { display: true, text: 'Calories', color: '#9794ac' }
                }
            },
            animation: { duration: 800 }
        }
    });
} else if (ctx) {
    ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📊</div><h3>No data available</h3><p>Log some meals to see your progress chart</p></div>';
}
</script>

<?php require_once 'includes/footer.php'; ?>