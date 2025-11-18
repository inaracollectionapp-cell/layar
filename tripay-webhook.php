<?php
// tripay-webhook.php - Complete Webhook Handler
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/tripay-functions.php';

// Set headers immediately
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Callback-Signature');

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/tripay-webhook-error.log');

// Log incoming request
file_put_contents(__DIR__ . '/tripay-webhook.log', 
    "[" . date('Y-m-d H:i:s') . "] INCOMING REQUEST\n" .
    "Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n" .
    "URI: " . ($_SERVER['REQUEST_URI'] ?? 'UNKNOWN') . "\n" .
    "IP: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "\n",
FILE_APPEND);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get raw input
$input = file_get_contents('php://input');
$callback = json_decode($input, true);

// Log callback data
file_put_contents(__DIR__ . '/tripay-webhook.log', 
    "[" . date('Y-m-d H:i:s') . "] CALLBACK DATA\n" . $input . "\n\n",
FILE_APPEND);

// Validate callback data
if (empty($callback) || !isset($callback['merchant_ref'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid callback data']);
    exit();
}

// Validate signature - AMBIL DARI DATABASE (TANPA HARCODE)
$privateKey = getSetting('tripay_private_key');
$signature = isset($_SERVER['HTTP_X_CALLBACK_SIGNATURE']) ? $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] : '';

if (empty($privateKey)) {
    error_log("Tripay private key not set");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error - Tripay private key missing']);
    exit();
}

$calculatedSignature = hash_hmac('sha256', $input, $privateKey);

file_put_contents(__DIR__ . '/tripay-webhook.log', 
    "[" . date('Y-m-d H:i:s') . "] SIGNATURE VERIFICATION\n" .
    "Received: $signature\n" .
    "Calculated: $calculatedSignature\n",
FILE_APPEND);

if ($signature !== $calculatedSignature) {
    error_log("Signature mismatch: $signature vs $calculatedSignature");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit();
}

$merchantRef = $callback['merchant_ref'];
$status = $callback['status'];
$reference = $callback['reference'] ?? '';

try {
    // Extract booking code from merchant_ref
    $parts = explode('-', $merchantRef);
    $bookingCode = $merchantRef; // fallback
    
    if (count($parts) >= 3 && $parts[0] === 'BOOK' && $parts[1] === 'ISOLA') {
        $bookingCode = $parts[2];
    }
    
    error_log("Processing webhook for booking: " . $bookingCode . " with status: " . $status);
    
    // Update transaction status
    $stmt = $conn->prepare("UPDATE transactions SET status = ?, tripay_reference = ?, updated_at = NOW() WHERE merchant_ref = ?");
    $stmt->bind_param("sss", $status, $reference, $merchantRef);
    $stmt->execute();
    
    if ($status === 'PAID') {
        // Update booking status to paid
        $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'paid', booking_status = 'confirmed', paid_at = NOW() WHERE booking_code = ?");
        $stmt->bind_param("s", $bookingCode);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            error_log("Booking updated to paid: " . $bookingCode);
            
            // Get booking data
            $booking = getBookingByCode($bookingCode);
            if ($booking) {
                // Generate tiket individual untuk setiap kursi
                generateTicketsForBooking($booking['id']);
                error_log("Individual tickets generated for booking: " . $bookingCode);
                
                // Send email dengan semua tiket
                require_once 'includes/email-functions.php';
                $emailResult = sendTicketEmail($bookingCode);
                error_log("Email sending: " . ($emailResult ? 'SUCCESS' : 'FAILED'));
                
                // Update seats status
                if ($booking['schedule_id']) {
                    $seats = json_decode($booking['seats'], true);
                    if ($seats) {
                        updateSeatStatus($booking['schedule_id'], $seats, 'booked');
                        updateAvailableSeats($booking['schedule_id']);
                        error_log("Seats updated for booking: " . $bookingCode);
                    }
                }
            }
            
            // Log success
            file_put_contents(__DIR__ . '/tripay-webhook-success.log', 
                "[" . date('Y-m-d H:i:s') . "] SUCCESS\n" .
                "Booking: $bookingCode\n" .
                "QR Code: " . ($qrResult ? 'YES' : 'NO') . "\n" .
                "Email: " . ($emailResult ? 'YES' : 'NO') . "\n\n",
            FILE_APPEND);
        }
        
    } elseif ($status === 'EXPIRED') {
        // Update status expired
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'expired' WHERE booking_code = ?");
        $stmt->bind_param("s", $bookingCode);
        $stmt->execute();
        
        // Release seats
        $booking = getBookingByCode($bookingCode);
        if ($booking && $booking['schedule_id']) {
            $seats = json_decode($booking['seats'], true);
            if ($seats) {
                updateSeatStatus($booking['schedule_id'], $seats, 'available');
                updateAvailableSeats($booking['schedule_id']);
                error_log("Seats released for expired booking: " . $bookingCode);
            }
        }
        
    } elseif ($status === 'FAILED') {
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE booking_code = ?");
        $stmt->bind_param("s", $bookingCode);
        $stmt->execute();
    }
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Callback processed successfully']);
    error_log("Webhook processed successfully for: " . $bookingCode);
    
} catch (Exception $e) {
    error_log("Webhook Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>