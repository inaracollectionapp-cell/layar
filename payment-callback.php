<?php
require_once 'includes/config.php';
require_once 'includes/tripay-config.php';

// Log callback untuk debugging
file_put_contents('tripay_callback.log', date('Y-m-d H:i:s') . " - " . file_get_contents('php://input') . "\n", FILE_APPEND);

// Callback dari Tripay
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validasi signature
$signature = isset($_SERVER['HTTP_X_CALLBACK_SIGNATURE']) ? $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] : '';
$privateKey = TripayConfig::getPrivateKey();
$calculatedSignature = hash_hmac('sha256', $json, $privateKey);

if ($signature !== $calculatedSignature) {
    http_response_code(400);
    file_put_contents('tripay_callback.log', "INVALID SIGNATURE\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit();
}

// Pastikan ini adalah callback event payment
if (!isset($_SERVER['HTTP_X_CALLBACK_EVENT']) || $_SERVER['HTTP_X_CALLBACK_EVENT'] !== 'payment_status') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid callback event']);
    exit();
}

// Process callback
if ($data && isset($data['reference'])) {
    $reference = $data['reference'];
    $status = $data['status'];
    $merchantRef = $data['merchant_ref'];
    
    try {
        // Get booking from transaction
        $stmt = $conn->prepare("SELECT booking_id FROM transactions WHERE reference = ? OR merchant_ref = ?");
        $stmt->bind_param("ss", $reference, $merchantRef);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        
        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            exit();
        }
        
        $bookingId = $transaction['booking_id'];
        
        // Update transaction status
        $stmt = $conn->prepare("UPDATE transactions SET status = ?, paid_at = NOW(), tripay_response = ? WHERE reference = ? OR merchant_ref = ?");
        $tripayResponse = json_encode($data);
        $stmt->bind_param("ssss", $status, $tripayResponse, $reference, $merchantRef);
        $stmt->execute();
        
        // Update booking status based on payment status
        if ($status === 'PAID') {
            $paymentStatus = 'paid';
            $bookingStatus = 'confirmed';
            $seatStatus = 'booked';
            
            // Update booking
            $stmt = $conn->prepare("UPDATE bookings SET payment_status = ?, booking_status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $paymentStatus, $bookingStatus, $bookingId);
            $stmt->execute();
            
            // Update seats
            $stmt = $conn->prepare("SELECT schedule_id, seats FROM bookings WHERE id = ?");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $bookingResult = $stmt->get_result();
            $booking = $bookingResult->fetch_assoc();
            
            if ($booking) {
                $seats = json_decode($booking['seats'], true);
                updateSeatStatus($booking['schedule_id'], $seats, $seatStatus);
                updateAvailableSeats($booking['schedule_id']);
                
                // Generate tiket individual per kursi
                generateTicketsForBooking($bookingId);
                
                // Kirim email tiket
                require_once 'includes/email-functions.php';
                sendTicketEmail($bookingId);
            }
            
            file_put_contents('tripay_callback.log', "PAYMENT SUCCESS - Ref: $reference\n", FILE_APPEND);
            
        } elseif ($status === 'EXPIRED' || $status === 'FAILED') {
            $paymentStatus = 'failed';
            $bookingStatus = 'cancelled';
            $seatStatus = 'available';
            
            $stmt = $conn->prepare("UPDATE bookings SET payment_status = ?, booking_status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $paymentStatus, $bookingStatus, $bookingId);
            $stmt->execute();
            
            $stmt = $conn->prepare("SELECT schedule_id, seats FROM bookings WHERE id = ?");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
            $bookingResult = $stmt->get_result();
            $booking = $bookingResult->fetch_assoc();
            
            if ($booking) {
                $seats = json_decode($booking['seats'], true);
                updateSeatStatus($booking['schedule_id'], $seats, $seatStatus);
                updateAvailableSeats($booking['schedule_id']);
            }
            
            file_put_contents('tripay_callback.log', "PAYMENT FAILED - Ref: $reference\n", FILE_APPEND);
        }
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        http_response_code(500);
        file_put_contents('tripay_callback.log', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>