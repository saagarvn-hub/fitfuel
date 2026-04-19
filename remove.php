<?php
require_once 'includes/config.php';
require_once 'includes/utils.php';

if (!isset($_SESSION['user_id'])) {
    redirect(BASE_URL . '/signin.php');
}

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$from = $_GET['from'] ?? 'index';
$date = $_GET['date'] ?? date('Y-m-d');

if ($id <= 0) {
    flash('error', 'Invalid item.');
    redirect(BASE_URL . '/index.php');
}

$user_id = (int)$_SESSION['user_id'];

if ($type === 'meal') {
    // Delete meal log entry
    $stmt = $conn->prepare("DELETE FROM meal_log WHERE log_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        flash('success', 'Meal removed successfully.');
    } else {
        flash('error', 'Failed to remove meal.');
    }
    
    // Redirect back
    if ($from === 'dashboard') {
        redirect(BASE_URL . '/index.php');
    } else {
        redirect(BASE_URL . '/diary.php?date=' . $date);
    }
    
} elseif ($type === 'recipe') {
    // Check ownership
    $check = $conn->prepare("SELECT user_id, image_path FROM recipes WHERE recipe_id = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $recipe = $check->get_result()->fetch_assoc();
    
    if (!$recipe) {
        flash('error', 'Recipe not found.');
        redirect(BASE_URL . '/dishes.php');
    }
    
    if ($recipe['user_id'] != $user_id) {
        flash('error', 'You don\'t have permission to delete this recipe.');
        redirect(BASE_URL . '/dishes.php');
    }
    
    // Delete image file if exists
    if ($recipe['image_path'] && file_exists(RECIPE_UPLOAD . $recipe['image_path'])) {
        unlink(RECIPE_UPLOAD . $recipe['image_path']);
    }
    
    // Delete recipe and related data
    $conn->begin_transaction();
    try {
        // Delete ratings first
        $stmt1 = $conn->prepare("DELETE FROM recipe_ratings WHERE recipe_id = ?");
        $stmt1->bind_param('i', $id);
        $stmt1->execute();
        
        // Update meal logs to remove recipe reference
        $stmt2 = $conn->prepare("UPDATE meal_log SET recipe_id = NULL WHERE recipe_id = ?");
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        
        // Delete recipe
        $stmt3 = $conn->prepare("DELETE FROM recipes WHERE recipe_id = ? AND user_id = ?");
        $stmt3->bind_param('ii', $id, $user_id);
        $stmt3->execute();
        
        $conn->commit();
        flash('success', 'Recipe deleted successfully.');
    } catch (Exception $e) {
        $conn->rollback();
        flash('error', 'Failed to delete recipe.');
    }
    
    redirect(BASE_URL . '/dishes.php');
    
} else {
    flash('error', 'Invalid request.');
    redirect(BASE_URL . '/index.php');
}
?>