<?php
// Helper Functions untuk ISOLA SCREEN

// Fungsi untuk mendapatkan pengaturan
function getSetting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

// Fungsi untuk update pengaturan
function updateSetting($key, $value) {
    global $conn;
    $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
    $stmt->bind_param("ss", $value, $key);
    return $stmt->execute();
}

// Fungsi untuk format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Fungsi untuk format tanggal Indonesia
function formatDateIndo($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $bulan[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    $dayName = $hari[date('w', $timestamp)];
    
    return "$dayName, $day $month $year";
}

// Fungsi untuk format waktu
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// Fungsi untuk generate booking code
function generateBookingCode() {
    return 'ISOLA-' . strtoupper(substr(uniqid(), -8));
}

// Fungsi untuk generate order ID
function generateOrderId() {
    return 'ORDER-' . time() . '-' . rand(1000, 9999);
}

// Fungsi untuk validasi email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Fungsi untuk sanitize input
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(strip_tags(trim($input)));
}

// Fungsi untuk upload gambar
function uploadImage($file, $targetDir = UPLOAD_DIR) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }

    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }

    if ($fileSize > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large. Max size: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }

    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $targetPath = $targetDir . $newFileName;

    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    if (move_uploaded_file($fileTmp, $targetPath)) {
        return ['success' => true, 'filename' => $newFileName, 'path' => $targetPath];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Fungsi untuk delete file
function deleteFile($filename, $directory = UPLOAD_DIR) {
    $filePath = $directory . $filename;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// Fungsi untuk check admin login
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Fungsi untuk require admin login
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Fungsi untuk mendapatkan film by ID
function getFilmById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM films WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fungsi untuk mendapatkan jadwal by ID
function getScheduleById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT s.*, f.title, f.duration, f.rating, f.cover_image 
                           FROM schedules s 
                           JOIN films f ON s.film_id = f.id 
                           WHERE s.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fungsi untuk mendapatkan booking by code
function getBookingByCode($code) {
    global $conn;
    $stmt = $conn->prepare("SELECT b.*, s.show_date, s.show_time, 
                           f.title, f.cover_image, f.is_double_feature, f.second_film_id,
                           f2.title as second_title
                           FROM bookings b
                           JOIN schedules s ON b.schedule_id = s.id
                           JOIN films f ON s.film_id = f.id
                           LEFT JOIN films f2 ON f.second_film_id = f2.id
                           WHERE b.booking_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fungsi untuk update status kursi
function updateSeatStatus($scheduleId, $seatLabels, $status) {
    global $conn;
    
    if (empty($seatLabels)) {
        return false;
    }
    
    $placeholders = str_repeat('?,', count($seatLabels) - 1) . '?';
    $sql = "UPDATE seats SET status = ? WHERE schedule_id = ? AND seat_label IN ($placeholders)";
    
    $stmt = $conn->prepare($sql);
    $types = 's' . str_repeat('s', count($seatLabels) + 1);
    $params = array_merge([$status, $scheduleId], $seatLabels);
    
    $stmt->bind_param($types, ...$params);
    return $stmt->execute();
}

// Fungsi untuk update available seats
function updateAvailableSeats($scheduleId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE schedules SET available_seats = (
        SELECT COUNT(*) FROM seats WHERE schedule_id = ? AND status = 'available'
    ) WHERE id = ?");
    $stmt->bind_param("ii", $scheduleId, $scheduleId);
    return $stmt->execute();
}

// Fungsi untuk check expired bookings dan update status
function checkExpiredBookings() {
    global $conn;
    
    // Get expired bookings
    $sql = "SELECT id, schedule_id, seats FROM bookings 
            WHERE booking_status = 'pending' 
            AND payment_status = 'unpaid' 
            AND expired_at < NOW()";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'expired' WHERE id = ?");
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        
        // Release seats
        $seats = json_decode($row['seats'], true);
        if ($seats) {
            updateSeatStatus($row['schedule_id'], $seats, 'available');
            updateAvailableSeats($row['schedule_id']);
        }
    }
}

// Fungsi untuk mendapatkan film yang sedang tayang
function getActiveFilms($limit = null) {
    global $conn;
    $sql = "SELECT * FROM films WHERE status = 'active' ORDER BY release_date DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    return $conn->query($sql);
}

// Fungsi untuk search film
function searchFilms($keyword) {
    global $conn;
    $keyword = '%' . $keyword . '%';
    $stmt = $conn->prepare("SELECT * FROM films WHERE status = 'active' AND (title LIKE ? OR genre LIKE ? OR cast LIKE ?) ORDER BY title");
    $stmt->bind_param("sss", $keyword, $keyword, $keyword);
    $stmt->execute();
    return $stmt->get_result();
}

// Fungsi untuk mendapatkan jadwal film
function getFilmSchedules($filmId, $date = null) {
    global $conn;
    
    if ($date) {
        $stmt = $conn->prepare("SELECT * FROM schedules WHERE film_id = ? AND show_date = ? AND status = 'active' ORDER BY show_time");
        $stmt->bind_param("is", $filmId, $date);
    } else {
        $stmt = $conn->prepare("SELECT * FROM schedules WHERE film_id = ? AND show_date >= CURDATE() AND status = 'active' ORDER BY show_date, show_time");
        $stmt->bind_param("i", $filmId);
    }
    
    $stmt->execute();
    return $stmt->get_result();
}

// Fungsi untuk mendapatkan kursi
function getSeats($scheduleId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM seats WHERE schedule_id = ? ORDER BY seat_row, seat_number");
    $stmt->bind_param("i", $scheduleId);
    $stmt->execute();
    return $stmt->get_result();
}

// ============================
// SPONSOR FUNCTIONS
// ============================

/**
 * Fungsi untuk mendapatkan sponsor aktif
 */
function getActiveSponsors($limit = null) {
    global $conn;
    $sql = "SELECT * FROM sponsors WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    return $conn->query($sql);
}

/**
 * Fungsi untuk mendapatkan semua sponsor (untuk admin)
 */
function getAllSponsors() {
    global $conn;
    $sql = "SELECT * FROM sponsors ORDER BY sort_order ASC, created_at DESC";
    return $conn->query($sql);
}

/**
 * Fungsi untuk mendapatkan sponsor by ID
 */
function getSponsorById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM sponsors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Fungsi untuk upload gambar sponsor
 */
function uploadSponsorImage($file) {
    $targetDir = __DIR__ . '/../assets/images/sponsors/';
    return uploadImage($file, $targetDir);
}

/**
 * Fungsi untuk menambah sponsor baru
 */
function addSponsor($name, $image, $url = null, $sortOrder = 0, $isActive = 1) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO sponsors (name, image, url, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $name, $image, $url, $sortOrder, $isActive);
    return $stmt->execute();
}

/**
 * Fungsi untuk update sponsor
 */
function updateSponsor($id, $name, $image = null, $url = null, $sortOrder = 0, $isActive = 1) {
    global $conn;
    
    if ($image) {
        $stmt = $conn->prepare("UPDATE sponsors SET name = ?, image = ?, url = ?, sort_order = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssiii", $name, $image, $url, $sortOrder, $isActive, $id);
    } else {
        $stmt = $conn->prepare("UPDATE sponsors SET name = ?, url = ?, sort_order = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssiii", $name, $url, $sortOrder, $isActive, $id);
    }
    
    return $stmt->execute();
}

/**
 * Fungsi untuk menghapus sponsor
 */
function deleteSponsor($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM sponsors WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

/**
 * Generate tiket individual untuk booking yang sudah dibayar
 */
function generateTicketsForBooking($bookingId) {
    global $conn;
    
    $booking = $conn->query("SELECT booking_code, seats, total_seats FROM bookings WHERE id = $bookingId")->fetch_assoc();
    if (!$booking) return false;
    
    $seats = json_decode($booking['seats'], true);
    $bookingCode = $booking['booking_code'];
    
    foreach ($seats as $seatLabel) {
        $ticketCode = $bookingCode . '-' . $seatLabel;
        
        $stmt = $conn->prepare("INSERT IGNORE INTO tickets (booking_id, ticket_code, seat_label, ticket_status) VALUES (?, ?, ?, 'valid')");
        $stmt->bind_param("iss", $bookingId, $ticketCode, $seatLabel);
        $stmt->execute();
    }
    
    return true;
}

/**
 * Get semua tiket untuk booking tertentu
 */
function getTicketsByBookingCode($bookingCode) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT t.*, b.booking_code, b.customer_name, b.customer_email, 
               s.show_date, s.show_time, f.title, f.is_double_feature, 
               f2.title as second_title, f.genre, f.rating
        FROM tickets t
        JOIN bookings b ON t.booking_id = b.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN films f ON s.film_id = f.id
        LEFT JOIN films f2 ON f.second_film_id = f2.id
        WHERE b.booking_code = ?
        ORDER BY t.seat_label ASC
    ");
    $stmt->bind_param("s", $bookingCode);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Get tiket by ticket code
 */
function getTicketByCode($ticketCode) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT t.*, b.booking_code, b.customer_name, b.customer_email, b.total_price,
               s.show_date, s.show_time, f.title, f.is_double_feature, 
               f2.title as second_title, f.genre, f.rating
        FROM tickets t
        JOIN bookings b ON t.booking_id = b.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN films f ON s.film_id = f.id
        LEFT JOIN films f2 ON f.second_film_id = f2.id
        WHERE t.ticket_code = ?
    ");
    $stmt->bind_param("s", $ticketCode);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Mark tiket sebagai digunakan
 */
function markTicketAsUsed($ticketCode) {
    global $conn;
    $stmt = $conn->prepare("UPDATE tickets SET ticket_status = 'used', used_at = NOW() WHERE ticket_code = ?");
    $stmt->bind_param("s", $ticketCode);
    return $stmt->execute();
}

/**
 * Check apakah semua tiket dalam booking sudah digunakan
 */
function checkAllTicketsUsed($bookingId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total, 
               SUM(CASE WHEN ticket_status = 'used' THEN 1 ELSE 0 END) as used_count
        FROM tickets WHERE booking_id = ?
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['total'] == $result['used_count'];
}

// Auto-run expired booking check
checkExpiredBookings();
?>