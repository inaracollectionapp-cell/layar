<?php
require_once '../includes/config.php';
requireAdminLogin();

$totalFilms = $conn->query("SELECT COUNT(*) as count FROM films WHERE status = 'active'")->fetch_assoc()['count'];
$totalBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'")->fetch_assoc()['count'];

// Query revenue yang lebih aman
$revenueResult = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings WHERE payment_status = 'paid'");
$revenueData = $revenueResult->fetch_assoc();
$totalRevenue = floatval($revenueData['total']);

$pendingPayments = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'unpaid' AND booking_status = 'pending'")->fetch_assoc()['count'];

$recentBookings = $conn->query("SELECT b.*, f.title, s.show_date, s.show_time 
                                FROM bookings b 
                                JOIN schedules s ON b.schedule_id = s.id 
                                JOIN films f ON s.film_id = f.id 
                                ORDER BY b.booking_date DESC LIMIT 10");

$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Dashboard</h1>
                <p class="text-gray-400">Selamat datang kembali, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-red-600 to-red-700 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Total Film</h3>
                        <i class="fas fa-film text-3xl opacity-50"></i>
                    </div>
                    <p class="text-4xl font-bold"><?php echo $totalFilms; ?></p>
                    <p class="text-sm text-red-100 mt-2">Film aktif</p>
                </div>
                
                <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Total Booking</h3>
                        <i class="fas fa-ticket-alt text-3xl opacity-50"></i>
                    </div>
                    <p class="text-4xl font-bold"><?php echo $totalBookings; ?></p>
                    <p class="text-sm text-green-100 mt-2">Booking terkonfirmasi</p>
                </div>
                
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Total Pendapatan</h3>
                        <i class="fas fa-money-bill-wave text-3xl opacity-50"></i>
                    </div>
                    <p class="text-3xl font-bold">
                        <?php 
                        // Format currency manual untuk sementara
                        if ($totalRevenue == 0) {
                            echo 'Rp 0';
                        } else {
                            echo 'Rp ' . number_format($totalRevenue, 0, ',', '.');
                        }
                        ?>
                    </p>
                    <p class="text-sm text-blue-100 mt-2">Revenue keseluruhan</p>
                </div>
                
                <div class="bg-gradient-to-br from-yellow-600 to-yellow-700 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Pending</h3>
                        <i class="fas fa-clock text-3xl opacity-50"></i>
                    </div>
                    <p class="text-4xl font-bold"><?php echo $pendingPayments; ?></p>
                    <p class="text-sm text-yellow-100 mt-2">Menunggu pembayaran</p>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl shadow-lg">
                <div class="p-6 border-b border-gray-700">
                    <h2 class="text-xl font-bold">Booking Terbaru</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Kode</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Film</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Pemesan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if ($recentBookings && $recentBookings->num_rows > 0): ?>
                                <?php while ($booking = $recentBookings->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-700 transition">
                                        <td class="px-6 py-4 font-mono text-sm text-red-400"><?php echo $booking['booking_code']; ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($booking['title']); ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo date('d/m/Y H:i', strtotime($booking['show_date'] . ' ' . $booking['show_time'])); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                        <td class="px-6 py-4 font-semibold">
                                            <?php echo 'Rp ' . number_format($booking['total_price'], 0, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $statusClass = $booking['payment_status'] === 'paid' ? 'bg-green-600' : ($booking['payment_status'] === 'unpaid' ? 'bg-yellow-600' : 'bg-red-600');
                                            $statusText = ucfirst($booking['payment_status']);
                                            ?>
                                            <span class="<?php echo $statusClass; ?> text-white text-xs px-3 py-1 rounded-full">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                        Belum ada booking
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</body>
</html>