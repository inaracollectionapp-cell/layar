<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/ticket-functions.php';

$bookingCode = isset($_GET['booking']) ? sanitize($_GET['booking']) : '';
$ticketCode = isset($_GET['ticket']) ? sanitize($_GET['ticket']) : '';

if (empty($bookingCode) && empty($ticketCode)) {
    header('Location: index.php');
    exit();
}

// Jika download individual ticket
if ($ticketCode) {
    $ticket = getTicketByCode($ticketCode);
    if (!$ticket) {
        die('Tiket tidak ditemukan');
    }
    $tickets = [$ticket];
    $singleTicket = true;
} else {
    // Download semua tiket dalam booking
    $booking = getBookingByCode($bookingCode);
    if (!$booking || $booking['payment_status'] !== 'paid') {
        header('Location: index.php');
        exit();
    }
    
    $ticketsResult = getTicketsByBookingCode($bookingCode);
    $tickets = [];
    while ($row = $ticketsResult->fetch_assoc()) {
        $tickets[] = $row;
    }
    $singleTicket = false;
}

if (empty($tickets)) {
    die('Tidak ada tiket yang tersedia');
}

$siteName = getSetting('site_name', 'ISOLA SCREEN');

// Create ZIP untuk multiple tickets
if (count($tickets) > 1 && !$singleTicket) {
    $zipFilename = 'tickets-' . $bookingCode . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipFilename;
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        die('Tidak dapat membuat file ZIP');
    }
    
    foreach ($tickets as $ticket) {
        $image = generateTicketImage($ticket, $siteName);
        if ($image) {
            ob_start();
            imagejpeg($image, null, 95);
            $imageData = ob_get_clean();
            imagedestroy($image);
            
            $zip->addFromString('ticket-' . $ticket['seat_label'] . '.jpg', $imageData);
        }
    }
    
    $zip->close();
    
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    unlink($zipPath);
    exit();
    
} else {
    // Single ticket
    $ticket = $tickets[0];
    $image = generateTicketImage($ticket, $siteName);
    
    if (!$image) {
        die('Gagal membuat tiket');
    }
    
    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="ticket-' . $ticket['seat_label'] . '.jpg"');
    imagejpeg($image, null, 95);
    imagedestroy($image);
    exit();
}

/**
 * Generate gambar tiket individual dengan design keren
 */
function generateTicketImage($ticket, $siteName) {
    $width = 800;
    $height = 1100;
    $image = imagecreatetruecolor($width, $height);
    
    // Warna tema (purple-red gradient)
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $purple = imagecolorallocate($image, 124, 58, 237);
    $red = imagecolorallocate($image, 220, 38, 38);
    $darkGray = imagecolorallocate($image, 31, 41, 55);
    $lightGray = imagecolorallocate($image, 156, 163, 175);
    $gold = imagecolorallocate($image, 245, 158, 11);
    
    // Background putih
    imagefill($image, 0, 0, $white);
    
    // Gradient header (purple to red)
    for ($i = 0; $i < 200; $i++) {
        $ratio = $i / 200;
        $r = intval(124 + ($ratio * (220 - 124)));
        $g = intval(58 - ($ratio * (58 - 38)));
        $b = intval(237 - ($ratio * (237 - 38)));
        $color = imagecolorallocate($image, $r, $g, $b);
        imagefilledrectangle($image, 0, $i, $width, $i + 1, $color);
    }
    
    // Site name dengan font besar
    $y = 40;
    imagestring($image, 5, ($width - strlen($siteName) * 10) / 2, $y, $siteName, $white);
    $y += 35;
    imagestring($image, 3, ($width - 70) / 2, $y, 'E-TICKET', $white);
    $y += 40;
    
    // Decorative line
    imageline($image, 50, $y, $width - 50, $y, $white);
    $y += 30;
    
    // Film title dengan box
    $filmTitle = ($ticket['is_double_feature'] == 1 && !empty($ticket['second_title'])) 
        ? $ticket['title'] . ' & ' . $ticket['second_title']
        : $ticket['title'];
    $filmTitle = substr($filmTitle, 0, 50);
    
    imagefilledrectangle($image, 40, $y, $width - 40, $y + 50, $darkGray);
    imagestring($image, 5, 60, $y + 18, $filmTitle, $white);
    $y += 80;
    
    // Seat number - BIG and prominent
    imagefilledrectangle($image, ($width - 300) / 2, $y, ($width + 300) / 2, $y + 120, $gold);
    
    imagestring($image, 2, ($width - 40) / 2, $y + 15, 'KURSI', $black);
    
    $seatFontSize = 5;
    $seatText = $ticket['seat_label'];
    $seatWidth = strlen($seatText) * 15;
    imagestring($image, $seatFontSize, ($width - $seatWidth) / 2, $y + 50, $seatText, $black);
    $y += 150;
    
    // Ticket code
    imagestring($image, 2, 60, $y, 'KODE TIKET:', $lightGray);
    $y += 20;
    $ticketCodeText = $ticket['ticket_code'];
    imagestring($image, 4, 60, $y, $ticketCodeText, $darkGray);
    $y += 50;
    
    // Info grid
    $leftX = 60;
    $rightX = $width / 2 + 30;
    
    // Row 1: Date & Time
    imagestring($image, 2, $leftX, $y, 'TANGGAL', $lightGray);
    imagestring($image, 2, $rightX, $y, 'WAKTU', $lightGray);
    $y += 20;
    imagestring($image, 4, $leftX, $y, formatDateIndo($ticket['show_date']), $darkGray);
    imagestring($image, 4, $rightX, $y, formatTime($ticket['show_time']), $darkGray);
    $y += 50;
    
    // Row 2: Name
    imagestring($image, 2, $leftX, $y, 'NAMA PEMESAN', $lightGray);
    $y += 20;
    $customerName = substr($ticket['customer_name'], 0, 35);
    imagestring($image, 4, $leftX, $y, $customerName, $darkGray);
    $y += 50;
    
    // Dashed line separator
    for ($x = 50; $x < $width - 50; $x += 10) {
        imageline($image, $x, $y, $x + 5, $y, $lightGray);
    }
    $y += 30;
    
    // QR Code section
    $qrCodePath = getTicketQRCodeUrl($ticket['ticket_code']);
    
    imagestring($image, 3, ($width - 200) / 2, $y, 'SCAN QR CODE DI PINTU MASUK', $darkGray);
    $y += 30;
    
    if (file_exists($qrCodePath)) {
        $qrImage = imagecreatefrompng($qrCodePath);
        if ($qrImage !== false) {
            $qrWidth = imagesx($qrImage);
            $qrHeight = imagesy($qrImage);
            
            $qrSize = 300;
            $qrResized = imagecreatetruecolor($qrSize, $qrSize);
            $qrWhite = imagecolorallocate($qrResized, 255, 255, 255);
            imagefill($qrResized, 0, 0, $qrWhite);
            imagecopyresampled($qrResized, $qrImage, 0, 0, 0, 0, $qrSize, $qrSize, $qrWidth, $qrHeight);
            
            $qrX = ($width - $qrSize) / 2;
            
            // White background untuk QR
            imagefilledrectangle($image, $qrX - 10, $y - 10, $qrX + $qrSize + 10, $y + $qrSize + 10, $darkGray);
            imagefilledrectangle($image, $qrX - 5, $y - 5, $qrX + $qrSize + 5, $y + $qrSize + 5, $white);
            
            imagecopy($image, $qrResized, $qrX, $y, 0, 0, $qrSize, $qrSize);
            
            imagedestroy($qrResized);
            imagedestroy($qrImage);
            
            $y += $qrSize + 30;
        }
    }
    
    // Footer
    imagestring($image, 2, ($width - 250) / 2, $y, 'Simpan tiket ini dengan baik', $lightGray);
    $y += 20;
    imagestring($image, 2, ($width - 220) / 2, $y, 'Tunjukkan saat masuk bioskop', $lightGray);
    
    return $image;
}
?>
