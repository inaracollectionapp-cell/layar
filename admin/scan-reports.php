<?php
require_once '../includes/config.php';
requireAdminLogin();

// Get filter parameters
$filterDate = isset($_GET['date']) ? $_GET['date'] : '';
$filterSchedule = isset($_GET['schedule']) ? intval($_GET['schedule']) : 0;

// Build query
$whereConditions = ["b.booking_status = 'used'"];
$params = [];
$types = '';

if (!empty($filterDate)) {
    $whereConditions[] = "DATE(b.used_at) = ?";
    $params[] = $filterDate;
    $types .= 's';
}

if ($filterSchedule > 0) {
    $whereConditions[] = "b.schedule_id = ?";
    $params[] = $filterSchedule;
    $types .= 'i';
}

$whereClause = implode(' AND ', $whereConditions);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total_scanned,
    COUNT(DISTINCT b.schedule_id) as total_shows,
    SUM(b.total_seats) as total_seats_scanned,
    DATE(b.used_at) as scan_date
FROM bookings b
WHERE $whereClause";

if (!empty($params)) {
    $stmt = $conn->prepare($statsQuery);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $statsResult = $stmt->get_result();
} else {
    $statsResult = $conn->query($statsQuery);
}

$stats = $statsResult->fetch_assoc();

// Get scanned tickets list
$scannedQuery = "SELECT b.*, f.title, s.show_date, s.show_time,
    f.is_double_feature, f2.title as second_title
FROM bookings b
JOIN schedules s ON b.schedule_id = s.id
JOIN films f ON s.film_id = f.id
LEFT JOIN films f2 ON f.second_film_id = f2.id
WHERE $whereClause
ORDER BY b.used_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($scannedQuery);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $scannedTickets = $stmt->get_result();
} else {
    $scannedTickets = $conn->query($scannedQuery);
}

// Get all schedules for filter
$schedulesFilter = $conn->query("SELECT s.id, f.title, s.show_date, s.show_time 
    FROM schedules s 
    JOIN films f ON s.film_id = f.id 
    ORDER BY s.show_date DESC, s.show_time DESC 
    LIMIT 50");

$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Scan Tiket - Admin <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Laporan Scan Tiket</h1>
                <p class="text-gray-400">Monitoring tiket yang sudah di-scan</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Total Tiket Di-scan</h3>
                        <i class="fas fa-qrcode text-3xl opacity-50"></i>
                    </div>
                    <p class="text-4xl font-bold"><?php echo $stats['total_scanned'] ?? 0; ?></p>
                    <p class="text-sm text-green-100 mt-2">Booking yang sudah masuk</p>
                </div>

                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Total Kursi</h3>
                        <i class="fas fa-couch text-3xl opacity-50"></i>
                    </div>
                    <p class="text-4xl font-bold"><?php echo $stats['total_seats_scanned'] ?? 0; ?></p>
                    <p class="text-sm text-blue-100 mt-2">Kursi terisi</p>
                </div>

                <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Total Pertunjukan</h3>
                        <i class="fas fa-film text-3xl opacity-50"></i>
                    </div>
                    <p class="text-4xl font-bold"><?php echo $stats['total_shows'] ?? 0; ?></p>
                    <p class="text-sm text-purple-100 mt-2">Jadwal yang sudah tayang</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-800 rounded-xl p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Filter Data</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Tanggal Scan</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filterDate); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Jadwal</label>
                        <select name="schedule" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                            <option value="0">Semua Jadwal</option>
                            <?php while ($sched = $schedulesFilter->fetch_assoc()): ?>
                                <option value="<?php echo $sched['id']; ?>" <?php echo $filterSchedule == $sched['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sched['title']) . ' - ' . date('d/m/Y', strtotime($sched['show_date'])) . ' ' . formatTime($sched['show_time']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <a href="scan-reports.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Scanned Tickets Table -->
            <div class="bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-700">
                    <h2 class="text-xl font-bold">Daftar Tiket yang Di-scan</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Kode Booking</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Film</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Jadwal</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Kursi</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Waktu Scan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if ($scannedTickets && $scannedTickets->num_rows > 0): ?>
                                <?php while ($ticket = $scannedTickets->fetch_assoc()): ?>
                                    <?php 
                                    $seats = json_decode($ticket['seats'], true);
                                    $filmTitle = $ticket['title'];
                                    if ($ticket['is_double_feature'] == 1 && !empty($ticket['second_title'])) {
                                        $filmTitle .= ' & ' . $ticket['second_title'];
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-700 transition">
                                        <td class="px-6 py-4">
                                            <span class="font-mono text-sm bg-gray-700 px-3 py-1 rounded"><?php echo htmlspecialchars($ticket['booking_code']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($filmTitle); ?></td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                <div><?php echo date('d/m/Y', strtotime($ticket['show_date'])); ?></div>
                                                <div class="text-gray-400"><?php echo formatTime($ticket['show_time']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($ticket['customer_name']); ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <?php foreach ($seats as $seat): ?>
                                                    <span class="bg-green-600 text-white text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($seat); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if (!empty($ticket['used_at'])): ?>
                                                <div class="text-sm">
                                                    <div class="text-green-400">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        <?php echo date('d/m/Y', strtotime($ticket['used_at'])); ?>
                                                    </div>
                                                    <div class="text-gray-400"><?php echo date('H:i', strtotime($ticket['used_at'])); ?></div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-500 text-sm">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                        <i class="fas fa-inbox text-4xl mb-2"></i>
                                        <p>Belum ada tiket yang di-scan</p>
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
