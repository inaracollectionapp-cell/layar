<?php
require_once 'includes/config.php';
require_once 'includes/tripay-functions.php';

header('Content-Type: application/json');

$reference = isset($_GET['reference']) ? sanitize($_GET['reference']) : '';

if (empty($reference)) {
    echo json_encode(['success' => false, 'message' => 'Reference tidak ditemukan']);
    exit();
}

try {
    $result = TripayPayment::getTransactionDetail($reference);
    
    if ($result && $result['success']) {
        $status = $result['data']['status'];
        
        // Update database if paid
        if ($status === 'PAID') {
            $stmt = $conn->prepare("SELECT booking_id FROM transactions WHERE reference = ?");
            $stmt->bind_param("s", $reference);
            $stmt->execute();
            $txResult = $stmt->get_result();
            $transaction = $txResult->fetch_assoc();
            
            if ($transaction) {
                // Update transaction
                $stmt = $conn->prepare("UPDATE transactions SET status = 'PAID', paid_at = NOW(), tripay_response = ? WHERE reference = ?");
                $tripayResponse = json_encode($result['data']);
                $stmt->bind_param("ss", $tripayResponse, $reference);
                $stmt->execute();
                
                // Update booking
                $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'paid', booking_status = 'confirmed' WHERE id = ?");
                $stmt->bind_param("i", $transaction['booking_id']);
                $stmt->execute();
                
                // Update seat status
                $stmt = $conn->prepare("SELECT schedule_id, seats FROM bookings WHERE id = ?");
                $stmt->bind_param("i", $transaction['booking_id']);
                $stmt->execute();
                $bookingResult = $stmt->get_result();
                $booking = $bookingResult->fetch_assoc();
                
                if ($booking) {
                    $seats = json_decode($booking['seats'], true);
                    updateSeatStatus($booking['schedule_id'], $seats, 'booked');
                    updateAvailableSeats($booking['schedule_id']);
                }
            }
        }
        
        echo json_encode(['success' => true, 'status' => $status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengecek status pembayaran']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
