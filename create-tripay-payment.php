<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$bookingCode = sanitize($input['booking_code']);

// Get booking data
$booking = getBookingByCode($bookingCode);
if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking tidak ditemukan']);
    exit();
}

// Get Tripay configuration
$apiKey = getSetting('tripay_api_key');
$privateKey = getSetting('tripay_private_key');
$merchantCode = getSetting('tripay_merchant_code');
$environment = getSetting('tripay_environment', 'sandbox');

if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
    echo json_encode(['success' => false, 'message' => 'Konfigurasi pembayaran belum lengkap']);
    exit();
}

// Check if transaction already exists for this booking
$existingTransaction = getExistingTransaction($booking['id']);
if ($existingTransaction) {
    echo json_encode([
        'success' => true,
        'reference' => $existingTransaction['reference'],
        'qr_url' => $existingTransaction['qr_url'],
        'merchant_ref' => $existingTransaction['merchant_ref']
    ]);
    exit();
}

// Generate merchant reference and order_id
$merchantRef = 'BOOK-' . $bookingCode . '-' . time();
$orderId = 'ORDER-' . $bookingCode . '-' . time(); // Generate unique order_id

try {
    // Prepare payment data
    $amount = intval($booking['total_price']);
    $seats = json_decode($booking['seats'], true);
    
    $data = [
        'method' => 'QRIS',
        'merchant_ref' => $merchantRef,
        'amount' => $amount,
        'customer_name' => $booking['customer_name'],
        'customer_email' => $booking['customer_email'],
        'customer_phone' => $booking['customer_phone'],
        'order_items' => [
            [
                'name' => 'Tiket ' . $booking['title'],
                'price' => $amount,
                'quantity' => 1
            ]
        ],
        'return_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/booking-success.php?booking=' . $bookingCode,
        'expired_time' => time() + (24 * 60 * 60), // 24 hours
        'signature' => hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey)
    ];
    
    // Create request to Tripay
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_URL => $environment === 'production' 
            ? 'https://tripay.co.id/api/transaction/create'
            : 'https://tripay.co.id/api-sandbox/transaction/create',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        throw new Exception('CURL Error: ' . $error);
    }
    
    $result = json_decode($response, true);
    
    if (!$result['success']) {
        throw new Exception($result['message'] ?? 'Unknown error from Tripay');
    }
    
    $transaction = $result['data'];
    
    // Save to database - include order_id in the INSERT
    $stmt = $conn->prepare("INSERT INTO transactions (booking_id, order_id, merchant_ref, reference, amount, qr_url, status, tripay_response) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdsss", 
        $booking['id'], 
        $orderId, // Add order_id here
        $merchantRef, 
        $transaction['reference'], 
        $amount,
        $transaction['qr_url'],
        $transaction['status'],
        $response
    );
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'reference' => $transaction['reference'],
        'qr_url' => $transaction['qr_url'],
        'merchant_ref' => $merchantRef
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// Helper function to check existing transaction
function getExistingTransaction($bookingId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE booking_id = ? AND status IN ('UNPAID', 'PENDING') ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}
?>