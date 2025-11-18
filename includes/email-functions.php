<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ticket-functions.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send ticket email to customer
 */
function sendTicketEmail($bookingCode) {
    global $conn;
    
    // Get booking details - QUERY DIPERBAIKI
    $stmt = $conn->prepare("
        SELECT b.*, f.title, f.genre, f.is_double_feature, f.second_film_id,
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
    
    // Set default values untuk menghindari undefined array key
    if (!isset($booking['is_double_feature'])) {
        $booking['is_double_feature'] = 0;
    }
    if (!isset($booking['second_title'])) {
        $booking['second_title'] = '';
    }
    
    if (!$booking) {
        error_log("Email Error: Booking not found - " . $bookingCode);
        return false;
    }
    
    if ($booking['payment_status'] !== 'paid') {
        error_log("Email Error: Payment not paid - " . $bookingCode);
        return false;
    }
    
    // Load PHPMailer
    require __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings - AMBIL DARI DATABASE
        $mail->isSMTP();
        
        // Konfigurasi SMTP dari database (TANPA HARCODE)
        $smtpHost = getSetting('smtp_host');
        $smtpUsername = getSetting('smtp_username');
        $smtpPassword = getSetting('smtp_password');
        $smtpPort = intval(getSetting('smtp_port'));
        
        // Validasi config
        if (empty($smtpHost) || empty($smtpUsername) || empty($smtpPassword)) {
            throw new Exception("SMTP configuration incomplete");
        }
        
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->Port = $smtpPort;
        
        // Untuk email hosting, gunakan STARTTLS untuk port 587
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        
        // Timeout settings
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;
        
        // SSL/TLS options untuk kompatibilitas hosting
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Debug mode
        $mail->SMTPDebug = 2;
        $debugOutput = '';
        $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
            $debugOutput .= "$str\n";
            error_log("PHPMailer Debug: $str");
        };
        
        // Charset
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Recipients - AMBIL DARI DATABASE
        $siteName = getSetting('site_name');
        $siteEmail = getSetting('site_email');
        
        $mail->setFrom($siteEmail, $siteName);
        $mail->addAddress($booking['customer_email'], $booking['customer_name']);
        $mail->addReplyTo($siteEmail, $siteName);
        
        // Add CC jika diperlukan
        $ccEmail = getSetting('email_cc', '');
        if (!empty($ccEmail)) {
            $mail->addCC($ccEmail);
        }
        
        // Content
        $mail->isHTML(true);
        
        // Generate film title untuk double feature - DIPERBAIKI
        $filmTitle = generateFilmTitle($booking);
        $mail->Subject = 'E-Ticket: ' . $filmTitle . ' - ' . $bookingCode;
        
        // Get semua tiket individual untuk booking ini
        $ticketsResult = getTicketsByBookingCode($bookingCode);
        $tickets = [];
        while ($row = $ticketsResult->fetch_assoc()) {
            $tickets[] = $row;
        }
        
        // Embed QR code untuk setiap tiket individual
        $embeddedQRs = [];
        foreach ($tickets as $index => $ticket) {
            $qrImagePath = getTicketQRCodeUrl($ticket['ticket_code']);
            
            if (!$qrImagePath || !file_exists(__DIR__ . '/../' . $qrImagePath)) {
                // Generate QR jika belum ada
                $qrImagePath = saveTicketQRCode($ticket['ticket_code']);
            }
            
            if ($qrImagePath && file_exists(__DIR__ . '/../' . $qrImagePath)) {
                try {
                    $cid = 'qr_ticket_' . $index;
                    $mail->addEmbeddedImage(
                        __DIR__ . '/../' . $qrImagePath, 
                        $cid, 
                        'qr_' . $ticket['seat_label'] . '.png',
                        'base64',
                        'image/png'
                    );
                    $embeddedQRs[$ticket['seat_label']] = $cid;
                    error_log("QR Code embedded for ticket: " . $ticket['ticket_code']);
                } catch (Exception $e) {
                    error_log("QR Code embedding failed: " . $e->getMessage());
                }
            }
        }
        
        // Generate email HTML dengan semua tiket individual
        $ticketHTML = generateMultipleTicketsEmailHTML($booking, $tickets, $embeddedQRs);
        if (!$ticketHTML) {
            throw new Exception("Failed to generate tickets HTML");
        }
        
        $mail->Body = $ticketHTML;
        
        // Alternative text content - DIPERBAIKI untuk double feature
        $seats = json_decode($booking['seats'], true);
        $filmTitle = generateFilmTitle($booking);
        $mail->AltBody = "
E-TICKET - {$siteName}

Kode Booking: {$booking['booking_code']}
Film: {$filmTitle}
Tanggal: " . formatDateIndo($booking['show_date']) . "
Waktu: " . formatTime($booking['show_time']) . "
Kursi: " . implode(', ', $seats) . "
Total: " . formatCurrency($booking['total_price']) . "

Tunjukkan email ini di pintu masuk bioskop.

Terima kasih telah memesan tiket di {$siteName}
        ";
        
        // Send email
        if ($mail->send()) {
            // Update email sent status
            $stmt = $conn->prepare("UPDATE bookings SET email_sent = 1, email_sent_at = NOW() WHERE booking_code = ?");
            $stmt->bind_param("s", $bookingCode);
            $stmt->execute();
            
            // Save debug log
            file_put_contents(__DIR__ . '/../email-success.log', 
                "[" . date('Y-m-d H:i:s') . "] EMAIL SENT SUCCESS\n" .
                "To: " . $booking['customer_email'] . "\n" .
                "Booking: " . $bookingCode . "\n" .
                "Film: " . $filmTitle . "\n" .
                "SMTP Config - Host: " . $smtpHost . ", Port: " . $smtpPort . "\n" .
                "SMTP Debug:\n" . $debugOutput . "\n\n",
            FILE_APPEND);
            
            error_log("Email sent successfully to: " . $booking['customer_email'] . " for booking: " . $bookingCode);
            return true;
        } else {
            // Save error log
            file_put_contents(__DIR__ . '/../email-error.log', 
                "[" . date('Y-m-d H:i:s') . "] EMAIL SEND FAILED\n" .
                "To: " . $booking['customer_email'] . "\n" .
                "Booking: " . $bookingCode . "\n" .
                "Film: " . $filmTitle . "\n" .
                "SMTP Config - Host: " . $smtpHost . ", Port: " . $smtpPort . "\n" .
                "Error: " . $mail->ErrorInfo . "\n" .
                "SMTP Debug:\n" . $debugOutput . "\n\n",
            FILE_APPEND);
            
            error_log("Email send failed: " . $mail->ErrorInfo);
            return false;
        }
        
    } catch (Exception $e) {
        // Save exception log
        $filmTitle = isset($booking) ? generateFilmTitle($booking) : 'N/A';
        file_put_contents(__DIR__ . '/../email-error.log', 
            "[" . date('Y-m-d H:i:s') . "] EMAIL EXCEPTION\n" .
            "Booking: " . $bookingCode . "\n" .
            "Film: " . $filmTitle . "\n" .
            "SMTP Config - Host: " . ($smtpHost ?? 'N/A') . ", Port: " . ($smtpPort ?? 'N/A') . "\n" .
            "Exception: " . $e->getMessage() . "\n" .
            "PHPMailer Error: " . ($mail->ErrorInfo ?? 'N/A') . "\n\n",
        FILE_APPEND);
        
        error_log("Email Exception for booking {$bookingCode}: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate film title based on double feature status
 */
function generateFilmTitle($booking) {
    if ($booking['is_double_feature'] == 1 && !empty($booking['second_title'])) {
        return $booking['title'] . ' & ' . $booking['second_title'];
    } else {
        return $booking['title'];
    }
}

/**
 * Test email configuration
 */
function testEmailConfiguration($testEmail) {
    require __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Konfigurasi SMTP dari database (TANPA HARCODE)
        $smtpHost = getSetting('smtp_host');
        $smtpUsername = getSetting('smtp_username');
        $smtpPassword = getSetting('smtp_password');
        $smtpPort = intval(getSetting('smtp_port'));
        
        // Validasi config
        if (empty($smtpHost) || empty($smtpUsername) || empty($smtpPassword)) {
            return [
                'success' => false, 
                'message' => 'SMTP configuration incomplete. Please check settings.',
                'debug' => ''
            ];
        }
        
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->Port = $smtpPort;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        
        // Timeout dan options
        $mail->Timeout = 30;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Enable debugging untuk test
        $mail->SMTPDebug = 2;
        $debugOutput = '';
        $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
            $debugOutput .= "$str\n";
            error_log("PHPMailer Test: $str");
        };
        
        $siteName = getSetting('site_name');
        $siteEmail = getSetting('site_email');
        
        $mail->setFrom($siteEmail, $siteName);
        $mail->addAddress($testEmail);
        $mail->addReplyTo($siteEmail, $siteName);
        
        $mail->isHTML(true);
        $mail->Subject = 'Test Email Configuration - ' . $siteName;
        $mail->Body    = '
            <h1>Test Email Berhasil!</h1>
            <p>Konfigurasi email Anda sudah benar.</p>
            <p><strong>SMTP Host:</strong> ' . $smtpHost . '</p>
            <p><strong>SMTP Port:</strong> ' . $smtpPort . '</p>
            <p><strong>Encryption:</strong> TLS</p>
            <p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>
        ';
        $mail->AltBody = 'Test Email Berhasil! Konfigurasi email Anda sudah benar. Timestamp: ' . date('Y-m-d H:i:s');
        
        if ($mail->send()) {
            return [
                'success' => true, 
                'message' => 'Email test berhasil dikirim ke ' . $testEmail,
                'debug' => $debugOutput
            ];
        } else {
            return [
                'success' => false, 
                'message' => "Email gagal dikirim: {$mail->ErrorInfo}",
                'debug' => $debugOutput
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => "Email exception: {$e->getMessage()}",
            'debug' => $debugOutput ?? ''
        ];
    }
}

/**
 * Get email settings for display
 */
function getEmailSettings() {
    return [
        'smtp_host' => getSetting('smtp_host'),
        'smtp_username' => getSetting('smtp_username'),
        'smtp_port' => getSetting('smtp_port'),
        'smtp_encryption' => 'tls',
        'site_email' => getSetting('site_email'),
        'site_name' => getSetting('site_name')
    ];
}

/**
 * Generate email HTML untuk multiple tiket individual
 */
function generateMultipleTicketsEmailHTML($booking, $tickets, $embeddedQRs) {
    $siteName = getSetting('site_name', 'ISOLA SCREEN');
    
    $filmTitle = ($booking['is_double_feature'] == 1 && !empty($booking['second_title'])) 
        ? $booking['title'] . ' & ' . $booking['second_title']
        : $booking['title'];
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #7c3aed 0%, #dc2626 100%);
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0 0 10px 0;
                font-size: 28px;
            }
            .content {
                padding: 30px 20px;
            }
            .booking-info {
                background: #f9fafb;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .booking-code {
                text-align: center;
                background: #1f2937;
                color: white;
                padding: 15px;
                border-radius: 8px;
                font-size: 24px;
                font-weight: bold;
                letter-spacing: 2px;
                margin-bottom: 20px;
            }
            .film-title {
                font-size: 20px;
                font-weight: bold;
                text-align: center;
                color: #1f2937;
                margin-bottom: 15px;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #e5e7eb;
            }
            .info-label {
                color: #6b7280;
                font-size: 14px;
            }
            .info-value {
                font-weight: bold;
                color: #1f2937;
            }
            .ticket {
                background: linear-gradient(135deg, #7c3aed 0%, #dc2626 100%);
                border-radius: 12px;
                padding: 3px;
                margin-bottom: 20px;
            }
            .ticket-inner {
                background: white;
                border-radius: 10px;
                padding: 20px;
            }
            .seat-label {
                background: #f59e0b;
                color: #1f2937;
                text-align: center;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 15px;
            }
            .seat-label .label {
                font-size: 12px;
                color: #78350f;
                margin-bottom: 5px;
            }
            .seat-label .seat {
                font-size: 32px;
                font-weight: bold;
            }
            .ticket-code {
                text-align: center;
                font-size: 12px;
                color: #6b7280;
                margin-bottom: 10px;
                font-family: monospace;
            }
            .qr-container {
                text-align: center;
                background: #f9fafb;
                padding: 15px;
                border-radius: 8px;
            }
            .qr-container img {
                width: 200px;
                height: 200px;
                background: white;
                padding: 10px;
                border-radius: 8px;
            }
            .qr-label {
                font-size: 12px;
                color: #6b7280;
                margin-top: 10px;
            }
            .warning {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 15px;
                margin-top: 20px;
                border-radius: 4px;
            }
            .warning strong {
                color: #92400e;
            }
            .footer {
                background: #f9fafb;
                padding: 20px;
                text-align: center;
                color: #6b7280;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . htmlspecialchars($siteName) . '</h1>
                <p style="margin: 0; font-size: 14px;">E-TICKET BIOSKOP</p>
            </div>
            
            <div class="content">
                <div class="booking-code">' . htmlspecialchars($booking['booking_code']) . '</div>
                
                <div class="booking-info">
                    <div class="film-title">' . htmlspecialchars($filmTitle) . '</div>
                    
                    <div class="info-row">
                        <span class="info-label">Tanggal</span>
                        <span class="info-value">' . formatDateIndo($booking['show_date']) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Waktu</span>
                        <span class="info-value">' . formatTime($booking['show_time']) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nama</span>
                        <span class="info-value">' . htmlspecialchars($booking['customer_name']) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Tiket</span>
                        <span class="info-value">' . count($tickets) . ' tiket</span>
                    </div>
                    <div class="info-row" style="border-bottom: none; padding-top: 10px;">
                        <span class="info-label" style="font-size: 16px;">Total Dibayar</span>
                        <span class="info-value" style="color: #059669; font-size: 18px;">' . formatCurrency($booking['total_price']) . '</span>
                    </div>
                </div>
                
                <h2 style="text-align: center; color: #1f2937; margin: 30px 0 20px 0;">
                    Tiket Anda (' . count($tickets) . ' Tiket)
                </h2>
                
                <div class="warning">
                    <strong>PENTING:</strong> Setiap kursi memiliki QR code terpisah. Tunjukkan QR code sesuai kursi Anda saat memasuki bioskop.
                </div>';
    
    // Loop untuk setiap tiket individual
    foreach ($tickets as $ticket) {
        $qrCid = isset($embeddedQRs[$ticket['seat_label']]) ? $embeddedQRs[$ticket['seat_label']] : '';
        
        $html .= '
                <div class="ticket">
                    <div class="ticket-inner">
                        <div class="seat-label">
                            <div class="label">KURSI</div>
                            <div class="seat">' . htmlspecialchars($ticket['seat_label']) . '</div>
                        </div>
                        
                        <div class="ticket-code">
                            Kode: ' . htmlspecialchars($ticket['ticket_code']) . '
                        </div>
                        
                        <div class="qr-container">
                            ' . ($qrCid ? '<img src="cid:' . $qrCid . '" alt="QR Code">' : '<p>QR Code akan tersedia segera</p>') . '
                            <div class="qr-label">Scan QR code ini di pintu masuk</div>
                        </div>
                    </div>
                </div>';
    }
    
    $html .= '
            </div>
            
            <div class="footer">
                <p>Terima kasih telah memesan tiket di ' . htmlspecialchars($siteName) . '</p>
                <p>Harap tiba 15 menit sebelum film dimulai</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>