<?php
require_once 'includes/config.php';
require_once 'includes/tripay-functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$bookingCode = isset($input['booking_code']) ? sanitize($input['booking_code']) : '';

if (empty($bookingCode)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

$booking = getBookingByCode($bookingCode);

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking tidak ditemukan']);
    exit();
}

if ($booking['payment_status'] === 'paid') {
    echo json_encode(['success' => false, 'message' => 'Booking sudah dibayar']);
    exit();
}

try {
    $result = TripayPayment::createTransaction(
        $bookingCode,
        $booking['total_price'],
        $booking['customer_name'],
        $booking['customer_email'],
        $booking['customer_phone']
    );
    
    if ($result && $result['success']) {
        // Save transaction to database
        $reference = $result['data']['reference'];
        $merchantRef = $result['data']['merchant_ref'];
        $qrString = isset($result['data']['qr_string']) ? $result['data']['qr_string'] : '';
        $qrUrl = isset($result['data']['qr_url']) ? $result['data']['qr_url'] : '';
        
        $stmt = $conn->prepare("INSERT INTO transactions (booking_id, reference, merchant_ref, amount, qr_string, qr_url, status, tripay_response) VALUES (?, ?, ?, ?, ?, ?, 'UNPAID', ?)");
        $tripayResponse = json_encode($result['data']);
        $stmt->bind_param("issdsss", $booking['id'], $reference, $merchantRef, $booking['total_price'], $qrString, $qrUrl, $tripayResponse);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'reference' => $reference,
                'qr_string' => $qrString,
                'qr_url' => $qrUrl,
                'amount' => $result['data']['amount'],
                'expired_time' => $result['data']['expired_time']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Gagal membuat transaksi']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
