<?php
$page_title = 'Feedback';
$active_page = 'account';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $message = trim($_POST['message'] ?? '');
        $rating = (int)($_POST['rating'] ?? 0);
        
        if (empty($message)) {
            $error = 'Please enter your feedback.';
        } else {
            $stmt = $conn->prepare("INSERT INTO feedback (user_id, message, rating) VALUES (?, ?, ?)");
            $stmt->bind_param('isi', $current_user_id, $message, $rating);
            
            if ($stmt->execute()) {
                $success = 'Thank you for your feedback! We appreciate your input.';
            } else {
                $error = 'Failed to submit feedback. Please try again.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">💬 Feedback</h1>
        <p class="page-subtitle">Help us improve FitFuel</p>
    </div>
</div>

<div style="max-width: 600px; margin: 0 auto;">
    <?php if ($success): ?>
        <div class="flash flash-success"><span class="flash-icon">✓</span><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash flash-error"><span class="flash-icon">✕</span><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">Share Your Thoughts</div>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
            <div class="form-group">
                <label class="form-label">How would you rate FitFuel?</label>
                <div class="star-rating" style="display: flex; gap: 8px; font-size: 2rem; cursor: pointer;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>" style="color: #d1cfe8;">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingValue" value="0">
            </div>
            
            <div class="form-group">
                <label class="form-label">Your Message</label>
                <textarea name="message" class="form-control" rows="6" 
                          placeholder="What do you like? What can we improve? Any bugs to report?" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;">
                Send Feedback
            </button>
        </form>
    </div>
    
    <div class="card" style="margin-top: 20px; background: var(--accent-dim);">
        <div class="card-header">
            <div class="card-title">💡 Feature Requests?</div>
        </div>
        <p class="text-muted">We're always looking to improve. Let us know what features you'd like to see!</p>
    </div>
</div>

<script>
// Star rating handler
document.querySelectorAll('.star-rating .star').forEach(star => {
    star.addEventListener('click', function() {
        const value = this.dataset.value;
        document.getElementById('ratingValue').value = value;
        
        // Update stars display
        document.querySelectorAll('.star-rating .star').forEach((s, i) => {
            if (i < value) {
                s.style.color = '#f59e0b';
            } else {
                s.style.color = '#d1cfe8';
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>