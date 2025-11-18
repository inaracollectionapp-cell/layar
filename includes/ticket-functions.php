<?php
require_once __DIR__ . '/config.php';

/**
 * Generate QR Code image untuk tiket individual
 */
function saveTicketQRCode($ticketCode) {
    try {
        $dir = __DIR__ . '/../assets/images/tickets';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $qrImagePath = $dir . '/qr_' . $ticketCode . '.png';
        
        $qrData = $ticketCode;
        
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $qrCode = \Endroid\QrCode\QrCode::create($qrData)
            ->setSize(300)
            ->setMargin(10);
        
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);
        
        $result->saveToFile($qrImagePath);
        
        return 'assets/images/tickets/qr_' . $ticketCode . '.png';
        
    } catch (Exception $e) {
        error_log("QR Code generation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get QR Code URL untuk tiket individual (generate if not exists)
 */
function getTicketQRCodeUrl($ticketCode) {
    $relativePath = 'assets/images/tickets/qr_' . $ticketCode . '.png';
    $absolutePath = __DIR__ . '/../' . $relativePath;
    
    if (!file_exists($absolutePath)) {
        $result = saveTicketQRCode($ticketCode);
        if (!$result) {
            return 'assets/images/placeholder-qr.png';
        }
    }
    
    if (file_exists($absolutePath)) {
        return $relativePath;
    }
    
    return 'assets/images/placeholder-qr.png';
}

/**
 * Extract ticket code dari QR scan - Support format individual ticket
 */
function extractTicketCode($qrData) {
    $qrData = trim($qrData);
    error_log("Original QR Data: " . $qrData);
    
    // Format 1: Individual ticket (ISOLA-XXXXXXXX-A1, ISOLA-XXXXXXXX-B2, dll)
    if (preg_match('/ISOLA-[A-Z0-9]{8}-[A-Z][0-9]+/', $qrData, $matches)) {
        error_log("Individual Ticket matched: " . $matches[0]);
        return $matches[0];
    }
    
    // Format 2: Legacy booking code (ISOLA-XXXXXXXX)
    if (preg_match('/ISOLA-[A-Z0-9]{8}/', $qrData, $matches)) {
        error_log("Booking Code matched: " . $matches[0]);
        return $matches[0];
    }
    
    // Format 3: JSON format
    if (strpos($qrData, 'ticket_code') !== false || strpos($qrData, 'booking_code') !== false) {
        $data = json_decode($qrData, true);
        if ($data && isset($data['ticket_code'])) {
            error_log("JSON Ticket Format matched: " . $data['ticket_code']);
            return $data['ticket_code'];
        }
        if ($data && isset($data['booking_code'])) {
            error_log("JSON Booking Format matched: " . $data['booking_code']);
            return $data['booking_code'];
        }
    }
    
    // Format 4: Cari pattern ISOLA- dalam string apapun
    if (preg_match('/(ISOLA-[A-Z0-9-]+)/', $qrData, $matches)) {
        error_log("Pattern matched: " . $matches[1]);
        return $matches[1];
    }
    
    // Format 5: Raw data
    if (strlen($qrData) > 5) {
        error_log("Using raw data: " . $qrData);
        return $qrData;
    }
    
    error_log("No format matched, returning original");
    return $qrData;
}

/**
 * Validate tiket individual dari QR scan
 */
function validateTicketQRCode($qrData) {
    try {
        $ticketCode = extractTicketCode($qrData);
        
        if (empty($ticketCode)) {
            return ['valid' => false, 'message' => 'QR Code tidak valid - Kode tiket kosong'];
        }
        
        global $conn;
        
        // Cek dulu apakah ini individual ticket atau booking code lama
        if (strpos($ticketCode, '-') !== false && preg_match('/ISOLA-[A-Z0-9]{8}-[A-Z][0-9]+/', $ticketCode)) {
            // Format individual ticket: ISOLA-XXXXXXXX-A1
            $stmt = $conn->prepare("
                SELECT t.*, b.booking_code, b.customer_name, b.payment_status,
                       f.title, f.is_double_feature, f2.title as second_title,
                       s.show_date, s.show_time
                FROM tickets t
                JOIN bookings b ON t.booking_id = b.id
                JOIN schedules s ON b.schedule_id = s.id
                JOIN films f ON s.film_id = f.id
                LEFT JOIN films f2 ON f.second_film_id = f2.id
                WHERE t.ticket_code = ?
            ");
            $stmt->bind_param("s", $ticketCode);
            $stmt->execute();
            $result = $stmt->get_result();
            $ticket = $result->fetch_assoc();
            
            if (!$ticket) {
                return ['valid' => false, 'message' => 'Tiket tidak ditemukan. Kode: ' . $ticketCode];
            }
            
            if ($ticket['payment_status'] !== 'paid') {
                return ['valid' => false, 'message' => 'Tiket belum dibayar'];
            }
            
            if ($ticket['ticket_status'] === 'used') {
                return ['valid' => false, 'message' => 'Tiket sudah digunakan pada ' . date('d/m/Y H:i', strtotime($ticket['used_at']))];
            }
            
            if ($ticket['ticket_status'] === 'cancelled') {
                return ['valid' => false, 'message' => 'Tiket sudah dibatalkan'];
            }
            
            return [
                'valid' => true,
                'message' => 'Tiket valid - Kursi ' . $ticket['seat_label'],
                'ticket' => $ticket,
                'is_individual' => true
            ];
            
        } else {
            // Format lama: booking code (backward compatibility)
            $stmt = $conn->prepare("
                SELECT b.*, f.title, f.is_double_feature, f2.title as second_title,
                       s.show_date, s.show_time 
                FROM bookings b
                JOIN schedules s ON b.schedule_id = s.id
                JOIN films f ON s.film_id = f.id
                LEFT JOIN films f2 ON f.second_film_id = f2.id
                WHERE b.booking_code = ?
            ");
            $stmt->bind_param("s", $ticketCode);
            $stmt->execute();
            $result = $stmt->get_result();
            $booking = $result->fetch_assoc();
            
            if (!$booking) {
                return ['valid' => false, 'message' => 'Tiket tidak ditemukan. Kode: ' . $ticketCode];
            }
            
            if ($booking['payment_status'] !== 'paid') {
                return ['valid' => false, 'message' => 'Tiket belum dibayar'];
            }
            
            if ($booking['booking_status'] === 'used') {
                return ['valid' => false, 'message' => 'Tiket sudah digunakan'];
            }
            
            if ($booking['booking_status'] === 'cancelled') {
                return ['valid' => false, 'message' => 'Tiket sudah dibatalkan'];
            }
            
            return [
                'valid' => true,
                'message' => 'Tiket valid (format lama)',
                'booking' => $booking,
                'is_individual' => false
            ];
        }
        
    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'Error sistem: ' . $e->getMessage()];
    }
}


/**
 * Generate PDF ticket (simple HTML-based) for viewing/downloading
 */
function generateTicketHTML($bookingCode) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT b.*, f.title, f.genre, f.rating, f.duration, f.is_double_feature,
               s.show_date, s.show_time 
        FROM bookings b
        JOIN schedules s ON b.schedule_id = s.id
        JOIN films f ON s.film_id = f.id
        WHERE b.booking_code = ?
    ");
    $stmt->bind_param("s", $bookingCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if (!$booking) {
        return false;
    }
    
    // Set default values untuk menghindari undefined array key
    if (!isset($booking['is_double_feature'])) {
        $booking['is_double_feature'] = 0;
    }
    if (!isset($booking['second_title'])) {
        $booking['second_title'] = '';
    }
    
    $seats = json_decode($booking['seats'], true);
    $qrCodeUrl = getTicketQRCodeUrl($bookingCode);
    if (!$qrCodeUrl) {
        $qrCodeUrl = 'assets/images/placeholder-qr.png';
    }
    $siteName = getSetting('site_name', 'ISOLA SCREEN');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background: #1a1a1a;
                color: #ffffff;
            }
            .ticket {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 20px;
                padding: 30px;
                margin: 20px 0;
            }
            .ticket-header {
                text-align: center;
                border-bottom: 2px dashed #ffffff;
                padding-bottom: 20px;
                margin-bottom: 20px;
            }
            .ticket-header h1 {
                margin: 0;
                font-size: 28px;
            }
            .ticket-body {
                margin: 20px 0;
            }
            .ticket-row {
                display: flex;
                justify-content: space-between;
                margin: 10px 0;
                font-size: 14px;
            }
            .ticket-label {
                font-weight: bold;
                opacity: 0.8;
            }
            .ticket-value {
                font-weight: bold;
            }
            .ticket-qr {
                text-align: center;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 2px dashed #ffffff;
            }
            .ticket-qr img {
                background: white;
                padding: 10px;
                border-radius: 10px;
            }
            .seats {
                background: rgba(255,255,255,0.1);
                padding: 10px;
                border-radius: 10px;
                margin: 10px 0;
                text-align: center;
                font-size: 18px;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="ticket">
            <div class="ticket-header">
                <h1>' . htmlspecialchars($siteName) . '</h1>
                <p style="margin: 5px 0; font-size: 12px;">E-TICKET</p>
            </div>
            
            <div class="ticket-body">
                <h2 style="margin: 0 0 15px 0; font-size: 24px;">' . 
                htmlspecialchars(
                    ($booking['is_double_feature'] == 1 && !empty($booking['second_title'])) 
                    ? $booking['title'] . ' & ' . $booking['second_title']
                    : $booking['title']
                ) . '</h2>
                
                <div class="ticket-row">
                    <span class="ticket-label">Kode Booking:</span>
                    <span class="ticket-value">' . htmlspecialchars($booking['booking_code']) . '</span>
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Tanggal:</span>
                    <span class="ticket-value">' . formatDateIndo($booking['show_date']) . '</span>
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Waktu:</span>
                    <span class="ticket-value">' . formatTime($booking['show_time']) . '</span>
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Nama:</span>
                    <span class="ticket-value">' . htmlspecialchars($booking['customer_name']) . '</span>
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Genre:</span>
                    <span class="ticket-value">' . htmlspecialchars($booking['genre']) . '</span>
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Rating:</span>
                    <span class="ticket-value">' . htmlspecialchars($booking['rating']) . '</span>
                </div>
                
                <div class="seats">
                    Kursi: ' . implode(', ', $seats) . '
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Jumlah Tiket:</span>
                    <span class="ticket-value">' . $booking['total_seats'] . ' tiket</span>
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Total:</span>
                    <span class="ticket-value">' . formatCurrency($booking['total_price']) . '</span>
                </div>
            </div>
            
            <div class="ticket-qr">
                <p style="margin: 0 0 10px 0; font-size: 14px;">Tunjukkan QR Code ini di pintu masuk</p>
                <img src="' . $qrCodeUrl . '" alt="QR Code" width="200" height="200">
                <p style="margin: 10px 0 0 0; font-size: 12px; opacity: 0.8;">Scan untuk validasi tiket</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}

/**
 * Generate ticket HTML for email with embedded QR code - QUERY DIPERBAIKI
 */
function generateTicketEmailHTML($bookingCode, $hasEmbeddedQR = false) {
    global $conn;
    
    // QUERY DIPERBAIKI: Tambahkan second_title untuk double feature
    $stmt = $conn->prepare("
        SELECT b.*, f.title, f.genre, f.rating, f.duration, f.is_double_feature, f.second_film_id,
               f2.title as second_title,
               s.show_date, s.show_time 
        FROM bookings b
        JOIN schedules s ON b.schedule_id = s.id
        JOIN films f ON s.film_id = f.id
        LEFT JOIN films f2 ON f.second_film_id = f2.id
        WHERE b.booking_code = ?
    ");
    $stmt->bind_param("s", $bookingCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if (!$booking) {
        return false;
    }
    
    // Set default values untuk menghindari undefined array key
    if (!isset($booking['is_double_feature'])) {
        $booking['is_double_feature'] = 0;
    }
    if (!isset($booking['second_title'])) {
        $booking['second_title'] = '';
    }
    
    $seats = json_decode($booking['seats'], true);
    $siteName = getSetting('site_name', 'ISOLA SCREEN');
    
    // Generate film title untuk double feature - DIPERBAIKI
    $filmTitle = ($booking['is_double_feature'] == 1 && !empty($booking['second_title'])) 
        ? $booking['title'] . ' & ' . $booking['second_title']
        : $booking['title'];
    
    // Use embedded QR if available, otherwise use local path
    if ($hasEmbeddedQR) {
        $qrImageTag = '<img src="cid:qr_code" alt="QR Code" width="200" height="200">';
    } else {
        $qrPath = getTicketQRCodeUrl($bookingCode);
        $qrImageTag = $qrPath 
            ? '<img src="' . $qrPath . '" alt="QR Code" width="200" height="200">'
            : '<p>QR Code tidak tersedia</p>';
    }
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background: #f5f5f5;
                color: #333;
            }
            .ticket {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 20px;
                padding: 30px;
                margin: 20px 0;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            }
            .ticket-header {
                text-align: center;
                border-bottom: 2px dashed #ffffff;
                padding-bottom: 20px;
                margin-bottom: 20px;
                color: white;
            }
            .ticket-header h1 {
                margin: 0;
                font-size: 28px;
            }
            .ticket-body {
                margin: 20px 0;
                color: white;
            }
            .ticket-row {
                margin: 15px 0;
                font-size: 14px;
            }
            .ticket-label {
                font-weight: bold;
                opacity: 0.9;
                display: block;
                margin-bottom: 5px;
            }
            .ticket-value {
                font-weight: bold;
                font-size: 16px;
            }
            .ticket-qr {
                text-align: center;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 2px dashed #ffffff;
                color: white;
            }
            .ticket-qr img {
                background: white;
                padding: 10px;
                border-radius: 10px;
                margin: 10px 0;
            }
            .seats {
                background: rgba(255,255,255,0.2);
                padding: 15px;
                border-radius: 10px;
                margin: 15px 0;
                text-align: center;
                font-size: 20px;
                font-weight: bold;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                padding-top: 20px;
                color: white;
                font-size: 12px;
                opacity: 0.8;
            }
            .film-title {
                text-align: center;
                font-size: 24px;
                margin: 0 0 20px 0;
                font-weight: bold;
                line-height: 1.3;
            }
        </style>
    </head>
    <body>
        <div class="ticket">
            <div class="ticket-header">
                <h1>' . htmlspecialchars($siteName) . '</h1>
                <p style="margin: 5px 0; font-size: 14px;">E-TICKET</p>
            </div>
            
            <div class="ticket-body">
                <div class="film-title">' . htmlspecialchars($filmTitle) . '</div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Kode Booking</span>
                    <span class="ticket-value">' . htmlspecialchars($booking['booking_code']) . '</span>
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Tanggal Tayang</span>
                    <span class="ticket-value">' . formatDateIndo($booking['show_date']) . '</span>
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Waktu Tayang</span>
                    <span class="ticket-value">' . formatTime($booking['show_time']) . '</span>
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Nama Pemesan</span>
                    <span class="ticket-value">' . htmlspecialchars($booking['customer_name']) . '</span>
                </div>
                
                <div class="seats">
                    <div style="font-size: 14px; opacity: 0.8; margin-bottom: 5px;">KURSI</div>
                    ' . implode(' &bull; ', $seats) . '
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Jumlah Tiket</span>
                    <span class="ticket-value">' . $booking['total_seats'] . ' tiket</span>
                </div>
                
                <div class="ticket-row">
                    <span class="ticket-label">Total Pembayaran</span>
                    <span class="ticket-value" style="font-size: 20px; color: #ffd700;">' . formatCurrency($booking['total_price']) . '</span>
                </div>
            </div>
            
            <div class="ticket-qr">
                <p style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold;">TUNJUKKAN QR CODE INI DI PINTU MASUK</p>
                ' . $qrImageTag . '
                <p style="margin: 10px 0 0 0; font-size: 12px; opacity: 0.8;">Scan untuk validasi tiket</p>
            </div>
            
            <div class="footer">
                <p>Terima kasih telah memesan tiket di ' . htmlspecialchars($siteName) . '</p>
                <p>Harap tiba 15 menit sebelum film dimulai</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}
?>