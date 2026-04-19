<?php
$page_title = 'Add Recipe';
$active_page = 'dishes';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

// Clone functionality
$clone_id = isset($_GET['clone']) ? (int)$_GET['clone'] : 0;
$clone_data = null;
if ($clone_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM recipes WHERE recipe_id = ? AND (is_public = 1 OR user_id = ?)");
    $stmt->bind_param('ii', $clone_id, $current_user_id);
    $stmt->execute();
    $clone_data = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        flash('error', 'Invalid request.');
    } else {
        $title = trim($_POST['title'] ?? '');
        $meal_type = $_POST['meal_type'] ?? 'lunch';
        $ingredients = trim($_POST['ingredients'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $calories = (int)($_POST['calories'] ?? 0);
        $protein = (float)($_POST['protein'] ?? 0);
        $carbs = (float)($_POST['carbs'] ?? 0);
        $fats = (float)($_POST['fats'] ?? 0);
        $fiber = (float)($_POST['fiber'] ?? 0);
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        
        // Validation
        if (empty($title)) {
            flash('error', 'Please enter a recipe title.');
        } elseif (empty($ingredients)) {
            flash('error', 'Please enter ingredients.');
        } elseif (empty($instructions)) {
            flash('error', 'Please enter instructions.');
        } elseif ($calories <= 0) {
            flash('error', 'Please enter valid calories.');
        } else {
            $image_path = null;
            if (!empty($_FILES['image']['tmp_name'])) {
                $upload = uploadImage($_FILES['image'], RECIPE_UPLOAD);
                if (isset($upload['error'])) {
                    flash('error', $upload['error']);
                } else {
                    $image_path = $upload['name'];
                }
            }
            
            if (!isset($_SESSION['flash'])) {
                $stmt = $conn->prepare("
                    INSERT INTO recipes (user_id, title, meal_type, ingredients, instructions, 
                                         calories, protein, carbs, fats, fiber, image_path, is_public) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param('issssiddddsi', 
                    $current_user_id, $title, $meal_type, $ingredients, $instructions,
                    $calories, $protein, $carbs, $fats, $fiber, $image_path, $is_public
                );
                
                if ($stmt->execute()) {
                    flash('success', 'Recipe added successfully!');
                    redirect(BASE_URL . '/dishes.php');
                } else {
                    flash('error', 'Failed to add recipe.');
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <a href="dishes.php" style="font-size:.82rem;color:var(--text3)">← Back to recipes</a>
        <h1 class="page-title" style="margin-top:4px"><?= $clone_data ? 'Clone Recipe' : 'Add New Recipe' ?></h1>
    </div>
</div>

<div style="max-width: 720px;">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        
        <div class="card" style="margin-bottom: 16px;">
            <div class="card-title" style="margin-bottom: 16px;">Basic info</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" 
                           value="<?= $clone_data ? e($clone_data['title']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Meal type</label>
                    <select name="meal_type" class="form-control">
                        <option value="breakfast" <?= $clone_data && $clone_data['meal_type'] == 'breakfast' ? 'selected' : '' ?>>🍳 Breakfast</option>
                        <option value="lunch" <?= $clone_data && $clone_data['meal_type'] == 'lunch' ? 'selected' : '' ?>>🥗 Lunch</option>
                        <option value="dinner" <?= $clone_data && $clone_data['meal_type'] == 'dinner' ? 'selected' : '' ?>>🍽️ Dinner</option>
                        <option value="snack" <?= $clone_data && $clone_data['meal_type'] == 'snack' ? 'selected' : '' ?>>🍎 Snack</option>
                        <option value="smoothie" <?= $clone_data && $clone_data['meal_type'] == 'smoothie' ? 'selected' : '' ?>>🥤 Smoothie</option>
                        <option value="protein_shake" <?= $clone_data && $clone_data['meal_type'] == 'protein_shake' ? 'selected' : '' ?>>💪 Protein Shake</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Image (optional)</label>
                <input type="file" name="image" accept="image/*" class="form-control" onchange="previewImage(this, 'imgPreview')">
                <img id="imgPreview" style="display:none; width:100%; max-height:180px; object-fit:cover; border-radius:var(--radius); margin-top:10px">
            </div>
            
            <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
                <input type="checkbox" name="is_public" id="is_public" value="1" <?= $clone_data && $clone_data['is_public'] ? 'checked' : 'checked' ?>>
                <label for="is_public" style="font-size:.85rem;color:var(--text2)">Make this recipe public for others to see</label>
            </div>
        </div>
        
        <div class="card" style="margin-bottom: 16px;">
            <div class="card-title" style="margin-bottom: 16px;">Nutrition (per serving)</div>
            
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px;">
                <div class="form-group">
                    <label class="form-label">Calories</label>
                    <input type="number" name="calories" class="form-control" 
                           value="<?= $clone_data ? $clone_data['calories'] : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Protein (g)</label>
                    <input type="number" name="protein" class="form-control" step="0.1"
                           value="<?= $clone_data ? $clone_data['protein'] : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Carbs (g)</label>
                    <input type="number" name="carbs" class="form-control" step="0.1"
                           value="<?= $clone_data ? $clone_data['carbs'] : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Fats (g)</label>
                    <input type="number" name="fats" class="form-control" step="0.1"
                           value="<?= $clone_data ? $clone_data['fats'] : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Fiber (g)</label>
                    <input type="number" name="fiber" class="form-control" step="0.1"
                           value="<?= $clone_data ? $clone_data['fiber'] : '' ?>">
                </div>
            </div>
        </div>
        
        <div class="card" style="margin-bottom: 16px;">
            <div class="card-title" style="margin-bottom: 10px;">Ingredients</div>
            <textarea name="ingredients" class="form-control" rows="6" required 
                      placeholder="1 cup rolled oats&#10;1 cup milk&#10;1 banana&#10;2 tbsp almonds"><?= $clone_data ? e($clone_data['ingredients']) : '' ?></textarea>
            <div class="form-hint">One ingredient per line</div>
        </div>
        
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-title" style="margin-bottom: 10px;">Instructions</div>
            <textarea name="instructions" class="form-control" rows="8" required
                      placeholder="1. Step one...&#10;2. Step two...&#10;3. Step three..."><?= $clone_data ? e($clone_data['instructions']) : '' ?></textarea>
            <div class="form-hint">Number each step for clarity</div>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary"><?= $clone_data ? 'Clone Recipe' : 'Add Recipe' ?></button>
            <a href="dishes.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>