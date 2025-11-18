<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

$reference = isset($_GET['reference']) ? sanitize($_GET['reference']) : '';

if (empty($reference)) {
    echo json_encode(['success' => false, 'message' => 'Reference tidak valid']);
    exit();
}

// Get Tripay configuration
$apiKey = getSetting('tripay_api_key');
$environment = getSetting('tripay_environment', 'sandbox');

try {
    // Check payment status from Tripay
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_URL => $environment === 'production'
            ? 'https://tripay.co.id/api/transaction/detail?reference=' . $reference
            : 'https://tripay.co.id/api-sandbox/transaction/detail?reference=' . $reference,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        throw new Exception('CURL Error: ' . $error);
    }
    
    $result = json_decode($response, true);
    
    if ($result['success']) {
        $transaction = $result['data'];
        
        // Update database if paid
        if ($transaction['status'] === 'PAID') {
            $stmt = $conn->prepare("UPDATE transactions SET status = 'PAID', paid_at = NOW() WHERE reference = ?");
            $stmt->bind_param("s", $reference);
            $stmt->execute();
            
            // Update booking status
            $stmt2 = $conn->prepare("SELECT booking_id FROM transactions WHERE reference = ?");
            $stmt2->bind_param("s", $reference);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $transactionData = $result2->fetch_assoc();
            
            if ($transactionData) {
                $stmt3 = $conn->prepare("UPDATE bookings SET payment_status = 'paid', booking_status = 'confirmed' WHERE id = ?");
                $stmt3->bind_param("i", $transactionData['booking_id']);
                $stmt3->execute();
            }
        }
        
        echo json_encode([
            'success' => true,
            'status' => $transaction['status'],
            'data' => $transaction
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>