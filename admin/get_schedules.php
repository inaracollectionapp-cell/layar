<?php
require_once '../includes/config.php';
requireAdminLogin();

header('Content-Type: application/json');

$filmId = isset($_GET['film_id']) ? intval($_GET['film_id']) : 0;

if (!$filmId) {
    echo json_encode(['success' => false, 'message' => 'Film ID tidak valid']);
    exit();
}

$schedules = getFilmSchedules($filmId);
$result = [];

while ($schedule = $schedules->fetch_assoc()) {
    $result[] = [
        'id' => $schedule['id'],
        'show_date' => formatDateIndo($schedule['show_date']),
        'show_time' => formatTime($schedule['show_time']),
        'available_seats' => $schedule['available_seats'],
        'price' => $schedule['price'],
        'is_promo' => $schedule['is_promo'],
        'promo_price' => $schedule['promo_price']
    ];
}

echo json_encode(['success' => true, 'schedules' => $result]);
?>
