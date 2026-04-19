<?php
$page_title = 'Meal Planner';
$active_page = 'planner';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

$start_date = isset($_GET['week']) ? $_GET['week'] : date('Y-m-d');
// Get Monday of the week
$monday = date('Y-m-d', strtotime('monday this week', strtotime($start_date)));
$week_dates = [];
for ($i = 0; $i < 7; $i++) {
    $week_dates[] = date('Y-m-d', strtotime("$monday +$i days"));
}

// Get planned meals for the week
$placeholders = implode(',', array_fill(0, 7, '?'));
$stmt = $conn->prepare("
    SELECT mp.*, r.title, r.calories, r.meal_type as recipe_type
    FROM meal_plan mp
    LEFT JOIN recipes r ON mp.recipe_id = r.recipe_id
    WHERE mp.user_id = ? AND mp.plan_date IN ($placeholders)
");
$stmt->bind_param('i' . str_repeat('s', 7), $current_user_id, ...$week_dates);
$stmt->execute();
$planned = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Organize by date and meal_time
$plan_map = [];
foreach ($planned as $p) {
    $plan_map[$p['plan_date']][$p['meal_time']] = $p;
}

// Get recipes for dropdown
$recipes = $conn->query("
    SELECT recipe_id, title, meal_type, calories 
    FROM recipes 
    WHERE is_public = 1 OR user_id = $current_user_id
    ORDER BY title
")->fetch_all(MYSQLI_ASSOC);

$meal_types = ['breakfast' => '☀️ Breakfast', 'lunch' => '🌿 Lunch', 'dinner' => '🌙 Dinner', 'snack' => '🍎 Snack'];

$prev_week = date('Y-m-d', strtotime("$monday -7 days"));
$next_week = date('Y-m-d', strtotime("$monday +7 days"));
$can_next = strtotime($next_week) <= strtotime(date('Y-m-d'));

require_once 'includes/header.php';
?>

<!-- CSRF Token -->
<input type="hidden" id="csrf" value="<?= csrfToken() ?>">

<div class="page-header">
    <div>
        <h1 class="page-title">📅 Meal Planner</h1>
        <p class="page-subtitle">Plan your meals for the week</p>
    </div>
</div>

<!-- Week Navigation - Fixed overlapping -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <a href="meal-planner.php?week=<?= $prev_week ?>" class="btn btn-secondary btn-sm">← Previous Week</a>
    <h2 style="font-size: 1.1rem; margin: 0; text-align: center;">Week of <?= date('M j', strtotime($monday)) ?> - <?= date('M j, Y', strtotime($week_dates[6])) ?></h2>
    <a href="meal-planner.php?week=<?= $next_week ?>" class="btn btn-secondary btn-sm" <?= !$can_next ? 'style="opacity:0.5; pointer-events:none;"' : '' ?>>
        Next Week →
    </a>
</div>

<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; overflow-x: auto;">
    <?php foreach ($week_dates as $date): 
        $is_today = ($date == date('Y-m-d'));
        $day_name = date('D', strtotime($date));
        $day_num = date('j', strtotime($date));
    ?>
        <div style="min-width: 110px; border: 1px solid var(--border); border-radius: var(--radius); padding: 10px; <?= $is_today ? 'border-color: var(--accent); border-width: 2px;' : '' ?>">
            <div style="text-align: center; padding: 8px; background: var(--surface2); border-radius: var(--radius); margin-bottom: 10px;">
                <div style="font-weight: 700;"><?= $day_name ?></div>
                <div style="font-size: 1rem; font-weight: 700; <?= $is_today ? 'color: var(--accent);' : '' ?>"><?= $day_num ?></div>
            </div>
            
            <?php foreach ($meal_types as $key => $label): 
                $meal = $plan_map[$date][$key] ?? null;
            ?>
                <div style="border: 1.5px dashed var(--border2); border-radius: var(--radius); padding: 8px; margin-bottom: 8px; font-size: 0.7rem; text-align: center; cursor: pointer; <?= $meal ? 'border-style: solid; background: var(--surface);' : '' ?>" onclick="openPlanModal('<?= $date ?>', '<?= $key ?>', '<?= $meal ? $meal['recipe_id'] : '' ?>')">
                    <?php if ($meal): ?>
                        <div style="font-weight: 700; font-size: 0.7rem;"><?= htmlspecialchars($meal['title']) ?></div>
                        <div style="color: var(--text3); font-size: 0.65rem;"><?= $meal['calories'] ?> kcal</div>
                    <?php else: ?>
                        <span style="color: var(--text3);">+ Add</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- Plan Modal -->
<div class="modal-backdrop" id="planModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="planModalTitle">Add Meal</div>
            <button class="modal-close" onclick="closeModal('planModal')">✕</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="save-plan.php">
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="plan_date" id="planDate">
                <input type="hidden" name="meal_time" id="planMealTime">
                <input type="hidden" name="action" value="save">
                
                <div class="form-group">
                    <label class="form-label">Select Recipe</label>
                    <select name="recipe_id" id="planRecipe" class="form-control" required>
                        <option value="">— Choose a recipe —</option>
                        <?php foreach ($recipes as $r): ?>
                        <option value="<?= $r['recipe_id'] ?>" data-cal="<?= $r['calories'] ?>">
                            <?= htmlspecialchars($r['title']) ?> (<?= $r['calories'] ?> kcal)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Or custom meal name</label>
                    <input type="text" name="custom_name" class="form-control" placeholder="e.g. Leftover pasta">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Calories (if custom)</label>
                    <input type="number" name="custom_calories" class="form-control" placeholder="0">
                </div>
                
                <button type="submit" class="btn btn-primary w-full">Save Plan</button>
                
                <button type="button" class="btn btn-danger w-full" style="margin-top: 8px;" id="removePlanBtn" onclick="removePlan()">
                    Remove from plan
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openPlanModal(date, mealTime, currentRecipeId) {
    document.getElementById('planDate').value = date;
    document.getElementById('planMealTime').value = mealTime;
    document.getElementById('planRecipe').value = currentRecipeId;
    
    const mealNames = {breakfast: 'Breakfast', lunch: 'Lunch', dinner: 'Dinner', snack: 'Snack'};
    document.getElementById('planModalTitle').innerHTML = `Plan ${mealNames[mealTime]} for ${date}`;
    
    const removeBtn = document.getElementById('removePlanBtn');
    removeBtn.style.display = currentRecipeId ? 'block' : 'none';
    
    openModal('planModal');
}

function removePlan() {
    if (confirm('Remove this meal from your plan?')) {
        const form = document.querySelector('#planModal form');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'action';
        input.value = 'remove';
        form.appendChild(input);
        form.submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>