<?php
$page_title = 'Settings';
$active_page = 'settings';
require_once 'includes/protect.php';
require_once 'includes/utils.php';

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $weight = (float)($_POST['weight_kg'] ?? $profile['weight_kg']);
        $goal_weight = (float)($_POST['goal_weight_kg'] ?? $profile['goal_weight_kg']);
        $height = (float)($_POST['height_cm'] ?? $profile['height_cm']);
        $age = (int)($_POST['age'] ?? $profile['age']);
        $gender = $_POST['gender'] ?? $profile['gender'];
        $activity = $_POST['activity_level'] ?? $profile['activity_level'];
        $goal_mode = $_POST['goal_mode'] ?? $profile['goal_mode'];
        $water_goal = (int)($_POST['water_goal'] ?? $profile['water_goal']);
        
        // Recalculate goals
        $bmr = calcBMR($weight, $height, $age, $gender);
        $cal_goal = calcCalorieGoal($bmr, $activity, $goal_mode);
        $macros = calcMacros($cal_goal, $goal_weight, $goal_mode);
        
        // Update profile
        $stmt = $conn->prepare("
            UPDATE user_profiles SET 
                weight_kg = ?, goal_weight_kg = ?, height_cm = ?, age = ?, gender = ?,
                activity_level = ?, goal_mode = ?, water_goal = ?,
                daily_calorie_goal = ?, protein_goal = ?, carbs_goal = ?, fats_goal = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param('dddissssiiiii', 
            $weight, $goal_weight, $height, $age, $gender,
            $activity, $goal_mode, $water_goal,
            $cal_goal, $macros['protein'], $macros['carbs'], $macros['fats'],
            $current_user_id
        );
        
        if ($stmt->execute()) {
            // Log weight change
            if ($weight != $profile['weight_kg']) {
                $wlog = $conn->prepare("INSERT INTO weight_log (user_id, weight_kg, log_date) VALUES (?, ?, CURDATE())");
                $wlog->bind_param('id', $current_user_id, $weight);
                $wlog->execute();
            }
            
            $message = 'Settings updated successfully!';
            
            // Refresh profile data
            $stmt2 = $conn->prepare("SELECT u.username, u.email, p.* FROM users u JOIN user_profiles p ON u.user_id=p.user_id WHERE u.user_id=?");
            $stmt2->bind_param('i', $current_user_id);
            $stmt2->execute();
            $profile = $stmt2->get_result()->fetch_assoc();
        } else {
            $error = 'Failed to update settings.';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($current) || empty($new) || empty($confirm)) {
            $error = 'Please fill in all fields.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param('i', $current_user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if (password_verify($current, $user['password_hash'])) {
                $new_hash = password_hash($new, PASSWORD_BCRYPT);
                $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $update->bind_param('si', $new_hash, $current_user_id);
                
                if ($update->execute()) {
                    $message = 'Password changed successfully!';
                } else {
                    $error = 'Failed to update password.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }
}

// Handle profile photo upload
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $upload = uploadImage($_FILES['profile_photo'], PROFILE_UPLOAD, 2097152);
    if (isset($upload['error'])) {
        $error = $upload['error'];
    } else {
        if ($profile['profile_photo'] && file_exists(PROFILE_UPLOAD . $profile['profile_photo'])) {
            unlink(PROFILE_UPLOAD . $profile['profile_photo']);
        }
        
        $stmt = $conn->prepare("UPDATE user_profiles SET profile_photo = ? WHERE user_id = ?");
        $stmt->bind_param('si', $upload['name'], $current_user_id);
        if ($stmt->execute()) {
            $message = 'Profile photo updated!';
            $profile['profile_photo'] = $upload['name'];
        } else {
            $error = 'Failed to save photo.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⚙️ Settings</h1>
        <p class="page-subtitle">Manage your profile and goals</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="flash flash-success"><span class="flash-icon">✓</span><?= $message ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="flash flash-error"><span class="flash-icon">✕</span><?= $error ?></div>
<?php endif; ?>

<div style="max-width: 800px; margin: 0 auto;">
    
    <!-- Profile Photo Section -->
    <div class="card" style="margin-bottom: 20px;" id="photo">
        <div class="card-header">
            <div class="card-title">Profile Photo</div>
        </div>
        <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
            <div class="profile-photo-wrap" style="margin: 0;">
                <div class="profile-photo" style="width: 80px; height: 80px;">
                    <?php if ($profile['profile_photo'] && file_exists(PROFILE_UPLOAD . $profile['profile_photo'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($profile['profile_photo']) ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <?= strtoupper(substr($profile['username'], 0, 2)) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div style="flex:1;">
                <form method="POST" enctype="multipart/form-data" style="display: inline-block;">
                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                    <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" 
                           class="form-control" style="display: inline-block; width: auto;" onchange="this.form.submit()">
                </form>
                <?php if ($profile['profile_photo']): ?>
                    <a href="remove-profile-photo.php?csrf=<?= csrfToken() ?>" 
                       class="btn btn-danger btn-sm" 
                       style="margin-left: 10px;"
                       onclick="return confirm('Remove your profile photo?')">
                        🗑️ Remove Photo
                    </a>
                <?php endif; ?>
                <div class="form-hint" style="margin-top: 8px;">JPG, PNG or WebP. Max 2MB.</div>
            </div>
        </div>
    </div>
    
    <!-- Profile Settings Tabs -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border);">
        <button class="tab-btn active" onclick="showTab('profile')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: var(--accent); border-bottom: 2px solid var(--accent);">Profile Settings</button>
        <button class="tab-btn" onclick="showTab('password')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: var(--text2);">Change Password</button>
    </div>
    
    <!-- Profile Settings Form -->
    <div id="profileTab" class="tab-content">
        <form method="POST" action="">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <div class="card-title">Personal Information</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= e($profile['username']) ?>" disabled>
                    <div class="form-hint">Username cannot be changed</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?= e($profile['email']) ?>" disabled>
                </div>
            </div>
            
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <div class="card-title">Body Metrics</div>
                </div>
                
                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label">Current Weight (kg)</label>
                        <input type="number" name="weight_kg" class="form-control" 
                               value="<?= $profile['weight_kg'] ?>" step="0.1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Goal Weight (kg)</label>
                        <input type="number" name="goal_weight_kg" class="form-control" 
                               value="<?= $profile['goal_weight_kg'] ?>" step="0.1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Height (cm)</label>
                        <input type="number" name="height_cm" class="form-control" 
                               value="<?= $profile['height_cm'] ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-control" 
                               value="<?= $profile['age'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="male" <?= $profile['gender'] == 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= $profile['gender'] == 'female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <div class="card-title">Fitness Goals</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Activity Level</label>
                        <select name="activity_level" class="form-control">
                            <option value="sedentary" <?= $profile['activity_level'] == 'sedentary' ? 'selected' : '' ?>>Sedentary (desk job)</option>
                            <option value="light" <?= $profile['activity_level'] == 'light' ? 'selected' : '' ?>>Lightly active (1-3x/week)</option>
                            <option value="moderate" <?= $profile['activity_level'] == 'moderate' ? 'selected' : '' ?>>Moderate (3-5x/week)</option>
                            <option value="active" <?= $profile['activity_level'] == 'active' ? 'selected' : '' ?>>Active (6-7x/week)</option>
                            <option value="very_active" <?= $profile['activity_level'] == 'very_active' ? 'selected' : '' ?>>Very active (athlete)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fitness Goal</label>
                        <select name="goal_mode" class="form-control">
                            <option value="lose" <?= $profile['goal_mode'] == 'lose' ? 'selected' : '' ?>>Lose weight</option>
                            <option value="maintain" <?= $profile['goal_mode'] == 'maintain' ? 'selected' : '' ?>>Maintain weight</option>
                            <option value="gain" <?= $profile['goal_mode'] == 'gain' ? 'selected' : '' ?>>Gain muscle</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Daily Water Goal (glasses)</label>
                    <input type="number" name="water_goal" class="form-control" 
                           value="<?= $profile['water_goal'] ?>" min="1" max="20" required>
                </div>
                
                <?php
                $current_bmr = calcBMR($profile['weight_kg'], $profile['height_cm'], $profile['age'], $profile['gender']);
                $new_cal_goal = calcCalorieGoal($current_bmr, $profile['activity_level'], $profile['goal_mode']);
                ?>
                <div class="form-hint" style="margin-top: 12px; padding: 10px; background: var(--accent-dim); border-radius: var(--radius);">
                    💡 Your daily calorie goal will be recalculated as: 
                    <strong><?= $new_cal_goal ?> kcal</strong>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <!-- Change Password Tab -->
    <div id="passwordTab" class="tab-content" style="display: none;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">🔐 Change Password</div>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                    <div class="form-hint">Minimum 6 characters</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-full">Update Password</button>
            </form>
        </div>
    </div>
    
    <!-- Danger Zone -->
    <div class="card" style="margin-top: 20px; border-color: #fecaca; background: #fef2f2;">
        <div class="card-header">
            <div class="card-title" style="color: #dc2626;">⚠️ Danger Zone</div>
        </div>
        <p class="text-muted" style="margin-bottom: 12px;">Export your data or delete your account permanently.</p>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="export-data.php" class="btn btn-secondary">📥 Export Data</a>
            <a href="delete-account.php" class="btn btn-danger" onclick="return confirm('WARNING: This will permanently delete ALL your data. This cannot be undone. Continue?')">
                🗑️ Delete Account
            </a>
        </div>
    </div>
</div>

<script>
function showTab(tab) {
    const profileTab = document.getElementById('profileTab');
    const passwordTab = document.getElementById('passwordTab');
    const tabs = document.querySelectorAll('.tab-btn');
    
    if (tab === 'profile') {
        profileTab.style.display = 'block';
        passwordTab.style.display = 'none';
        tabs[0].classList.add('active');
        tabs[0].style.color = 'var(--accent)';
        tabs[0].style.borderBottom = '2px solid var(--accent)';
        tabs[1].classList.remove('active');
        tabs[1].style.color = 'var(--text2)';
        tabs[1].style.borderBottom = 'none';
    } else {
        profileTab.style.display = 'none';
        passwordTab.style.display = 'block';
        tabs[1].classList.add('active');
        tabs[1].style.color = 'var(--accent)';
        tabs[1].style.borderBottom = '2px solid var(--accent)';
        tabs[0].classList.remove('active');
        tabs[0].style.color = 'var(--text2)';
        tabs[0].style.borderBottom = 'none';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>