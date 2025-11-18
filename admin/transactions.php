<?php
require_once '../includes/config.php';
requireAdminLogin();

$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Query yang diperbaiki - tanpa theater_name
$sql = "SELECT t.*, b.booking_code, b.customer_name, b.customer_email, b.customer_phone, 
               f.title, s.show_date, s.show_time
        FROM transactions t
        JOIN bookings b ON t.booking_id = b.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN films f ON s.film_id = f.id";

if ($filterStatus) {
    $sql .= " WHERE t.status = '" . $conn->real_escape_string($filterStatus) . "'";
}

$sql .= " ORDER BY t.created_at DESC";

$transactions = $conn->query($sql);
$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Admin <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Transaksi</h1>
                <p class="text-gray-400">Laporan transaksi pembayaran</p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-semibold mb-2">Filter Status</label>
                        <select name="status" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $filterStatus === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="success" <?php echo $filterStatus === 'success' ? 'selected' : ''; ?>>Success</option>
                            <option value="failed" <?php echo $filterStatus === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="expired" <?php echo $filterStatus === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="refunded" <?php echo $filterStatus === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    
                    <?php if ($filterStatus): ?>
                        <a href="transactions.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition">
                            <i class="fas fa-times mr-2"></i>Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Order ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Kode Booking</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Pemesan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Film & Jadwal</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Jumlah</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Metode Bayar</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Waktu</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if ($transactions && $transactions->num_rows > 0): ?>
                                <?php while ($transaction = $transactions->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-700 transition">
                                        <td class="px-6 py-4 font-mono text-sm">
                                            <?php echo htmlspecialchars($transaction['order_id'] ?? $transaction['id']); ?>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-sm text-red-400">
                                            <?php echo htmlspecialchars($transaction['booking_code']); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold"><?php echo htmlspecialchars($transaction['customer_name']); ?></div>
                                            <div class="text-sm text-gray-400"><?php echo htmlspecialchars($transaction['customer_email']); ?></div>
                                            <div class="text-sm text-gray-400"><?php echo htmlspecialchars($transaction['customer_phone'] ?? '-'); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold"><?php echo htmlspecialchars($transaction['title']); ?></div>
                                            <div class="text-sm text-gray-400">
                                                <?php echo date('d/m/Y', strtotime($transaction['show_date'])); ?> 
                                                <?php echo date('H:i', strtotime($transaction['show_time'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-semibold">
                                            <?php echo formatCurrency($transaction['amount'] ?? $transaction['total_amount'] ?? 0); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php 
                                            // Deteksi metode pembayaran berdasarkan order_id
                                            $orderId = $transaction['order_id'] ?? '';
                                            $paymentMethod = '-';
                                            
                                            if (stripos($orderId, 'ORDER') === 0) {
                                                $paymentMethod = 'Tripay';
                                            } elseif (stripos($orderId, 'OFFLINE') === 0) {
                                                $paymentMethod = 'Offline';
                                            } else {
                                                // Fallback ke data existing jika ada
                                                $paymentMethod = $transaction['payment_method'] ?? $transaction['payment_type'] ?? '-';
                                                $paymentMethod = ucfirst(str_replace('_', ' ', $paymentMethod));
                                            }
                                            
                                            echo htmlspecialchars($paymentMethod); 
                                            ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $status = $transaction['status'] ?? $transaction['transaction_status'] ?? 'pending';
                                            $statusClass = 'bg-gray-600';
                                            
                                            if ($status === 'paid' || $status === 'success' || $status === 'settlement' || $status === 'capture') {
                                                $statusClass = 'bg-green-600';
                                            } elseif ($status === 'pending') {
                                                $statusClass = 'bg-yellow-600';
                                            } elseif ($status === 'failed' || $status === 'deny' || $status === 'cancel' || $status === 'cancelled' || $status === 'expire' || $status === 'expired') {
                                                $statusClass = 'bg-red-600';
                                            } elseif ($status === 'refunded') {
                                                $statusClass = 'bg-blue-600';
                                            }
                                            ?>
                                            <span class="<?php echo $statusClass; ?> text-white text-xs px-3 py-1 rounded-full">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <a href="transaction_detail.php?id=<?php echo $transaction['id']; ?>" 
                                                   class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-lg transition"
                                                   title="Detail Transaksi">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (($status === 'pending' || $status === 'paid') && !in_array($status, ['cancelled', 'refunded'])): ?>
                                                    <button onclick="updateTransactionStatus(<?php echo $transaction['id']; ?>, 'cancelled')" 
                                                            class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-lg transition"
                                                            title="Batalkan Transaksi">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-8 text-center text-gray-400">
                                        <i class="fas fa-receipt text-4xl mb-2 block"></i>
                                        Belum ada transaksi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    function updateTransactionStatus(transactionId, status) {
        if (confirm('Apakah Anda yakin ingin membatalkan transaksi ini?')) {
            fetch('update_transaction_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${transactionId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status transaksi berhasil diupdate');
                    location.reload();
                } else {
                    alert('Gagal mengupdate status transaksi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengupdate status transaksi');
            });
        }
    }
    </script>

</body>
</html>