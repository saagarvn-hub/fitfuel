<?php
$page_title = 'Meal Diary';
$active_page = 'diary';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// Handle log meal form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_meal') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        flash('error', 'Invalid request.');
    } else {
        $recipe_id = !empty($_POST['recipe_id']) ? (int)$_POST['recipe_id'] : null;
        $meal_name = trim($_POST['meal_name'] ?? '');
        $calories = (int)($_POST['calories'] ?? 0);
        $protein = (float)($_POST['protein'] ?? 0);
        $carbs = (float)($_POST['carbs'] ?? 0);
        $fats = (float)($_POST['fats'] ?? 0);
        $fiber = (float)($_POST['fiber'] ?? 0);
        $servings = max(0.25, (float)($_POST['servings'] ?? 1));
        $meal_time = $_POST['meal_time'] ?? 'snack';
        $log_date = $_POST['log_date'] ?? $date;
        $notes = trim($_POST['notes'] ?? '');

        if (empty($meal_name)) {
            flash('error', 'Please enter a meal name.');
        } elseif ($calories <= 0) {
            flash('error', 'Please enter valid calories.');
        } else {
            if ($servings != 1) {
                $calories = round($calories * $servings);
                $protein = round($protein * $servings, 1);
                $carbs = round($carbs * $servings, 1);
                $fats = round($fats * $servings, 1);
                $fiber = round($fiber * $servings, 1);
            }

            $stmt = $conn->prepare("INSERT INTO meal_log (
                user_id, recipe_id, meal_name, calories, protein, carbs, fats, fiber, 
                servings, log_date, meal_time, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iisiddddssss', 
                $current_user_id, $recipe_id, $meal_name, $calories, $protein, $carbs, 
                $fats, $fiber, $servings, $log_date, $meal_time, $notes
            );
            
            if ($stmt->execute()) {
                updateStreak($current_user_id);
                checkAchievements($current_user_id);
                flash('success', 'Meal logged successfully!');
            } else {
                flash('error', 'Failed to log meal.');
            }
        }
    }
    header('Location: diary.php?date=' . $log_date);
    exit;
}

// Fetch meals for date
$stmt = $conn->prepare("SELECT * FROM meal_log WHERE user_id = ? AND log_date = ? ORDER BY FIELD(meal_time, 'breakfast', 'lunch', 'dinner', 'snack'), logged_at");
$stmt->bind_param('is', $current_user_id, $date);
$stmt->execute();
$meals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$grouped = [];
foreach ($meals as $m) {
    $grouped[$m['meal_time']][] = $m;
}

$totals = getTodayTotals($conn, $current_user_id, $date);
$cal_pct = $profile['daily_calorie_goal'] > 0 ? round($totals['cal'] / $profile['daily_calorie_goal'] * 100) : 0;

$recipes = $conn->query("
    SELECT recipe_id, title, meal_type, calories, protein, carbs, fats, fiber 
    FROM recipes 
    WHERE is_public = 1 OR user_id = $current_user_id 
    ORDER BY title
")->fetch_all(MYSQLI_ASSOC);

$mt_icons = ['breakfast' => '☀️', 'lunch' => '🌿', 'dinner' => '🌙', 'snack' => '🍎'];
$prev_date = date('Y-m-d', strtotime($date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($date . ' +1 day'));
$can_next = $next_date <= date('Y-m-d');

require_once 'includes/header.php';
?>

<!-- CSRF Token -->
<input type="hidden" id="csrf" value="<?= csrfToken() ?>">

<!-- Page Header with Log button ONLY -->
<div class="page-header">
    <div>
        <h1 class="page-title">Meal Diary</h1>
        <p class="page-subtitle"><?= date('l, F j, Y', strtotime($date)) ?></p>
    </div>
    <div class="page-actions">
        <a href="diary.php?date=<?= $prev_date ?>" class="btn btn-secondary btn-sm">← Prev</a>
        <input type="date" value="<?= $date ?>" max="<?= date('Y-m-d') ?>" 
               onchange="window.location='diary.php?date='+this.value" 
               class="form-control" style="width:150px">
        <a href="diary.php?date=<?= $next_date ?>" class="btn btn-secondary btn-sm" 
           <?= !$can_next ? 'style="opacity:0.4;pointer-events:none"' : '' ?>>
            Next →
        </a>
        <button class="btn btn-primary" onclick="openModal('logModal')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Log meal
        </button>
    </div>
</div>

<!-- Summary Stats -->
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card accent-card">
        <div class="stat-label">Total calories</div>
        <div class="stat-value"><?= number_format($totals['cal']) ?></div>
        <div class="stat-sub"><?= $cal_pct ?>% of <?= number_format($profile['daily_calorie_goal']) ?> goal</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Protein</div>
        <div class="stat-value" style="color:#6366f1"><?= round($totals['pro']) ?>g</div>
        <div class="stat-sub">of <?= $profile['protein_goal'] ?>g</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Carbs</div>
        <div class="stat-value" style="color:#0ea5e9"><?= round($totals['carbs']) ?>g</div>
        <div class="stat-sub">of <?= $profile['carbs_goal'] ?>g</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Fats</div>
        <div class="stat-value" style="color:#f59e0b"><?= round($totals['fats']) ?>g</div>
        <div class="stat-sub">of <?= $profile['fats_goal'] ?>g</div>
    </div>
</div>

<!-- Progress Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="progress-wrap" style="margin-bottom:0">
        <div class="progress-header">
            <span>Daily calorie progress</span>
            <span><?= number_format($totals['cal']) ?> / <?= number_format($profile['daily_calorie_goal']) ?> kcal</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill indigo" data-pct="<?= min($cal_pct, 100) ?>" style="width:0"></div>
        </div>
    </div>
</div>

<!-- Meals List -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Meals — <?= date('M j', strtotime($date)) ?></div>
    </div>
    
    <?php if (empty($meals)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <h3>No meals logged</h3>
            <p>Tap "Log meal" to add your first entry for this day.</p>
            <button class="btn btn-primary btn-sm" onclick="openModal('logModal')" style="margin-top:10px">
                + Log your first meal
            </button>
        </div>
    <?php else: ?>
        <?php foreach (['breakfast', 'lunch', 'dinner', 'snack'] as $mt): 
            if (empty($grouped[$mt])) continue; ?>
            <div class="meal-group mt-<?= $mt ?>" style="margin-bottom:18px">
                <div class="meal-group-header">
                    <div class="meal-group-dot"></div>
                    <span class="meal-group-title"><?= $mt_icons[$mt] ?> <?= ucfirst($mt) ?></span>
                    <span class="meal-group-cals"><?= array_sum(array_column($grouped[$mt], 'calories')) ?> kcal</span>
                </div>
                <?php foreach ($grouped[$mt] as $m): ?>
                <div class="meal-item" id="meal-<?= $m['log_id'] ?>">
                    <?php if ($m['image_path'] && file_exists(UPLOAD_PATH . 'meals/' . $m['image_path'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/meals/<?= e($m['image_path']) ?>" 
                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px;">
                    <?php else: ?>
                        <div style="width: 50px; height: 50px; background: var(--surface2); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            🍽️
                        </div>
                    <?php endif; ?>
                    <div style="flex:1; min-width:0">
                        <div class="meal-item-name"><?= e($m['meal_name']) ?></div>
                        <div class="meal-item-meta">
                            <?= $m['servings'] ?> serving<?= $m['servings'] != 1 ? 's' : '' ?> · 
                            P:<?= round($m['protein']) ?>g · C:<?= round($m['carbs']) ?>g · F:<?= round($m['fats']) ?>g
                            <?php if ($m['fiber'] > 0): ?> · Fiber:<?= round($m['fiber']) ?>g<?php endif; ?>
                        </div>
                        <?php if ($m['notes']): ?>
                            <div style="font-size:.75rem;color:var(--text3);margin-top:2px">💬 <?= e($m['notes']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="meal-item-cal"><?= $m['calories'] ?> kcal</div>
                    <div class="meal-item-actions">
                        <button class="meal-action-btn" onclick="uploadMealImage(<?= $m['log_id'] ?>)" title="Add photo">
                            📷
                        </button>
                        <a href="remove.php?type=meal&id=<?= $m['log_id'] ?>&from=diary&date=<?= $date ?>" 
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

<!-- Log Meal Modal -->
<div class="modal-backdrop" id="logModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Log a meal</div>
            <button class="modal-close" onclick="closeModal('logModal')">✕</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="log_meal">
                <input type="hidden" name="log_date" value="<?= $date ?>">

                <div class="form-group">
                    <label class="form-label">Pick a recipe (optional)</label>
                    <select name="recipe_id" id="recipeSelect" class="form-control" onchange="fillFromRecipe(this)">
                        <option value="">— Custom meal —</option>
                        <?php foreach ($recipes as $r): ?>
                        <option value="<?= $r['recipe_id'] ?>"
                            data-cal="<?= $r['calories'] ?>"
                            data-pro="<?= $r['protein'] ?>"
                            data-carbs="<?= $r['carbs'] ?>"
                            data-fats="<?= $r['fats'] ?>"
                            data-fiber="<?= $r['fiber'] ?>"
                            data-name="<?= e($r['title']) ?>"
                            data-type="<?= $r['meal_type'] ?>">
                            <?= e($r['title']) ?> (<?= $r['calories'] ?> kcal)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Meal name</label>
                        <input type="text" name="meal_name" id="mealName" class="form-control" placeholder="e.g. Chicken salad" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Meal type</label>
                        <select name="meal_time" id="mealTime" class="form-control">
                            <option value="breakfast">☀️ Breakfast</option>
                            <option value="lunch">🌿 Lunch</option>
                            <option value="dinner">🌙 Dinner</option>
                            <option value="snack" selected>🍎 Snack</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Servings</label>
                        <input type="number" name="servings" id="servings" class="form-control" value="1" min="0.25" step="0.25" oninput="updateServingCalories()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Calories <span id="servingCalPreview" style="color:#4f46e5;font-size:.8rem"></span></label>
                        <input type="number" name="calories" id="modalCal" class="form-control" placeholder="350" required min="0">
                        <input type="hidden" id="base_calories" value="0">
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group"><label class="form-label">Protein (g)</label><input type="number" name="protein" id="modalPro" class="form-control" placeholder="0" min="0" step="0.1"></div>
                    <div class="form-group"><label class="form-label">Carbs (g)</label><input type="number" name="carbs" id="modalCarbs" class="form-control" placeholder="0" min="0" step="0.1"></div>
                    <div class="form-group"><label class="form-label">Fats (g)</label><input type="number" name="fats" id="modalFats" class="form-control" placeholder="0" min="0" step="0.1"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Notes (optional)</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Added extra cheese">
                </div>

                <button type="submit" class="btn btn-primary w-full" style="justify-content:center">Log meal</button>
            </form>
        </div>
    </div>
</div>

<script>
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
        formData.append('csrf', document.getElementById('csrf')?.value || '<?= csrfToken() ?>');
        
        const response = await fetch('upload-meal-image.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    };
    input.click();
}

function fillFromRecipe(select) {
    const option = select.options[select.selectedIndex];
    if (!option.value) { clearForm(); return; }
    
    document.getElementById('mealName').value = option.dataset.name || '';
    document.getElementById('modalCal').value = option.dataset.cal || '';
    document.getElementById('modalPro').value = option.dataset.pro || '';
    document.getElementById('modalCarbs').value = option.dataset.carbs || '';
    document.getElementById('modalFats').value = option.dataset.fats || '';
    document.getElementById('base_calories').value = option.dataset.cal || 0;
    
    const typeMap = {'breakfast':'breakfast','lunch':'lunch','dinner':'dinner','snack':'snack','smoothie':'snack','protein_shake':'snack'};
    document.getElementById('mealTime').value = typeMap[option.dataset.type] || 'snack';
    updateServingCalories();
}

function clearForm() {
    document.getElementById('mealName').value = '';
    document.getElementById('modalCal').value = '';
    document.getElementById('modalPro').value = '';
    document.getElementById('modalCarbs').value = '';
    document.getElementById('modalFats').value = '';
    document.getElementById('base_calories').value = 0;
    document.getElementById('servings').value = 1;
    updateServingCalories();
}

function updateServingCalories() {
    const base = parseFloat(document.getElementById('base_calories').value || 0);
    const servings = parseFloat(document.getElementById('servings').value || 1);
    const preview = document.getElementById('servingCalPreview');
    const calInput = document.getElementById('modalCal');
    
    if (base > 0 && preview) {
        const scaled = Math.round(base * servings);
        preview.textContent = `(→ ${scaled} kcal)`;
        calInput.value = scaled;
    } else if (preview) {
        preview.textContent = '';
    }
}

<?php if (!empty($_GET['log'])): ?>
document.addEventListener('DOMContentLoaded', function() { openModal('logModal'); });
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>