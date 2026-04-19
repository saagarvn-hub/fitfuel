<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'invalid_method']);
    exit;
}

$csrf = $_POST['csrf'] ?? '';
if (!verifyCsrf($csrf)) {
    echo json_encode(['success' => false, 'error' => 'invalid_csrf']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$glasses = max(0, min(20, (int)($_POST['glasses'] ?? 0)));
$date = $_POST['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS water_log (
    water_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    glasses INT DEFAULT 0,
    log_date DATE NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, log_date),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

$stmt = $conn->prepare("INSERT INTO water_log (user_id, glasses, log_date) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE glasses = VALUES(glasses)");
$stmt->bind_param('iis', $user_id, $glasses, $date);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'glasses' => $glasses]);
} else {
    echo json_encode(['success' => false, 'error' => 'database_error']);
}
?>