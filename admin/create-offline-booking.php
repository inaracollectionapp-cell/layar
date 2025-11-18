<?php
require_once '../includes/config.php';
requireAdminLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduleId = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    $selectedSeatsJson = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : '';
    $customerName = isset($_POST['customer_name']) ? sanitize($_POST['customer_name']) : '';
    $customerEmail = isset($_POST['customer_email']) ? sanitize($_POST['customer_email']) : '';
    $customerPhone = isset($_POST['customer_phone']) ? sanitize($_POST['customer_phone']) : '';
    
    if (!$scheduleId || empty($selectedSeatsJson) || empty($customerName)) {
        $error = 'Data tidak lengkap';
    } else {
        $selectedSeats = json_decode($selectedSeatsJson, true);
        
        if (!$selectedSeats || count($selectedSeats) === 0) {
            $error = 'Silakan pilih kursi terlebih dahulu';
        } else {
            $schedule = getScheduleById($scheduleId);
            
            if (!$schedule) {
                $error = 'Jadwal tidak ditemukan';
            } else {
                $conn->begin_transaction();
                
                try {
                    // Check seat availability
                    $placeholders = implode(',', array_fill(0, count($selectedSeats), '?'));
                    $stmt = $conn->prepare("SELECT seat_label, status FROM seats WHERE schedule_id = ? AND seat_label IN ($placeholders) FOR UPDATE");
                    $types = 'i' . str_repeat('s', count($selectedSeats));
                    $params = array_merge([$scheduleId], $selectedSeats);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    $unavailableSeats = [];
                    while ($row = $result->fetch_assoc()) {
                        if ($row['status'] !== 'available') {
                            $unavailableSeats[] = $row['seat_label'];
                        }
                    }
                    
                    if (!empty($unavailableSeats)) {
                        throw new Exception('Kursi ' . implode(', ', $unavailableSeats) . ' sudah tidak tersedia');
                    }
                    
                    // Check promo price
                    $today = date('Y-m-d');
                    $promoStart = $schedule['promo_start_date'];
                    $promoEnd = $schedule['promo_end_date'];
                    $isPromoActive = $schedule['is_promo'] == 1 && 
                                    (!$promoStart || $today >= $promoStart) && 
                                    (!$promoEnd || $today <= $promoEnd);
                    
                    $pricePerSeat = $isPromoActive && !empty($schedule['promo_price']) ? $schedule['promo_price'] : $schedule['price'];
                    
                    $totalSeats = count($selectedSeats);
                    $totalPrice = $totalSeats * $pricePerSeat;
                    $bookingCode = generateBookingCode();
                    
                    // Create offline booking (paid and confirmed)
                    $stmt = $conn->prepare("INSERT INTO bookings (booking_code, schedule_id, customer_name, customer_email, customer_phone, seats, total_seats, total_price, booking_status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'paid')");
                    $seatsJson = json_encode($selectedSeats);
                    $stmt->bind_param("sissssis", $bookingCode, $scheduleId, $customerName, $customerEmail, $customerPhone, $seatsJson, $totalSeats, $totalPrice);
                    $stmt->execute();
                    
                    $bookingId = $conn->insert_id;
                    
                    // Update seats to booked
                    updateSeatStatus($scheduleId, $selectedSeats, 'booked');
                    updateAvailableSeats($scheduleId);
                    
                    // Create transaction record (offline payment)
                    $stmt = $conn->prepare("INSERT INTO transactions (booking_id, order_id, reference, merchant_ref, amount, status, paid_at) VALUES (?, ?, ?, ?, ?, 'PAID', NOW())");
                    $orderId = 'OFFLINE-' . $bookingCode;
                    $reference = 'OFFLINE-' . time();
                    $merchantRef = 'OFFLINE-' . $bookingCode;
                    $stmt->bind_param("isssi", $bookingId, $orderId, $reference, $merchantRef, $totalPrice);
                    $stmt->execute();
                    
                    // Send email if email provided
                    if (!empty($customerEmail) && validateEmail($customerEmail)) {
                        require_once '../includes/email-functions.php';
                        sendTicketEmail($bookingCode);
                    }
                    
                    $conn->commit();
                    
                    $success = 'Booking offline berhasil dibuat dengan kode: ' . $bookingCode;
                    
                    // Reset form
                    $_POST = [];
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
                }
            }
        }
    }
}

// Get active films and schedules
$films = getActiveFilms();
$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Offline - Admin <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .seat {
            width: 40px;
            height: 40px;
            margin: 4px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        .seat.available {
            background-color: #4b5563;
            border: 2px solid #6b7280;
        }
        .seat.available:hover {
            background-color: #059669;
            transform: scale(1.1);
        }
        .seat.selected {
            background-color: #dc2626;
            border: 2px solid #ef4444;
            transform: scale(1.1);
        }
        .seat.booked, .seat.reserved {
            background-color: #1f2937;
            border: 2px solid #374151;
            cursor: not-allowed;
            opacity: 0.5;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Booking Offline</h1>
                <p class="text-gray-400">Buat booking tanpa pembayaran online untuk tiket offline</p>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-700 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-bold">Berhasil!</h3>
                            <p><?php echo $success; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-700 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-bold">Error!</h3>
                            <p><?php echo $error; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" id="bookingForm" class="space-y-6">
                <!-- Film Selection -->
                <div class="bg-gray-800 rounded-xl p-6">
                    <h2 class="text-xl font-bold mb-4">
                        <i class="fas fa-film text-red-500 mr-2"></i>Pilih Film & Jadwal
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Film</label>
                            <select name="film_id" id="filmSelect" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required onchange="loadSchedules()">
                                <option value="">Pilih Film</option>
                                <?php while ($film = $films->fetch_assoc()): ?>
                                    <option value="<?php echo $film['id']; ?>"><?php echo htmlspecialchars($film['title']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Jadwal Tayang</label>
                            <select name="schedule_id" id="scheduleSelect" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required onchange="loadSeats()">
                                <option value="">Pilih Jadwal</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Seat Selection -->
                <div id="seatContainer" class="bg-gray-800 rounded-xl p-6" style="display: none;">
                    <h2 class="text-xl font-bold mb-4">
                        <i class="fas fa-couch text-purple-500 mr-2"></i>Pilih Kursi
                    </h2>
                    <div id="seatsDisplay"></div>
                    <input type="hidden" name="selected_seats" id="selectedSeatsInput">
                </div>

                <!-- Customer Info -->
                <div id="customerContainer" class="bg-gray-800 rounded-xl p-6" style="display: none;">
                    <h2 class="text-xl font-bold mb-4">
                        <i class="fas fa-user text-blue-500 mr-2"></i>Data Pemesan
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Nama Lengkap *</label>
                            <input type="text" name="customer_name" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Email (opsional)</label>
                            <input type="email" name="customer_email" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                            <p class="text-xs text-gray-400 mt-1">Jika diisi, e-ticket akan dikirim otomatis</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Telepon (opsional)</label>
                            <input type="tel" name="customer_phone" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                        </div>
                    </div>
                </div>

                <div id="submitContainer" class="text-right" style="display: none;">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition">
                        <i class="fas fa-check mr-2"></i>Buat Booking Offline
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
    const selectedSeats = [];
    
    function loadSchedules() {
        const filmId = document.getElementById('filmSelect').value;
        const scheduleSelect = document.getElementById('scheduleSelect');
        
        scheduleSelect.innerHTML = '<option value="">Pilih Jadwal</option>';
        document.getElementById('seatContainer').style.display = 'none';
        document.getElementById('customerContainer').style.display = 'none';
        document.getElementById('submitContainer').style.display = 'none';
        
        if (!filmId) return;
        
        fetch('get_schedules.php?film_id=' + filmId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.schedules.forEach(schedule => {
                        const option = document.createElement('option');
                        option.value = schedule.id;
                        option.textContent = `${schedule.show_date} - ${schedule.show_time} (${schedule.available_seats} kursi tersedia)`;
                        scheduleSelect.appendChild(option);
                    });
                }
            });
    }
    
    function loadSeats() {
        const scheduleId = document.getElementById('scheduleSelect').value;
        
        if (!scheduleId) {
            document.getElementById('seatContainer').style.display = 'none';
            document.getElementById('customerContainer').style.display = 'none';
            document.getElementById('submitContainer').style.display = 'none';
            return;
        }
        
        selectedSeats.length = 0;
        
        fetch('get_seats.php?schedule_id=' + scheduleId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displaySeats(data.seats);
                    document.getElementById('seatContainer').style.display = 'block';
                    document.getElementById('customerContainer').style.display = 'block';
                    document.getElementById('submitContainer').style.display = 'block';
                }
            });
    }
    
    function displaySeats(seatsByRow) {
        const container = document.getElementById('seatsDisplay');
        container.innerHTML = '';
        
        for (const [row, seats] of Object.entries(seatsByRow)) {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'flex items-center justify-center mb-2';
            
            const rowLabel = document.createElement('div');
            rowLabel.className = 'w-8 text-center font-bold text-gray-400 mr-2';
            rowLabel.textContent = row;
            rowDiv.appendChild(rowLabel);
            
            const seatsDiv = document.createElement('div');
            seatsDiv.className = 'flex flex-wrap justify-center';
            
            seats.forEach(seat => {
                const seatDiv = document.createElement('div');
                seatDiv.className = 'seat ' + seat.status;
                seatDiv.setAttribute('data-seat', seat.seat_label);
                seatDiv.setAttribute('data-status', seat.status);
                seatDiv.textContent = seat.seat_number;
                seatDiv.title = `Kursi ${seat.seat_label} - ${seat.status}`;
                
                if (seat.status === 'available') {
                    seatDiv.onclick = function() {
                        toggleSeat(this);
                    };
                }
                
                seatsDiv.appendChild(seatDiv);
            });
            
            rowDiv.appendChild(seatsDiv);
            container.appendChild(rowDiv);
        }
    }
    
    function toggleSeat(element) {
        const seatLabel = element.dataset.seat;
        const status = element.dataset.status;
        
        if (status !== 'available') return;
        
        if (element.classList.contains('selected')) {
            element.classList.remove('selected');
            element.classList.add('available');
            const index = selectedSeats.indexOf(seatLabel);
            if (index > -1) selectedSeats.splice(index, 1);
        } else {
            element.classList.remove('available');
            element.classList.add('selected');
            selectedSeats.push(seatLabel);
        }
        
        document.getElementById('selectedSeatsInput').value = JSON.stringify(selectedSeats);
    }
    </script>

</body>
</html>
