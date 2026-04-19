<?php
$page_title = 'Recipe Details';
$active_page = 'dishes';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Invalid recipe.');
    redirect(BASE_URL . '/dishes.php');
}

// Fetch recipe with ratings
$stmt = $conn->prepare("
    SELECT r.*, u.username, u.user_id as author_id,
           COALESCE(AVG(rr.rating), 0) as avg_rating,
           COUNT(DISTINCT rr.rating_id) as rating_count
    FROM recipes r
    JOIN users u ON r.user_id = u.user_id
    LEFT JOIN recipe_ratings rr ON r.recipe_id = rr.recipe_id
    WHERE r.recipe_id = ? AND (r.is_public = 1 OR r.user_id = ?)
    GROUP BY r.recipe_id
");
$stmt->bind_param('ii', $id, $current_user_id);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

if (!$recipe) {
    flash('error', 'Recipe not found.');
    redirect(BASE_URL . '/dishes.php');
}

$is_owner = ($recipe['user_id'] == $current_user_id);

// Get user's rating
$user_rating = 0;
$rating_stmt = $conn->prepare("SELECT rating FROM recipe_ratings WHERE recipe_id = ? AND user_id = ?");
$rating_stmt->bind_param('ii', $id, $current_user_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result()->fetch_assoc();
if ($rating_result) {
    $user_rating = $rating_result['rating'];
}

$mt_emojis = [
    'breakfast' => '🍳', 'lunch' => '🥗', 'dinner' => '🍽️',
    'snack' => '🍎', 'smoothie' => '🥤', 'protein_shake' => '💪'
];

require_once 'includes/header.php';
?>

<style>
    .recipe-detail-container {
        max-width: 900px;
        margin: 0 auto;
    }
    .recipe-hero {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        margin-bottom: 32px;
    }
    .recipe-image {
        background: linear-gradient(135deg, var(--accent), #7C3AED);
        border-radius: var(--radius-lg);
        height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .recipe-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .recipe-image-placeholder {
        font-size: 5rem;
    }
    .recipe-title {
        font-size: 1.8rem;
        margin-bottom: 16px;
        font-family: 'Syne', sans-serif;
    }
    .recipe-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    .rating-section {
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid var(--border);
    }
    .stars {
        display: flex;
        gap: 8px;
        margin-top: 8px;
    }
    .stars .star {
        font-size: 1.8rem;
        cursor: pointer;
        transition: transform 0.2s;
        color: #cbd5e1;
    }
    .stars .star:hover {
        transform: scale(1.1);
    }
    .stars .star.active {
        color: #f59e0b;
    }
    .ingredients-list, .instructions-list {
        background: var(--surface2);
        border-radius: var(--radius-lg);
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid var(--border);
    }
    .ingredients-list li, .instructions-list li {
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
        list-style: none;
    }
    .ingredients-list li:last-child, .instructions-list li:last-child {
        border-bottom: none;
    }
    @media (max-width: 768px) {
        .recipe-hero {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .recipe-image {
            height: 250px;
        }
        .recipe-title {
            font-size: 1.5rem;
        }
    }
</style>

<div class="recipe-detail-container">
    <!-- Back button -->
    <div style="margin-bottom: 20px;">
        <a href="dishes.php" style="color: var(--text3); font-size: 0.85rem;">← Back to recipes</a>
    </div>

    <div class="recipe-hero">
        <!-- Recipe Image -->
        <div class="recipe-image">
            <?php if ($recipe['image_path'] && file_exists(RECIPE_UPLOAD . $recipe['image_path'])): ?>
                <img src="<?= BASE_URL ?>/uploads/recipes/<?= e($recipe['image_path']) ?>" alt="<?= e($recipe['title']) ?>">
            <?php else: ?>
                <div class="recipe-image-placeholder">
                    <?= $mt_emojis[$recipe['meal_type']] ?? '🍴' ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recipe Info -->
        <div>
            <h1 class="recipe-title"><?= e($recipe['title']) ?></h1>
            
            <div class="recipe-meta">
                <span class="badge badge-purple">🔥 <?= $recipe['calories'] ?> cal</span>
                <span class="badge badge-blue">💪 Protein: <?= round($recipe['protein']) ?>g</span>
                <span class="badge badge-blue">🍞 Carbs: <?= round($recipe['carbs']) ?>g</span>
                <span class="badge badge-amber">🫒 Fats: <?= round($recipe['fats']) ?>g</span>
                <span class="badge badge-green">⭐ <?= round($recipe['avg_rating'], 1) ?> (<?= $recipe['rating_count'] ?> reviews)</span>
                <span class="badge badge-gray"><?= ucfirst(str_replace('_', ' ', $recipe['meal_type'])) ?></span>
            </div>
            
            <!-- Rating Section -->
            <div class="rating-section">
                <label style="font-size: 0.85rem; color: var(--text2);">Rate this recipe:</label>
                <div class="stars" data-recipe="<?= $recipe['recipe_id'] ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?= ($user_rating && $user_rating >= $i) ? 'active' : '' ?>" data-rating="<?= $i ?>">★</span>
                    <?php endfor; ?>
                </div>
                <div id="ratingMessage" style="font-size: 0.75rem; color: var(--text3); margin-top: 5px;">
                    <?php if ($user_rating > 0): ?>
                        You rated this <?= $user_rating ?> ★
                    <?php else: ?>
                        Click a star to rate
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Add to Meal Log Form -->
            <form method="POST" action="diary.php" style="margin-top: 24px;">
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="log_meal">
                <input type="hidden" name="recipe_id" value="<?= $recipe['recipe_id'] ?>">
                <input type="hidden" name="meal_name" value="<?= e($recipe['title']) ?>">
                <input type="hidden" name="calories" id="logCalories" value="<?= $recipe['calories'] ?>">
                <input type="hidden" name="protein" value="<?= $recipe['protein'] ?>">
                <input type="hidden" name="carbs" value="<?= $recipe['carbs'] ?>">
                <input type="hidden" name="fats" value="<?= $recipe['fats'] ?>">
                <input type="hidden" name="fiber" value="<?= $recipe['fiber'] ?>">
                <input type="hidden" name="log_date" value="<?= date('Y-m-d') ?>">
                
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div>
                        <label style="font-size: 0.75rem; color: var(--text3);">Servings:</label>
                        <input type="number" name="servings" id="logServings" value="1" step="0.5" min="0.5" 
                               style="width: 80px; padding: 10px; border: 1px solid var(--border2); border-radius: var(--radius);" 
                               oninput="updateLogCalories(<?= $recipe['calories'] ?>)">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; color: var(--text3);">Meal time:</label>
                        <select name="meal_time" style="padding: 10px; border: 1px solid var(--border2); border-radius: var(--radius);">
                            <option value="breakfast">☀️ Breakfast</option>
                            <option value="lunch">🌿 Lunch</option>
                            <option value="dinner">🌙 Dinner</option>
                            <option value="snack">🍎 Snack</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 18px;">
                        + Add to Meal Log
                    </button>
                </div>
                <div id="totalCaloriesPreview" style="font-size: 0.8rem; color: var(--accent); margin-top: 10px;">
                    Total: <?= $recipe['calories'] ?> kcal
                </div>
            </form>
        </div>
    </div>
    
    <!-- Ingredients -->
    <div class="ingredients-list">
        <h3 style="margin-bottom: 16px;">🥕 Ingredients</h3>
        <ul>
            <?php
            $ingredients = explode("\n", $recipe['ingredients']);
            foreach ($ingredients as $ingredient):
                if (trim($ingredient) != ''):
            ?>
                <li><?= e(trim($ingredient)) ?></li>
            <?php endif; endforeach; ?>
        </ul>
    </div>
    
    <!-- Instructions -->
    <div class="instructions-list">
        <h3 style="margin-bottom: 16px;">👨‍🍳 Instructions</h3>
        <ul>
            <?php
            $steps = explode("\n", $recipe['instructions']);
            foreach ($steps as $index => $step):
                if (trim($step) != ''):
            ?>
                <li><?= ($index + 1) . '. ' . e(trim($step)) ?></li>
            <?php endif; endforeach; ?>
        </ul>
    </div>
    
    <!-- Owner Actions -->
    <?php if ($is_owner): ?>
        <div style="display: flex; gap: 12px; justify-content: center; margin-top: 24px;">
            <a href="edit-recipe.php?id=<?= $recipe['recipe_id'] ?>" class="btn btn-secondary">✏️ Edit Recipe</a>
            <a href="remove.php?type=recipe&id=<?= $recipe['recipe_id'] ?>&from=view" 
               class="btn btn-danger" 
               onclick="return confirm('Delete this recipe permanently? This cannot be undone.')">
                🗑️ Delete Recipe
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
// Update total calories when servings change
function updateLogCalories(baseCalories) {
    const servings = parseFloat(document.getElementById('logServings').value || 1);
    const total = Math.round(baseCalories * servings);
    document.getElementById('logCalories').value = total;
    document.getElementById('totalCaloriesPreview').innerHTML = `Total: ${total} kcal`;
}

// Rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.stars .star');
    const recipeId = document.querySelector('.stars')?.dataset.recipe;
    const csrf = document.getElementById('csrf')?.value || '<?= csrfToken() ?>';
    
    if (stars.length > 0) {
        stars.forEach(star => {
            star.addEventListener('click', async function() {
                const rating = this.dataset.rating;
                
                try {
                    const response = await fetch('rating.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `recipe_id=${recipeId}&rating=${rating}&csrf=${csrf}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Update star display
                        stars.forEach((s, index) => {
                            if (index < rating) {
                                s.classList.add('active');
                            } else {
                                s.classList.remove('active');
                            }
                        });
                        
                        // Update message
                        const msgDiv = document.getElementById('ratingMessage');
                        if (msgDiv) {
                            msgDiv.innerHTML = `You rated this ${rating} ★`;
                        }
                        
                        // Show success toast
                        const toast = document.createElement('div');
                        toast.textContent = '⭐ Rating saved!';
                        toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #10b981; color: white; padding: 12px 20px; border-radius: 10px; z-index: 9999; animation: fadeIn 0.3s;';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 2000);
                    } else {
                        alert('Failed to save rating. Please try again.');
                    }
                } catch (error) {
                    console.error('Rating error:', error);
                    alert('Error saving rating. Make sure you are logged in.');
                }
            });
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>