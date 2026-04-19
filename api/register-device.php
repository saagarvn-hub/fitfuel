<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$device_name = $input['device_name'] ?? 'Mobile App';

if (empty($email) || empty($password)) {
    echo json_encode(['error' => 'Email and password required']);
    exit;
}

$stmt = $conn->prepare("SELECT user_id, password_hash FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user['password_hash'])) {
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// Generate API key
$api_key = bin2hex(random_bytes(32));

// Add api_key column to users table if not exists
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS api_key VARCHAR(64) NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_device VARCHAR(255) NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_app DATETIME NULL");

$update = $conn->prepare("UPDATE users SET api_key = ?, last_device = ?, last_login_app = NOW() WHERE user_id = ?");
$update->bind_param('ssi', $api_key, $device_name, $user['user_id']);
$update->execute();

echo json_encode([
    'success' => true,
    'api_key' => $api_key,
    'user_id' => $user['user_id'],
    'message' => 'Device registered successfully'
]);
?>