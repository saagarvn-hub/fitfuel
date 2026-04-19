<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

if (!empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $weight = (float)($_POST['weight_kg'] ?? 70);
        $goal_w = (float)($_POST['goal_weight_kg'] ?? 65);
        $height = (float)($_POST['height_cm'] ?? 170);
        $age = (int)($_POST['age'] ?? 25);
        $gender = $_POST['gender'] ?? 'male';
        $activity = $_POST['activity_level'] ?? 'moderate';
        $goal_mode = $_POST['goal_mode'] ?? 'maintain';

        // Validation
        if (strlen($username) < 3) {
            $error = 'Username must be at least 3 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($weight < 30 || $weight > 300) {
            $error = 'Weight must be between 30 and 300 kg.';
        } elseif ($height < 100 || $height > 250) {
            $error = 'Height must be between 100 and 250 cm.';
        } elseif ($age < 10 || $age > 120) {
            $error = 'Age must be between 10 and 120.';
        } else {
            // Check for duplicate user
            $chk = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
            $chk->bind_param('ss', $email, $username);
            $chk->execute();
            
            if ($chk->get_result()->num_rows > 0) {
                $error = 'Email or username already taken.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $bmr = calcBMR($weight, $height, $age, $gender);
                $cal = calcCalorieGoal($bmr, $activity, $goal_mode);
                $macros = calcMacros($cal, $goal_w, $goal_mode);

                $conn->begin_transaction();
                try {
                    // Insert user
                    $s1 = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                    $s1->bind_param('sss', $username, $email, $hash);
                    $s1->execute();
                    $uid = $conn->insert_id;
                    
                    // Insert profile
                    $s2 = $conn->prepare("INSERT INTO user_profiles (
                        user_id, weight_kg, goal_weight_kg, height_cm, age, gender, 
                        goal_mode, activity_level, daily_calorie_goal, 
                        protein_goal, carbs_goal, fats_goal, water_goal
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 8)");
                    $s2->bind_param('idddisssiiii', 
                        $uid, $weight, $goal_w, $height, $age, $gender, 
                        $goal_mode, $activity, $cal, 
                        $macros['protein'], $macros['carbs'], $macros['fats']
                    );
                    $s2->execute();
                    
                    // Initial weight log
                    $s3 = $conn->prepare("INSERT INTO weight_log (user_id, weight_kg, log_date) VALUES (?, ?, CURDATE())");
                    $s3->bind_param('id', $uid, $weight);
                    $s3->execute();
                    
                    $conn->commit();
                    
                    $_SESSION['user_id'] = $uid;
                    flash('success', 'Account created! Your daily goal is ' . $cal . ' kcal.');
                    redirect(BASE_URL . '/index.php');
                } catch (Exception $ex) {
                    $conn->rollback();
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

$page_title = 'Register';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — FitFuel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div id="loader">
    <div class="loader-inner">
        <div class="loader-logo">
            <svg width="48" height="48" viewBox="0 0 40 40" fill="none">
                <rect width="40" height="40" rx="12" fill="#4f46e5"/>
                <path d="M20 8C14.5 8 10 12.5 10 18c0 7 10 18 10 18s10-11 10-18c0-5.5-4.5-10-10-10z" fill="white"/>
                <circle cx="20" cy="17" r="4" fill="#4f46e5"/>
            </svg>
        </div>
        <div class="loader-bar"><div class="loader-fill"></div></div>
        <div class="loader-text">Creating your wellness plan...</div>
    </div>
</div>

<div id="app" style="opacity:0; transition:opacity 0.4s">
<div class="auth-page" style="align-items:flex-start;padding:28px 24px">
    <div class="auth-card" style="max-width:560px">
        <div class="auth-logo">
            <div class="auth-logo-icon">
                <svg width="26" height="26" viewBox="0 0 40 40" fill="none">
                    <path d="M20 6C13 6 8 11 8 17c0 9 12 20 12 20s12-11 12-20c0-6-5-11-12-11z" fill="white"/>
                    <circle cx="20" cy="16" r="4" fill="#4f46e5"/>
                </svg>
            </div>
            <span style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800">FitFuel</span>
        </div>
        <h1 class="auth-title">Create your account</h1>
        <p class="auth-subtitle">We'll calculate your personalized calorie and macro goals.</p>
        
        <?php if ($error): ?>
            <div class="flash flash-error">
                <span class="flash-icon">✕</span><?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="fituser123" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
            </div>
            
            <hr class="divider">
            
            <p style="font-size:.8rem;color:var(--text3);margin-bottom:14px;font-weight:600">
                HEALTH METRICS — used to calculate your daily goals
            </p>
            
            <div class="form-row-3">
                <div class="form-group">
                    <label class="form-label">Weight (kg)</label>
                    <input type="number" name="weight_kg" id="weight_kg" class="form-control" 
                           placeholder="70" min="30" max="300" step="0.1" required oninput="updateBMIPreview()">
                </div>
                <div class="form-group">
                    <label class="form-label">Goal weight (kg)</label>
                    <input type="number" name="goal_weight_kg" class="form-control" 
                           placeholder="65" min="30" max="300" step="0.1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Height (cm)</label>
                    <input type="number" name="height_cm" id="height_cm" class="form-control" 
                           placeholder="170" min="100" max="250" required oninput="updateBMIPreview()">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" class="form-control" placeholder="25" min="10" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-control">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
            </div>
            
            <div id="bmiPreview" class="form-hint" style="margin:-8px 0 12px"></div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Activity level</label>
                    <select name="activity_level" class="form-control">
                        <option value="sedentary">Sedentary (desk job, little exercise)</option>
                        <option value="light">Lightly active (exercise 1–3 days/week)</option>
                        <option value="moderate" selected>Moderate (exercise 3–5 days/week)</option>
                        <option value="active">Active (exercise 6–7 days/week)</option>
                        <option value="very_active">Very active (athlete, physical job)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fitness goal</label>
                    <select name="goal_mode" class="form-control">
                        <option value="lose">Lose weight (−500 kcal/day)</option>
                        <option value="maintain" selected>Maintain weight</option>
                        <option value="gain">Gain muscle (+300 kcal/day)</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;margin-top:6px">
                Create account & calculate goals
            </button>
        </form>
        
        <div class="auth-footer">
            Already have an account? <a href="<?= BASE_URL ?>/signin.php">Sign in</a>
        </div>
    </div>
</div>
</div>

<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script>
    window.addEventListener('load', () => {
        setTimeout(() => {
            const loader = document.getElementById('loader');
            const app = document.getElementById('app');
            if (loader) loader.classList.add('hidden');
            if (app) app.style.opacity = '1';
        }, 300);
        updateBMIPreview();
    });
</script>
</body>
</html>