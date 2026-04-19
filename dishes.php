<?php
$page_title = 'Recipes';
$active_page = 'dishes';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

$recipes = $conn->query("
    SELECT r.*, u.username, 
           COALESCE(AVG(rr.rating), 0) as avg_rating, 
           COUNT(rr.rating) as rating_count 
    FROM recipes r 
    JOIN users u ON r.user_id = u.user_id 
    LEFT JOIN recipe_ratings rr ON r.recipe_id = rr.recipe_id 
    WHERE r.is_public = 1 OR r.user_id = $current_user_id 
    GROUP BY r.recipe_id 
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$mt_emojis = [
    'breakfast' => '🍳', 'lunch' => '🥗', 'dinner' => '🍽️', 
    'snack' => '🍎', 'smoothie' => '🥤', 'protein_shake' => '💪'
];

require_once 'includes/header.php';
?>

<!-- Page Header with Add button ONLY -->
<div class="page-header">
    <div>
        <h1 class="page-title">Recipes</h1>
        <p class="page-subtitle"><?= count($recipes) ?> recipes in your library</p>
    </div>
    <a href="add-recipe.php" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add recipe
    </a>
</div>

<div class="search-bar">
    <div class="search-input-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" id="recipeSearch" class="form-control search-input" placeholder="Search recipes...">
    </div>
    <div class="filter-chips">
        <button class="chip active" data-type="all">All</button>
        <button class="chip" data-type="breakfast">🍳 Breakfast</button>
        <button class="chip" data-type="lunch">🥗 Lunch</button>
        <button class="chip" data-type="dinner">🍽️ Dinner</button>
        <button class="chip" data-type="snack">🍎 Snack</button>
        <button class="chip" data-type="smoothie">🥤 Smoothie</button>
        <button class="chip" data-type="protein_shake">💪 Protein</button>
    </div>
</div>

<?php if (empty($recipes)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">🥗</div>
        <h3>No recipes yet</h3>
        <p><a href="add-recipe.php">Add your first recipe</a> to get started!</p>
    </div>
<?php else: ?>
    <div class="recipe-grid">
        <?php foreach ($recipes as $r): ?>
        <a href="view.php?id=<?= $r['recipe_id'] ?>" class="recipe-card mt-<?= $r['meal_type'] ?>" 
           data-type="<?= $r['meal_type'] ?>" data-title="<?= e(strtolower($r['title'])) ?>">
            <div class="recipe-img">
                <?php if ($r['image_path'] && file_exists(RECIPE_UPLOAD . $r['image_path'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/recipes/<?= e($r['image_path']) ?>" alt="<?= e($r['title']) ?>">
                <?php else: ?>
                    <div class="recipe-type-dot"></div>
                    <div class="recipe-img-placeholder"><?= $mt_emojis[$r['meal_type']] ?? '🍴' ?></div>
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
                <div style="margin-top:8px">
                    <span class="badge badge-gray"><?= ucfirst(str_replace('_', ' ', $r['meal_type'])) ?></span>
                    <?php if ($r['user_id'] == $current_user_id): ?>
                        <span class="badge badge-purple" style="margin-left:4px">Mine</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function filterRecipes() {
    const searchInput = document.getElementById('recipeSearch');
    const query = searchInput?.value?.toLowerCase() || '';
    const activeChip = document.querySelector('.chip.active');
    const type = activeChip?.dataset?.type || 'all';
    
    document.querySelectorAll('.recipe-card').forEach(card => {
        const cardType = card.dataset.type;
        const cardTitle = card.dataset.title || '';
        const matchesType = type === 'all' || cardType === type;
        const matchesSearch = cardTitle.includes(query);
        card.style.display = matchesType && matchesSearch ? '' : 'none';
    });
}

document.querySelectorAll('.chip').forEach(chip => {
    chip.addEventListener('click', function() {
        document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        filterRecipes();
    });
});

document.getElementById('recipeSearch')?.addEventListener('input', filterRecipes);
</script>

<?php require_once 'includes/footer.php'; ?>