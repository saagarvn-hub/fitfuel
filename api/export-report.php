<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    die('Not logged in');
}

$userId = $_SESSION['user_id'];
$format = $_GET['format'] ?? 'csv';

$meals = getLast7DaysMeals($userId);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="fitfuel_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Meal Name', 'Calories', 'Protein (g)', 'Carbs (g)', 'Fats (g)', 'Meal Time']);

foreach ($meals as $date => $dailyMeals) {
    foreach ($dailyMeals as $meal) {
        fputcsv($output, [
            $date,
            $meal['meal_name'],
            $meal['calories'],
            $meal['protein'],
            $meal['carbs'],
            $meal['fats'],
            $meal['meal_time']
        ]);
    }
}

fclose($output);
?>