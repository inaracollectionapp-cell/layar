<?php
require_once 'includes/config.php';
require_once 'includes/ticket-functions.php';
require_once 'includes/email-functions.php';

$bookingCode = isset($_GET['booking']) ? sanitize($_GET['booking']) : '';

if (empty($bookingCode)) {
    header('Location: index.php');
    exit();
}

$booking = getBookingByCode($bookingCode);

if (!$booking) {
    header('Location: index.php');
    exit();
}

// Send email ticket if not sent yet
if ($booking['payment_status'] === 'paid' && (!isset($booking['email_sent']) || $booking['email_sent'] == 0)) {
    sendTicketEmail($bookingCode);
}

// Get semua tiket individual
$ticketsResult = getTicketsByBookingCode($bookingCode);
$tickets = [];
while ($row = $ticketsResult->fetch_assoc()) {
    $tickets[] = $row;
}

$seats = json_decode($booking['seats'], true);
$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Berhasil - <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            
            <!-- Success Icon -->
            <div class="text-center mb-8">
                <div class="inline-block bg-green-600 rounded-full p-6 mb-4 animate-bounce">
                    <i class="fas fa-check text-6xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">Pembayaran Berhasil!</h1>
                <p class="text-gray-400">Terima kasih telah memesan tiket di <?php echo $siteName; ?></p>
                <?php if ($booking['payment_status'] === 'paid'): ?>
                <p class="text-sm text-green-400 mt-2">
                    <i class="fas fa-envelope mr-2"></i>E-ticket telah dikirim ke email Anda
                </p>
                <?php endif; ?>
            </div>

            <!-- Info Booking -->
            <div class="bg-gray-800 rounded-xl p-6 mb-6">
                <div class="text-center mb-4">
                    <p class="text-sm text-gray-400 mb-1">Kode Booking</p>
                    <p class="text-2xl font-bold text-red-400 tracking-wider"><?php echo $booking['booking_code']; ?></p>
                </div>
                
                <div class="border-t border-gray-700 pt-4">
                    <h3 class="font-bold text-xl mb-3 text-center">
                        <?php 
                        if ($booking['is_double_feature'] == 1 && !empty($booking['second_title'])) {
                            echo htmlspecialchars($booking['title']) . ' & ' . htmlspecialchars($booking['second_title']);
                        } else {
                            echo htmlspecialchars($booking['title']);
                        }
                        ?>
                    </h3>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-400 mb-1"><i class="fas fa-calendar w-5"></i>Tanggal</p>
                            <p class="font-semibold"><?php echo formatDateIndo($booking['show_date']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400 mb-1"><i class="fas fa-clock w-5"></i>Waktu</p>
                            <p class="font-semibold"><?php echo formatTime($booking['show_time']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <p class="text-gray-400 text-sm mb-1"><i class="fas fa-user w-5"></i>Nama Pemesan</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                    </div>
                    
                    <div class="mt-3">
                        <p class="text-gray-400 text-sm mb-1"><i class="fas fa-ticket-alt w-5"></i>Total Tiket</p>
                        <p class="font-semibold"><?php echo count($tickets); ?> tiket untuk kursi: <?php echo implode(', ', $seats); ?></p>
                    </div>
                    
                    <div class="border-t border-gray-700 pt-4 mt-4 flex justify-between items-center">
                        <span class="text-lg font-semibold">Total Dibayar</span>
                        <span class="text-2xl font-bold text-green-400"><?php echo formatCurrency($booking['total_price']); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($booking['payment_status'] === 'paid' && !empty($tickets)): ?>
            <!-- Individual Tickets -->
            <h2 class="text-2xl font-bold mb-4 text-center">
                <i class="fas fa-ticket-alt mr-2"></i>Tiket Anda (<?php echo count($tickets); ?> Tiket)
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <?php foreach ($tickets as $ticket): 
                    $qrCodeUrl = getTicketQRCodeUrl($ticket['ticket_code']);
                ?>
                <div class="bg-gradient-to-br from-purple-600 to-red-600 rounded-xl p-1">
                    <div class="bg-gray-800 rounded-lg p-4">
                        <div class="text-center mb-3">
                            <div class="bg-gray-700 inline-block px-4 py-2 rounded-lg mb-2">
                                <p class="text-xs text-gray-400">Kursi</p>
                                <p class="text-2xl font-bold text-red-400"><?php echo $ticket['seat_label']; ?></p>
                            </div>
                            <p class="text-xs text-gray-400">Kode Tiket</p>
                            <p class="text-sm font-mono font-semibold"><?php echo $ticket['ticket_code']; ?></p>
                        </div>
                        
                        <div class="bg-white p-3 rounded-lg">
                            <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="w-full h-auto">
                        </div>
                        
                        <p class="text-xs text-center text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>Tunjukkan QR ini di pintu masuk
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                <a href="download-ticket.php?booking=<?php echo $bookingCode; ?>" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition text-center">
                    <i class="fas fa-download mr-2"></i>Download Semua Tiket
                </a>
                
                <a href="print-ticket.php?booking=<?php echo $bookingCode; ?>" 
                   target="_blank"
                   class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg transition text-center">
                    <i class="fas fa-print mr-2"></i>Cetak PDF
                </a>
                
                <a href="index.php" 
                   class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 rounded-lg transition text-center">
                    <i class="fas fa-home mr-2"></i>Beranda
                </a>
            </div>
            
            <div class="bg-yellow-600 bg-opacity-20 border border-yellow-600 rounded-lg p-4 text-center">
                <p class="text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <strong>Penting:</strong> Setiap kursi memiliki QR code terpisah. Pastikan Anda membawa semua tiket saat datang ke bioskop.
                </p>
            </div>
        </div>
    </div>

</body>
</html>
