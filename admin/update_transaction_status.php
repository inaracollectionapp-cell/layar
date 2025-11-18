<?php
require_once '../includes/config.php';
requireAdminLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';

if (!$id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

try {
    $conn->begin_transaction();
    
    // Get transaction details
    $stmt = $conn->prepare("SELECT t.*, b.schedule_id, b.seats FROM transactions t 
                           JOIN bookings b ON t.booking_id = b.id 
                           WHERE t.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    
    if (!$transaction) {
        throw new Exception('Transaksi tidak ditemukan');
    }
    
    // Update transaction status
    $stmt = $conn->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    
    // Update booking status based on transaction status
    if ($status === 'cancelled') {
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled', payment_status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $transaction['booking_id']);
        $stmt->execute();
        
        // Release seats - with proper validation
        $seats = json_decode($transaction['seats'], true);
        if ($seats && is_array($seats) && count($seats) > 0 && $transaction['schedule_id']) {
            updateSeatStatus($transaction['schedule_id'], $seats, 'available');
            updateAvailableSeats($transaction['schedule_id']);
        }
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Status transaksi berhasil diupdate']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
