<?php
// debug-qr.php - File untuk debugging QR Code
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/ticket-functions.php';

echo "<h2>Debug QR Code Format</h2>";

// Test dengan kode booking yang ada di database
$testCodes = [
    "ISOLA-C326991A",
    "ISOLA-87F8B3FE", 
    "ISOLA-8DA8E951"
];

foreach ($testCodes as $code) {
    echo "<h3>Testing: $code</h3>";
    
    // Cek apakah booking ada
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if ($booking) {
        echo "✅ Booking found<br>";
        echo "Payment Status: " . $booking['payment_status'] . "<br>";
        echo "Booking Status: " . $booking['booking_status'] . "<br>";
        
        // Test validateTicketQRCode function
        $result = validateTicketQRCode($code);
        echo "<pre>Validation Result: ";
        print_r($result);
        echo "</pre>";
        
        // Test format yang berbeda
        $formats = [
            $code,
            $code . "|" . time() . "|ISOLA",
            json_encode(['booking_code' => $code, 'timestamp' => time()]),
            base64_encode(json_encode(['booking_code' => $code, 'timestamp' => time()]))
        ];
        
        foreach ($formats as $format) {
            $extracted = extractBookingCode($format);
            echo "Format: " . substr($format, 0, 50) . "... → Extracted: $extracted<br>";
        }
        
    } else {
        echo "❌ Booking not found<br>";
    }
    echo "<hr>";
}
?>