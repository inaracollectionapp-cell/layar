<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$scheduleId = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
$selectedSeatsJson = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : '';
$customerName = isset($_POST['customer_name']) ? sanitize($_POST['customer_name']) : '';
$customerEmail = isset($_POST['customer_email']) ? sanitize($_POST['customer_email']) : '';
$customerPhone = isset($_POST['customer_phone']) ? sanitize($_POST['customer_phone']) : '';

if (!$scheduleId || empty($selectedSeatsJson) || empty($customerName) || empty($customerEmail) || empty($customerPhone)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

$selectedSeats = json_decode($selectedSeatsJson, true);

if (!$selectedSeats || count($selectedSeats) === 0) {
    echo json_encode(['success' => false, 'message' => 'Silakan pilih kursi terlebih dahulu']);
    exit();
}

if (!validateEmail($customerEmail)) {
    echo json_encode(['success' => false, 'message' => 'Email tidak valid']);
    exit();
}

$schedule = getScheduleById($scheduleId);

if (!$schedule) {
    echo json_encode(['success' => false, 'message' => 'Jadwal tidak ditemukan']);
    exit();
}

// Check promo status
$today = date('Y-m-d');
$promoStart = $schedule['promo_start_date'];
$promoEnd = $schedule['promo_end_date'];
$isPromoActive = $schedule['is_promo'] == 1 && 
                (!$promoStart || $today >= $promoStart) && 
                (!$promoEnd || $today <= $promoEnd);

// Use promo price if active, otherwise use regular price
$pricePerSeat = $isPromoActive && !empty($schedule['promo_price']) ? $schedule['promo_price'] : $schedule['price'];

$conn->begin_transaction();

try {
    $placeholders = implode(',', array_fill(0, count($selectedSeats), '?'));
    $stmt = $conn->prepare("SELECT seat_label, status FROM seats WHERE schedule_id = ? AND seat_label IN ($placeholders) FOR UPDATE");
    $types = 'i' . str_repeat('s', count($selectedSeats));
    $params = array_merge([$scheduleId], $selectedSeats);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $unavailableSeats = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] !== 'available') {
            $unavailableSeats[] = $row['seat_label'];
        }
    }
    
    if (!empty($unavailableSeats)) {
        throw new Exception('Kursi ' . implode(', ', $unavailableSeats) . ' sudah tidak tersedia');
    }
    
    $totalSeats = count($selectedSeats);
    $totalPrice = $totalSeats * $pricePerSeat;
    $bookingCode = generateBookingCode();
    $expiredAt = date('Y-m-d H:i:s', strtotime('+' . BOOKING_TIMEOUT_MINUTES . ' minutes'));
    
    $stmt = $conn->prepare("INSERT INTO bookings (booking_code, schedule_id, customer_name, customer_email, customer_phone, seats, total_seats, total_price, booking_status, payment_status, expired_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?)");
    $seatsJson = json_encode($selectedSeats);
    $stmt->bind_param("sissssiis", $bookingCode, $scheduleId, $customerName, $customerEmail, $customerPhone, $seatsJson, $totalSeats, $totalPrice, $expiredAt);
    $stmt->execute();
    
    $bookingId = $conn->insert_id;
    
    updateSeatStatus($scheduleId, $selectedSeats, 'reserved');
    updateAvailableSeats($scheduleId);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'booking_code' => $bookingCode,
        'booking_id' => $bookingId,
        'message' => 'Booking berhasil dibuat'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
