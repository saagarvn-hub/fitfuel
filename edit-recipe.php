<?php
$page_title = "Edit Recipe";
require_once 'includes/config.php';
require_once 'includes/protect.php';

$id = $_GET['id'] ?? 0;
$recipe = getRecipeById($id);
if (!$recipe) {
    header('Location: dishes.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'],
        'meal_type' => $_POST['meal_type'],
        'ingredients' => $_POST['ingredients'],
        'instructions' => $_POST['instructions'],
        'calories' => $_POST['calories'],
        'protein' => $_POST['protein'] ?: 0,
        'carbs' => $_POST['carbs'] ?: 0,
        'fats' => $_POST['fats'] ?: 0
    ];
    
    $imagePath = $recipe['image_path'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/uploads/';
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = $targetPath;
        }
    }
    
    if (updateRecipe($id, $data, $imagePath)) {
        $success = 'Recipe updated successfully!';
        $recipe = getRecipeById($id);
    } else {
        $error = 'Failed to update recipe';
    }
}
?>
<?php include 'includes/header.php'; ?>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <h2><i class="fas fa-edit"></i> Edit Recipe</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group"><label>Recipe Title *</label><input type="text" name="title" value="<?php echo htmlspecialchars($recipe['title']); ?>" required></div>
            <div class="form-row">
                <div class="form-group"><label>Meal Type *</label><select name="meal_type" required>
                    <option value="breakfast" <?php echo $recipe['meal_type'] == 'breakfast' ? 'selected' : ''; ?>>Breakfast</option>
                    <option value="lunch" <?php echo $recipe['meal_type'] == 'lunch' ? 'selected' : ''; ?>>Lunch</option>
                    <option value="dinner" <?php echo $recipe['meal_type'] == 'dinner' ? 'selected' : ''; ?>>Dinner</option>
                    <option value="snack" <?php echo $recipe['meal_type'] == 'snack' ? 'selected' : ''; ?>>Snack</option>
                    <option value="smoothie" <?php echo $recipe['meal_type'] == 'smoothie' ? 'selected' : ''; ?>>Smoothie</option>
                    <option value="protein_shake" <?php echo $recipe['meal_type'] == 'protein_shake' ? 'selected' : ''; ?>>Protein Shake</option>
                </select></div>
                <div class="form-group"><label>Calories *</label><input type="number" name="calories" value="<?php echo $recipe['calories']; ?>" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Protein (g)</label><input type="number" name="protein" step="0.1" value="<?php echo $recipe['protein']; ?>"></div>
                <div class="form-group"><label>Carbs (g)</label><input type="number" name="carbs" step="0.1" value="<?php echo $recipe['carbs']; ?>"></div>
                <div class="form-group"><label>Fats (g)</label><input type="number" name="fats" step="0.1" value="<?php echo $recipe['fats']; ?>"></div>
            </div>
            <div class="form-group"><label>Ingredients *</label><textarea name="ingredients" rows="5" required><?php echo htmlspecialchars($recipe['ingredients']); ?></textarea></div>
            <div class="form-group"><label>Instructions *</label><textarea name="instructions" rows="8" required><?php echo htmlspecialchars($recipe['instructions']); ?></textarea></div>
            <div class="form-group"><label>Recipe Image</label><?php if ($recipe['image_path']): ?><div><img src="<?php echo $recipe['image_path']; ?>" style="max-width: 150px; margin-bottom: 0.5rem;"></div><?php endif; ?><input type="file" name="image" accept="image/*"></div>
            <button type="submit" class="btn btn-primary">Update Recipe</button>
            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-outline">Cancel</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>