<?php
$page_title = 'Contact';
$active_page = 'contact';
require_once 'includes/config.php';
require_once 'includes/utils.php';

$is_logged_in = !empty($_SESSION['user_id']);
if ($is_logged_in) {
    $current_user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT u.username, u.email, p.* FROM users u LEFT JOIN user_profiles p ON u.user_id=p.user_id WHERE u.user_id=?");
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $success = 'Thank you for reaching out! We\'ll get back to you within 48 hours.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📧 Contact Us</h1>
        <p class="page-subtitle">Get in touch with us</p>
    </div>
</div>

<div style="max-width: 800px; margin: 0 auto;">
    
    <!-- Contact Info Cards -->
    <div class="stat-grid" style="margin-bottom: 24px;">
        <div class="stat-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 8px;">📧</div>
            <div class="stat-label">Email</div>
            <div class="stat-value" style="font-size: 0.9rem; word-break: break-all;">saagarvnjain@gmail.com</div>
            <div class="stat-sub">24/7 Support</div>
        </div>
        <div class="stat-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 8px;">📞</div>
            <div class="stat-label">Phone</div>
            <div class="stat-value" style="font-size: 1rem;">+91 6362476630</div>
            <div class="stat-sub">Mon-Fri, 9am-6pm</div>
        </div>
        <div class="stat-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 8px;">💬</div>
            <div class="stat-label">WhatsApp</div>
            <div class="stat-value" style="font-size: 1rem;">+91 6362476630</div>
            <div class="stat-sub">Quick replies</div>
        </div>
        <div class="stat-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 8px;">🌐</div>
            <div class="stat-label">Website</div>
            <div class="stat-value" style="font-size: 0.9rem;">fitfuel.local</div>
            <div class="stat-sub">Visit us online</div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="flash flash-success"><span class="flash-icon">✓</span><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash flash-error"><span class="flash-icon">✕</span><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="card-title">📝 Send us a message</div>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Your Name</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= isset($profile['username']) ? htmlspecialchars($profile['username']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= isset($profile['email']) ? htmlspecialchars($profile['email']) : '' ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" required placeholder="How can we help you?">
            </div>
            
            <div class="form-group">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="5" required placeholder="Write your message here..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;">
                📤 Send Message
            </button>
        </form>
    </div>
    
    <div class="card" style="margin-top: 20px; text-align: center;">
        <div class="card-header">
            <div class="card-title">📍 Other Ways to Reach Us</div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div>
                <strong>📧 Email:</strong> <a href="mailto:saagarvnjain@gmail.com">saagarvnjain@gmail.com</a>
            </div>
            <div>
                <strong>📞 Phone/WhatsApp:</strong> <a href="tel:+916362476630">+91 6362476630</a>
            </div>
            <div>
                <strong>⏰ Support Hours:</strong> Monday - Friday, 9:00 AM to 6:00 PM IST
            </div>
            <div>
                <strong>📍 Location:</strong> India
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>