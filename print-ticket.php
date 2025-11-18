<?php
require_once 'includes/config.php';
require_once 'includes/ticket-functions.php';
require_once 'vendor/autoload.php';

$bookingCode = isset($_GET['booking']) ? sanitize($_GET['booking']) : '';
$ticketCode = isset($_GET['ticket']) ? sanitize($_GET['ticket']) : '';

if (empty($bookingCode) && empty($ticketCode)) {
    header('Location: index.php');
    exit();
}

// Jika print individual ticket
if ($ticketCode) {
    $ticket = getTicketByCode($ticketCode);
    if (!$ticket) {
        die('Tiket tidak ditemukan');
    }
    $tickets = [$ticket];
} else {
    // Print semua tiket dalam booking
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
}

if (empty($tickets)) {
    die('Tidak ada tiket yang tersedia');
}

$siteName = getSetting('site_name', 'ISOLA SCREEN');

// Create PDF
$pdf = new FPDF('P', 'mm', 'A4');

foreach ($tickets as $ticket) {
    $pdf->AddPage();
    
    // Header gradient (purple to red simulation)
    $pdf->SetFillColor(124, 58, 237);
    $pdf->Rect(0, 0, 210, 40, 'F');
    
    $pdf->SetFillColor(220, 38, 38);
    $pdf->Rect(0, 20, 210, 20, 'F');
    
    // Site name
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 28);
    $pdf->SetXY(0, 12);
    $pdf->Cell(210, 10, mb_convert_encoding($siteName, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(0, 28);
    $pdf->Cell(210, 10, 'E-TICKET BIOSKOP', 0, 1, 'C');
    
    // Film title box
    $pdf->SetFillColor(31, 41, 55);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY(20, 50);
    $pdf->Cell(170, 12, '', 1, 0, 'C', true);
    
    $filmTitle = ($ticket['is_double_feature'] == 1 && !empty($ticket['second_title'])) 
        ? $ticket['title'] . ' & ' . $ticket['second_title']
        : $ticket['title'];
    
    $filmTitle = mb_convert_encoding($filmTitle, 'ISO-8859-1', 'UTF-8');
    
    $pdf->SetXY(20, 51);
    $pdf->Cell(170, 10, substr($filmTitle, 0, 50), 0, 1, 'C');
    
    // Seat number - BIG
    $pdf->SetFillColor(245, 158, 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(55, 70);
    $pdf->Cell(100, 35, '', 1, 0, 'C', true);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY(55, 73);
    $pdf->Cell(100, 5, 'KURSI', 0, 1, 'C');
    
    $pdf->SetFont('Arial', 'B', 32);
    $pdf->SetXY(55, 82);
    $pdf->Cell(100, 12, $ticket['seat_label'], 0, 1, 'C');
    
    // Ticket code
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(20, 115);
    $pdf->Cell(170, 5, 'KODE TIKET', 0, 1);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetXY(20, 120);
    $pdf->Cell(170, 5, $ticket['ticket_code'], 0, 1);
    
    // Info grid
    $pdf->SetY(135);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    
    // Left column
    $pdf->SetX(25);
    $pdf->Cell(80, 5, 'TANGGAL', 0, 0);
    $pdf->Cell(80, 5, 'WAKTU', 0, 1);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetX(25);
    
    $showDate = formatDateIndo($ticket['show_date']);
    $showTime = formatTime($ticket['show_time']);
    
    $showDate = mb_convert_encoding($showDate, 'ISO-8859-1', 'UTF-8');
    $showTime = mb_convert_encoding($showTime, 'ISO-8859-1', 'UTF-8');
    
    $pdf->Cell(80, 8, $showDate, 0, 0);
    $pdf->Cell(80, 8, $showTime, 0, 1);
    
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetX(25);
    $pdf->Cell(160, 5, 'NAMA PEMESAN', 0, 1);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetX(25);
    
    $customerName = substr($ticket['customer_name'], 0, 40);
    $customerName = mb_convert_encoding($customerName, 'ISO-8859-1', 'UTF-8');
    
    $pdf->Cell(160, 8, $customerName, 0, 1);
    
    // Dashed line
    $pdf->SetLineWidth(0.5);
    $pdf->SetDrawColor(200, 200, 200);
    for ($x = 25; $x < 185; $x += 5) {
        $pdf->Line($x, 175, $x + 2, 175);
    }
    $pdf->SetLineWidth(0.2);
    
    // QR Code section
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0, 0, 0);
    
    $qrInstruction = 'SCAN QR CODE INI DI PINTU MASUK';
    $qrInstruction = mb_convert_encoding($qrInstruction, 'ISO-8859-1', 'UTF-8');
    
    $pdf->Cell(0, 5, $qrInstruction, 0, 1, 'C');
    
    // Add QR code image
    $qrCodePath = getTicketQRCodeUrl($ticket['ticket_code']);
    if (file_exists($qrCodePath)) {
        $pdf->Image($qrCodePath, 55, $pdf->GetY() + 5, 100, 100);
    }
    
    $pdf->Ln(105);
    
    // Footer
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(100, 100, 100);
    
    $footer1 = 'Simpan tiket ini dengan baik dan tunjukkan saat masuk bioskop';
    $footer2 = 'Terima kasih telah memesan di ' . $siteName;
    
    $footer1 = mb_convert_encoding($footer1, 'ISO-8859-1', 'UTF-8');
    $footer2 = mb_convert_encoding($footer2, 'ISO-8859-1', 'UTF-8');
    
    $pdf->Cell(0, 5, $footer1, 0, 1, 'C');
    $pdf->Cell(0, 5, $footer2, 0, 1, 'C');
}

// Output PDF
$filename = $ticketCode ? 'ticket-' . $ticket['seat_label'] . '.pdf' : 'tickets-' . $bookingCode . '.pdf';
$pdf->Output('D', $filename);
?>
