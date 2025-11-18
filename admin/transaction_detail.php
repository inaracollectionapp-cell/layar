<?php
require_once '../includes/config.php';
requireAdminLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    header('Location: transactions.php');
    exit();
}

// Get transaction details
$stmt = $conn->prepare("
    SELECT t.*, b.booking_code, b.customer_name, b.customer_email, b.customer_phone, 
           b.seats, b.total_seats, b.total_price, b.booking_status, b.payment_status,
           f.title, f.genre, f.rating, f.duration,
           s.show_date, s.show_time
    FROM transactions t
    JOIN bookings b ON t.booking_id = b.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN films f ON s.film_id = f.id
    WHERE t.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();

if (!$transaction) {
    header('Location: transactions.php');
    exit();
}

$seats = json_decode($transaction['seats'], true);
$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi - Admin <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8">
                <a href="transactions.php" class="text-gray-400 hover:text-white mb-4 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar Transaksi
                </a>
                <h1 class="text-3xl font-bold mb-2">Detail Transaksi</h1>
                <p class="text-gray-400">Informasi lengkap transaksi pembayaran</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Transaction Info -->
                <div class="bg-gray-800 rounded-xl p-6">
                    <h2 class="text-xl font-bold mb-4 border-b border-gray-700 pb-3">
                        <i class="fas fa-file-invoice text-red-500 mr-2"></i>Informasi Transaksi
                    </h2>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Order ID:</span>
                            <span class="font-mono"><?php echo htmlspecialchars($transaction['order_id'] ?? $transaction['id']); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Reference:</span>
                            <span class="font-mono text-sm"><?php echo htmlspecialchars($transaction['reference'] ?? '-'); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Merchant Ref:</span>
                            <span class="font-mono text-sm"><?php echo htmlspecialchars($transaction['merchant_ref'] ?? '-'); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Metode Pembayaran:</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($transaction['payment_method'] ?? $transaction['payment_type'] ?? '-'); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Jumlah:</span>
                            <span class="font-bold text-lg"><?php echo formatCurrency($transaction['amount'] ?? $transaction['total_amount'] ?? 0); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Status:</span>
                            <?php
                            $status = $transaction['status'] ?? 'pending';
                            $statusClass = 'bg-gray-600';
                            
                            if (in_array($status, ['paid', 'success', 'settlement', 'capture'])) {
                                $statusClass = 'bg-green-600';
                            } elseif ($status === 'pending') {
                                $statusClass = 'bg-yellow-600';
                            } elseif (in_array($status, ['failed', 'deny', 'cancel', 'cancelled', 'expire', 'expired'])) {
                                $statusClass = 'bg-red-600';
                            }
                            ?>
                            <span class="<?php echo $statusClass; ?> text-white text-sm px-3 py-1 rounded-full">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Waktu Dibuat:</span>
                            <span><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></span>
                        </div>
                        <?php if (!empty($transaction['paid_at']) && $transaction['paid_at'] != '0000-00-00 00:00:00'): ?>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Waktu Dibayar:</span>
                            <span><?php echo date('d/m/Y H:i', strtotime($transaction['paid_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Customer & Booking Info -->
                <div class="bg-gray-800 rounded-xl p-6">
                    <h2 class="text-xl font-bold mb-4 border-b border-gray-700 pb-3">
                        <i class="fas fa-user text-blue-500 mr-2"></i>Informasi Pemesan
                    </h2>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Kode Booking:</span>
                            <span class="font-mono text-red-400"><?php echo htmlspecialchars($transaction['booking_code']); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Nama:</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($transaction['customer_name']); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Email:</span>
                            <span><?php echo htmlspecialchars($transaction['customer_email']); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Telepon:</span>
                            <span><?php echo htmlspecialchars($transaction['customer_phone'] ?? '-'); ?></span>
                        </div>
                    </div>

                    <h3 class="font-bold mb-3 border-b border-gray-700 pb-2">
                        <i class="fas fa-film text-purple-500 mr-2"></i>Detail Film
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Judul:</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($transaction['title']); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Tanggal:</span>
                            <span><?php echo formatDateIndo($transaction['show_date']); ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <span class="text-gray-400">Waktu:</span>
                            <span><?php echo formatTime($transaction['show_time']); ?></span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-400">Kursi:</span>
                            <span class="font-semibold"><?php echo implode(', ', $seats); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex gap-3">
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition">
                    <i class="fas fa-print mr-2"></i>Cetak
                </button>
                
                <?php if (!in_array($status, ['cancelled', 'refunded'])): ?>
                <button onclick="cancelTransaction()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Batalkan Transaksi
                </button>
                <?php endif; ?>
                
                <a href="transactions.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
        </main>
    </div>

    <script>
    function cancelTransaction() {
        if (confirm('Apakah Anda yakin ingin membatalkan transaksi ini?')) {
            fetch('update_transaction_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=<?php echo $id; ?>&status=cancelled`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaksi berhasil dibatalkan');
                    location.reload();
                } else {
                    alert('Gagal membatalkan transaksi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            });
        }
    }
    </script>

</body>
</html>
