<?php
require_once '../includes/config.php';
requireAdminLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = sanitize($_POST['site_name']);
    $studioName = sanitize($_POST['studio_name']);
    $totalSeats = intval($_POST['total_seats']);
    $seatRows = intval($_POST['seat_rows']);
    $seatPerRow = intval($_POST['seat_per_row']);
    $bookingTimeout = intval($_POST['booking_timeout']);
    $contactEmail = sanitize($_POST['contact_email']);
    $contactPhone = sanitize($_POST['contact_phone']);
    
    $tripayApiKey = sanitize($_POST['tripay_api_key']);
    $tripayPrivateKey = sanitize($_POST['tripay_private_key']);
    $tripayMerchantCode = sanitize($_POST['tripay_merchant_code']);
    $tripayEnvironment = sanitize($_POST['tripay_environment']);
    
    $smtpHost = sanitize($_POST['smtp_host']);
    $smtpPort = intval($_POST['smtp_port']);
    $smtpUsername = sanitize($_POST['smtp_username']);
    $smtpPassword = sanitize($_POST['smtp_password']);
    $siteEmail = sanitize($_POST['site_email']);
    
    $settings = [
        'site_name' => $siteName,
        'studio_name' => $studioName,
        'total_seats' => $totalSeats,
        'seat_rows' => $seatRows,
        'seat_per_row' => $seatPerRow,
        'booking_timeout' => $bookingTimeout,
        'contact_email' => $contactEmail,
        'contact_phone' => $contactPhone,
        'tripay_api_key' => $tripayApiKey,
        'tripay_private_key' => $tripayPrivateKey,
        'tripay_merchant_code' => $tripayMerchantCode,
        'tripay_environment' => $tripayEnvironment,
        'smtp_host' => $smtpHost,
        'smtp_port' => $smtpPort,
        'smtp_username' => $smtpUsername,
        'smtp_password' => $smtpPassword,
        'site_email' => $siteEmail
    ];
    
    $allSuccess = true;
    foreach ($settings as $key => $value) {
        if (!updateSetting($key, $value)) {
            $allSuccess = false;
            break;
        }
    }
    
    if ($allSuccess) {
        $success = 'Pengaturan berhasil disimpan';
    } else {
        $error = 'Gagal menyimpan pengaturan';
    }
}

$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Pengaturan</h1>
                <p class="text-gray-400">Konfigurasi sistem dan integrasi</p>
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

            <form method="POST" class="space-y-6">
                <div class="bg-gray-800 rounded-xl p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-building text-red-500 mr-3"></i>Informasi Bioskop
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Nama Website</label>
                            <input type="text" name="site_name" value="<?php echo htmlspecialchars(getSetting('site_name')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Nama Studio</label>
                            <input type="text" name="studio_name" value="<?php echo htmlspecialchars(getSetting('studio_name')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Email Kontak</label>
                            <input type="email" name="contact_email" value="<?php echo htmlspecialchars(getSetting('contact_email')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Telepon Kontak</label>
                            <input type="text" name="contact_phone" value="<?php echo htmlspecialchars(getSetting('contact_phone')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-couch text-red-500 mr-3"></i>Konfigurasi Kursi
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Total Kursi</label>
                            <input type="number" name="total_seats" value="<?php echo htmlspecialchars(getSetting('total_seats')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Jumlah Baris</label>
                            <input type="number" name="seat_rows" value="<?php echo htmlspecialchars(getSetting('seat_rows')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                            <p class="text-xs text-gray-400 mt-1">Contoh: 5 untuk A, B, C, D, E</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Kursi per Baris</label>
                            <input type="number" name="seat_per_row" value="<?php echo htmlspecialchars(getSetting('seat_per_row')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-clock text-red-500 mr-3"></i>Konfigurasi Booking
                    </h2>
                    
                    <div class="max-w-md">
                        <label class="block text-sm font-semibold mb-2">Timeout Booking (menit)</label>
                        <input type="number" name="booking_timeout" value="<?php echo htmlspecialchars(getSetting('booking_timeout')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        <p class="text-xs text-gray-400 mt-1">Waktu maksimal untuk menyelesaikan pembayaran</p>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-credit-card text-red-500 mr-3"></i>Konfigurasi Tripay (QRIS Payment)
                    </h2>
                    
                    <div class="bg-yellow-900 border border-yellow-700 text-yellow-100 px-4 py-3 rounded-lg mb-4">
                        <i class="fas fa-info-circle mr-2"></i>
                        Dapatkan API Key dari dashboard Tripay Anda di <a href="https://tripay.co.id" target="_blank" class="underline">tripay.co.id</a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">API Key</label>
                            <input type="text" name="tripay_api_key" value="<?php echo htmlspecialchars(getSetting('tripay_api_key')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="DEV-...">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Private Key</label>
                            <input type="text" name="tripay_private_key" value="<?php echo htmlspecialchars(getSetting('tripay_private_key')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="xxxxx-xxxxx-xxxxx">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Merchant Code</label>
                            <input type="text" name="tripay_merchant_code" value="<?php echo htmlspecialchars(getSetting('tripay_merchant_code')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="T1234">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Environment</label>
                            <select name="tripay_environment" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                                <option value="sandbox" <?php echo getSetting('tripay_environment') === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                                <option value="production" <?php echo getSetting('tripay_environment') === 'production' ? 'selected' : ''; ?>>Production</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-xl p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-envelope text-red-500 mr-3"></i>Konfigurasi Email (SMTP)
                    </h2>
                    
                    <div class="bg-blue-900 border border-blue-700 text-blue-100 px-4 py-3 rounded-lg mb-4">
                        <i class="fas fa-info-circle mr-2"></i>
                        Untuk Gmail: Gunakan App Password. Tutorial: <a href="https://support.google.com/accounts/answer/185833" target="_blank" class="underline">support.google.com</a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?php echo htmlspecialchars(getSetting('smtp_host', 'smtp.gmail.com')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="smtp.gmail.com">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">SMTP Port</label>
                            <input type="number" name="smtp_port" value="<?php echo htmlspecialchars(getSetting('smtp_port', '587')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="587">
                            <p class="text-xs text-gray-400 mt-1">587 untuk TLS, 465 untuk SSL</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">SMTP Username/Email</label>
                            <input type="email" name="smtp_username" value="<?php echo htmlspecialchars(getSetting('smtp_username')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="youremail@gmail.com">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">SMTP Password</label>
                            <input type="password" name="smtp_password" value="<?php echo htmlspecialchars(getSetting('smtp_password')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="App Password">
                        </div>
                    </div>
                    
                    <div class="max-w-md">
                        <label class="block text-sm font-semibold mb-2">Email Pengirim Tiket</label>
                        <input type="email" name="site_email" value="<?php echo htmlspecialchars(getSetting('site_email', 'noreply@isolascreen.com')); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="noreply@isolascreen.com">
                        <p class="text-xs text-gray-400 mt-1">Email yang tampil sebagai pengirim tiket</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                    </button>
                </div>
            </form>
        </main>
    </div>

</body>
</html>
