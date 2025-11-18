<?php
require_once '../includes/config.php';
requireAdminLogin();

header('Content-Type: application/json');

$scheduleId = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;

if (!$scheduleId) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID tidak valid']);
    exit();
}

$seats = getSeats($scheduleId);
$seatsByRow = [];

while ($seat = $seats->fetch_assoc()) {
    $row = $seat['seat_row'];
    if (!isset($seatsByRow[$row])) {
        $seatsByRow[$row] = [];
    }
    $seatsByRow[$row][] = [
        'seat_label' => $seat['seat_label'],
        'seat_number' => $seat['seat_number'],
        'status' => $seat['status']
    ];
}

echo json_encode(['success' => true, 'seats' => $seatsByRow]);
?>
