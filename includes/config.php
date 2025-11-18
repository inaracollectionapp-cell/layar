<?php
// Configuration file untuk LAYAR ISOLA

// Database Configuration - UPDATED
define('DB_HOST', 'localhost');
define('DB_USER', 'rotz3716_isolascreen');
define('DB_PASS', 'Otongkecil');
define('DB_NAME', 'rotz3716_isolascreen');

// Site Configuration
define('SITE_URL', 'https://sleepwellindonesia.com/isolascreen/');
define('ADMIN_URL', SITE_URL . '/admin');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../assets/images/films/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Booking Configuration
define('BOOKING_TIMEOUT_MINUTES', 15);

// Currency
define('CURRENCY', 'IDR');
// Hapus salah satu definisi CURRENCY_SYMBOL jika ada duplikasi
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'Rp');
}

// Include database connection
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
?>