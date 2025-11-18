<?php
require_once '../includes/config.php';
requireAdminLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $filmId = intval($_POST['film_id']);
        $showDate = sanitize($_POST['show_date']);
        $showTime = sanitize($_POST['show_time']);
        $price = floatval($_POST['price']);
        $status = sanitize($_POST['status']);
        
        // Promo fields
        $isPromo = isset($_POST['is_promo']) ? 1 : 0;
        $promoPrice = !empty($_POST['promo_price']) ? floatval($_POST['promo_price']) : null;
        $promoStartDate = !empty($_POST['promo_start_date']) ? sanitize($_POST['promo_start_date']) : null;
        $promoEndDate = !empty($_POST['promo_end_date']) ? sanitize($_POST['promo_end_date']) : null;
        
        $totalSeats = intval(getSetting('total_seats', 50));
        
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO schedules (film_id, show_date, show_time, price, is_promo, promo_price, promo_start_date, promo_end_date, total_seats, available_seats, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdisssiis", $filmId, $showDate, $showTime, $price, $isPromo, $promoPrice, $promoStartDate, $promoEndDate, $totalSeats, $totalSeats, $status);
            
            if ($stmt->execute()) {
                $success = 'Jadwal berhasil ditambahkan';
            } else {
                $error = 'Gagal menambahkan jadwal';
            }
        } else {
            $stmt = $conn->prepare("UPDATE schedules SET film_id = ?, show_date = ?, show_time = ?, price = ?, is_promo = ?, promo_price = ?, promo_start_date = ?, promo_end_date = ?, status = ? WHERE id = ?");
            $stmt->bind_param("issdissssi", $filmId, $showDate, $showTime, $price, $isPromo, $promoPrice, $promoStartDate, $promoEndDate, $status, $id);
            
            if ($stmt->execute()) {
                $success = 'Jadwal berhasil diupdate';
            } else {
                $error = 'Gagal mengupdate jadwal';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = 'Jadwal berhasil dihapus';
        } else {
            $error = 'Gagal menghapus jadwal';
        }
    }
}

$schedules = $conn->query("SELECT s.*, f.title, f.cover_image 
                          FROM schedules s 
                          JOIN films f ON s.film_id = f.id 
                          ORDER BY s.show_date DESC, s.show_time DESC");
$films = getActiveFilms();

$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Tayang - Admin <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Jadwal Tayang</h1>
                    <p class="text-gray-400">Kelola jadwal tayang film</p>
                </div>
                <button onclick="openAddModal()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>Tambah Jadwal
                </button>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-700 text-white px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-700 text-white px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Film</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Waktu</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Harga</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Promo</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Kursi Tersedia</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if ($schedules && $schedules->num_rows > 0): ?>
                                <?php while ($schedule = $schedules->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-700 transition">
                                        <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($schedule['title']); ?></td>
                                        <td class="px-6 py-4"><?php echo date('d/m/Y', strtotime($schedule['show_date'])); ?></td>
                                        <td class="px-6 py-4"><?php echo formatTime($schedule['show_time']); ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="<?php echo $schedule['is_promo'] == 1 ? 'line-through text-gray-500 text-sm' : ''; ?>">
                                                    <?php echo formatCurrency($schedule['price']); ?>
                                                </span>
                                                <?php if ($schedule['is_promo'] == 1 && !empty($schedule['promo_price'])): ?>
                                                    <span class="text-green-400 font-bold">
                                                        <?php echo formatCurrency($schedule['promo_price']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($schedule['is_promo'] == 1): ?>
                                                <span class="bg-green-600 text-white text-xs px-2 py-1 rounded">AKTIF</span>
                                            <?php else: ?>
                                                <span class="bg-gray-600 text-white text-xs px-2 py-1 rounded">NON-AKTIF</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm"><?php echo $schedule['available_seats']; ?> / <?php echo $schedule['total_seats']; ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="<?php echo $schedule['status'] === 'active' ? 'bg-green-600' : 'bg-gray-600'; ?> text-white text-xs px-3 py-1 rounded-full">
                                                <?php echo ucfirst($schedule['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button onclick='editSchedule(<?php echo json_encode($schedule); ?>)' class="text-blue-400 hover:text-blue-300 mr-3" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteSchedule(<?php echo $schedule['id']; ?>)" class="text-red-400 hover:text-red-300" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                                        Belum ada jadwal
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="scheduleModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-700 flex justify-between items-center">
                <h2 id="modalTitle" class="text-2xl font-bold">Tambah Jadwal</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" class="p-6">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="scheduleId">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Film *</label>
                    <select name="film_id" id="film_id" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        <option value="">Pilih Film</option>
                        <?php 
                        $films->data_seek(0);
                        while ($film = $films->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $film['id']; ?>"><?php echo htmlspecialchars($film['title']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Tanggal *</label>
                        <input type="date" name="show_date" id="show_date" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Waktu *</label>
                        <input type="time" name="show_time" id="show_time" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Harga Normal (Rp) *</label>
                    <input type="number" name="price" id="price" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required min="0" step="1000">
                </div>
                
                <!-- Promo Settings -->
                <div class="mb-4 p-4 bg-gray-700 rounded-lg">
                    <div class="flex items-center mb-3">
                        <input type="checkbox" name="is_promo" id="is_promo" class="w-4 h-4 text-red-600 bg-gray-800 border-gray-600 rounded focus:ring-red-500">
                        <label for="is_promo" class="ml-2 text-sm font-semibold">Aktifkan Promo Harga</label>
                    </div>
                    
                    <div id="promoFields" class="hidden space-y-3">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Harga Promo (Rp)</label>
                            <input type="number" name="promo_price" id="promo_price" class="w-full px-3 py-2 bg-gray-600 border border-gray-500 rounded-lg focus:outline-none focus:border-red-500" min="0" step="1000">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold mb-1">Mulai Promo</label>
                                <input type="date" name="promo_start_date" id="promo_start_date" class="w-full px-3 py-2 bg-gray-600 border border-gray-500 rounded-lg focus:outline-none focus:border-red-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold mb-1">Akhir Promo</label>
                                <input type="date" name="promo_end_date" id="promo_end_date" class="w-full px-3 py-2 bg-gray-600 border border-gray-500 rounded-lg focus:outline-none focus:border-red-500">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2">Status *</label>
                    <select name="status" id="status" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script>
        function openAddModal() {
            document.getElementById('scheduleModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Tambah Jadwal';
            document.getElementById('formAction').value = 'add';
            document.querySelector('form').reset();
            document.getElementById('scheduleId').value = '';
            document.getElementById('promoFields').classList.add('hidden');
            document.getElementById('is_promo').checked = false;
        }

        function editSchedule(schedule) {
            document.getElementById('scheduleModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Edit Jadwal';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('scheduleId').value = schedule.id;
            document.getElementById('film_id').value = schedule.film_id;
            document.getElementById('show_date').value = schedule.show_date;
            document.getElementById('show_time').value = schedule.show_time;
            document.getElementById('price').value = schedule.price;
            document.getElementById('status').value = schedule.status;
            
            // Promo fields
            document.getElementById('is_promo').checked = schedule.is_promo == 1;
            document.getElementById('promo_price').value = schedule.promo_price || '';
            document.getElementById('promo_start_date').value = schedule.promo_start_date || '';
            document.getElementById('promo_end_date').value = schedule.promo_end_date || '';
            
            if (schedule.is_promo == 1) {
                document.getElementById('promoFields').classList.remove('hidden');
            } else {
                document.getElementById('promoFields').classList.add('hidden');
            }
        }

        function closeModal() {
            document.getElementById('scheduleModal').classList.add('hidden');
        }

        function deleteSchedule(id) {
            if (confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Toggle promo fields
        document.getElementById('is_promo').addEventListener('change', function() {
            const promoFields = document.getElementById('promoFields');
            if (this.checked) {
                promoFields.classList.remove('hidden');
            } else {
                promoFields.classList.add('hidden');
            }
        });
    </script>
</body>
</html>