<?php
// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/ticket-functions.php';

// Log access
error_log("Scan Ticket Page Accessed: " . date('Y-m-d H:i:s'));

$siteName = getSetting('site_name', 'ISOLA SCREEN');
$validationResult = null;
$scannedData = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qrData = isset($_POST['qr_data']) ? trim($_POST['qr_data']) : '';
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $scannedData = $qrData;
    
    error_log("POST Data Received - QR Data: " . $qrData . ", Action: " . $action);
    
    if (!empty($qrData)) {
        $validationResult = validateTicketQRCode($qrData);
        error_log("Validation Result: " . ($validationResult['valid'] ? 'VALID' : 'INVALID'));
        
        // If valid and action is validate, mark as used
        if ($validationResult['valid'] && $action === 'validate') {
            // Check jika ini individual ticket atau booking code
            if (isset($validationResult['is_individual']) && $validationResult['is_individual']) {
                // Individual ticket - mark this specific ticket as used
                $ticketCode = extractTicketCode($qrData);
                $success = markTicketAsUsed($ticketCode);
                
                if ($success) {
                    $validationResult['ticket']['ticket_status'] = 'used';
                    $validationResult['message'] = 'Tiket kursi ' . $validationResult['ticket']['seat_label'] . ' berhasil divalidasi';
                    error_log("Individual ticket marked as used: " . $ticketCode);
                    
                    // Check jika semua tiket dalam booking sudah digunakan
                    $stmt = $conn->prepare("SELECT id FROM bookings WHERE booking_code = ?");
                    $stmt->bind_param("s", $validationResult['ticket']['booking_code']);
                    $stmt->execute();
                    $bookingData = $stmt->get_result()->fetch_assoc();
                    
                    if ($bookingData && checkAllTicketsUsed($bookingData['id'])) {
                        $conn->query("UPDATE bookings SET booking_status = 'used' WHERE id = " . $bookingData['id']);
                        error_log("All tickets used, booking marked as used: " . $validationResult['ticket']['booking_code']);
                    }
                } else {
                    $validationResult['message'] = 'Gagal menandai tiket sebagai digunakan';
                    error_log("Failed to mark individual ticket as used");
                }
            } else {
                // Legacy booking code - mark entire booking as used
                if ($validationResult['booking']['booking_status'] !== 'used') {
                    $success = markTicketAsUsed($validationResult['booking']['booking_code']);
                    if ($success) {
                        $validationResult['booking']['booking_status'] = 'used';
                        $validationResult['message'] = 'Booking berhasil divalidasi dan ditandai sebagai sudah digunakan';
                        error_log("Booking marked as used: " . $validationResult['booking']['booking_code']);
                    } else {
                        $validationResult['message'] = 'Gagal menandai tiket sebagai digunakan';
                        error_log("Failed to mark booking as used");
                    }
                }
            }
        }
    } else {
        $validationResult = [
            'valid' => false,
            'message' => 'Data QR Code tidak boleh kosong'
        ];
        error_log("Empty QR data submitted");
    }
}

// Function untuk debug JSON extraction
function debugJSON($qrData) {
    $result = [
        'original' => $qrData,
        'extracted' => extractBookingCode($qrData),
        'json_decode' => null,
        'json_error' => null
    ];
    
    // Test JSON decode
    $jsonTest = json_decode($qrData, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $result['json_decode'] = $jsonTest;
    } else {
        $result['json_error'] = json_last_error_msg();
    }
    
    return $result;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Tiket - <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- QR Code Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    <style>
        .hidden {
            display: none;
        }
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        #qr-reader__dashboard_section {
            display: none;
        }
        .scanner-active {
            border: 3px solid #10B981;
            border-radius: 10px;
        }
        .debug-info {
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    
    <!-- Header -->
    <header class="bg-gray-800 border-b border-gray-700">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold flex items-center">
                    <i class="fas fa-qrcode text-red-500 mr-3"></i><?php echo $siteName; ?> - Scan Tiket
                </h1>
                <a href="admin/index.php" class="text-gray-400 hover:text-white">
                    <i class="fas fa-home text-xl"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            
         

           
            <!-- Tab Selection -->
            <div class="bg-gray-800 rounded-lg p-2 mb-6 flex gap-2">
                <button 
                    onclick="switchTab('camera')" 
                    id="tabCamera"
                    class="flex-1 py-3 rounded-lg bg-red-600 font-semibold transition">
                    <i class="fas fa-camera mr-2"></i>Scan QR
                </button>
                <button 
                    onclick="switchTab('manual')" 
                    id="tabManual"
                    class="flex-1 py-3 rounded-lg bg-gray-700 hover:bg-gray-600 font-semibold transition">
                    <i class="fas fa-keyboard mr-2"></i>Input Manual
                </button>
            </div>

            <!-- Camera Scanner Section -->
            <div id="cameraSection" class="mb-6">
                <div class="bg-gray-800 rounded-xl p-6">
                    <div class="text-center mb-4">
                        <h3 class="text-lg font-semibold mb-2">Scanner QR Code</h3>
                        <p class="text-sm text-gray-400">Izinkan akses kamera untuk menggunakan scanner</p>
                        <p class="text-xs text-green-400 mt-1">
                            <i class="fas fa-check mr-1"></i>Mendukung format JSON
                        </p>
                    </div>
                    
                    <div id="qr-reader" class="rounded-lg overflow-hidden mb-4"></div>
                    
                    <div class="text-center">
                        <p class="text-sm text-gray-400 mb-3">
                            <i class="fas fa-camera mr-2"></i>Posisikan QR Code di dalam frame
                        </p>
                        <button 
                            onclick="stopScanner()" 
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-stop mr-2"></i>Stop Scanner
                        </button>
                    </div>
                </div>
            </div>

            <!-- Manual Input Section -->
            <div id="manualSection" class="hidden mb-6">
                <div class="bg-gray-800 rounded-xl p-6">
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">
                                <i class="fas fa-ticket-alt mr-2"></i>Kode Booking atau Data QR
                            </label>
                            <input 
                                type="text" 
                                name="qr_data" 
                                value="<?php echo htmlspecialchars($scannedData); ?>"
                                class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent text-white text-lg font-mono"
                                placeholder='Masukkan kode booking atau data QR (contoh: ISOLA-12345678 atau {"booking_code":"ISOLA-12345678"})'
                                required
                                autocomplete="off">
                            <p class="text-xs text-gray-400 mt-1">
                                Contoh: <code class="bg-gray-700 px-1 rounded">ISOLA-C326991A</code> atau 
                                <code class="bg-gray-700 px-1 rounded">{"booking_code":"ISOLA-F44112FC"}</code>
                            </p>
                        </div>
                        <button 
                            type="submit" 
                            name="action" 
                            value="check"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition">
                            <i class="fas fa-search mr-2"></i>Cek & Validasi Tiket
                        </button>
                    </form>
                    
                    <!-- Test dengan kode booking yang sudah ada -->
                    <div class="mt-4 p-4 bg-gray-700 rounded-lg">
                        <p class="text-sm text-gray-400 mb-2">Test dengan kode booking yang ada di database:</p>
                        <div class="flex gap-2 flex-wrap">
                            <?php
                            // Ambil beberapa kode booking dari database untuk testing
                            $testStmt = $conn->prepare("
                                SELECT booking_code 
                                FROM bookings 
                                WHERE payment_status = 'paid' 
                                AND booking_status IN ('confirmed', 'pending')
                                ORDER BY id DESC 
                                LIMIT 5
                            ");
                            if ($testStmt) {
                                $testStmt->execute();
                                $testResult = $testStmt->get_result();
                                $hasTestData = false;
                                while ($test = $testResult->fetch_assoc()) {
                                    $hasTestData = true;
                                    echo '<button type="button" onclick="setTestCode(\'' . $test['booking_code'] . '\')" class="px-3 py-2 bg-green-600 hover:bg-green-700 rounded text-sm font-mono transition">' . $test['booking_code'] . '</button>';
                                }
                                $testStmt->close();
                                
                                if (!$hasTestData) {
                                    echo '<p class="text-yellow-400 text-sm">Tidak ada data tiket yang bisa di-test</p>';
                                }
                            } else {
                                echo '<p class="text-red-400 text-sm">Error mengambil data test</p>';
                            }
                            ?>
                        </div>
                        
                        <div class="mt-3 pt-3 border-t border-gray-600">
                            <p class="text-sm text-gray-400 mb-2">Test dengan format JSON:</p>
                            <div class="flex gap-2 flex-wrap">
                                <?php
                                $jsonTestStmt = $conn->prepare("
                                    SELECT booking_code 
                                    FROM bookings 
                                    WHERE payment_status = 'paid' 
                                    AND booking_status IN ('confirmed', 'pending')
                                    ORDER BY id DESC 
                                    LIMIT 2
                                ");
                                if ($jsonTestStmt) {
                                    $jsonTestStmt->execute();
                                    $jsonTestResult = $jsonTestStmt->get_result();
                                    while ($test = $jsonTestResult->fetch_assoc()) {
                                        $jsonData = json_encode([
                                            'booking_code' => $test['booking_code'],
                                            'timestamp' => time(),
                                            'type' => 'ticket',
                                            'site' => 'ISOLA SCREEN'
                                        ]);
                                        echo '<button type="button" onclick="setTestCode(\'' . addslashes($jsonData) . '\')" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 rounded text-xs font-mono transition break-all">JSON: ' . $test['booking_code'] . '</button>';
                                    }
                                    $jsonTestStmt->close();
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validation Result -->
            <?php if ($validationResult): ?>
            <div class="bg-gray-800 rounded-xl overflow-hidden shadow-2xl mb-6">
                <?php if ($validationResult['valid']): ?>
                    <!-- Valid Ticket -->
                    <div class="bg-green-600 p-6 text-center">
                        <i class="fas fa-check-circle text-6xl mb-3"></i>
                        <h2 class="text-2xl font-bold">TIKET VALID</h2>
                        <p class="text-sm mt-2"><?php echo htmlspecialchars($validationResult['message']); ?></p>
                    </div>
                    
                    <div class="p-6">
                        <?php $booking = $validationResult['booking']; ?>
                        <?php $seats = json_decode($booking['seats'], true); ?>
                        
                        <div class="space-y-4">
                            <div class="bg-gray-700 p-4 rounded-lg text-center">
                                <p class="text-sm text-gray-400 mb-1">Kode Booking</p>
                                <p class="text-2xl font-bold text-red-400 font-mono"><?php echo htmlspecialchars($booking['booking_code']); ?></p>
                            </div>
                            
                            <div class="text-center">
                                <p class="text-sm text-gray-400 mb-1">Film</p>
                                <p class="text-xl font-bold"><?php echo htmlspecialchars($booking['title']); ?></p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-400 mb-1">Tanggal</p>
                                    <p class="font-semibold"><?php echo formatDateIndo($booking['show_date']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-400 mb-1">Waktu</p>
                                    <p class="font-semibold"><?php echo formatTime($booking['show_time']); ?></p>
                                </div>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-400 mb-1">Nama Pemesan</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                            </div>
                            
                            <div class="bg-gray-700 p-4 rounded-lg text-center">
                                <p class="text-sm text-gray-400 mb-1">Kursi</p>
                                <p class="font-semibold text-lg"><?php echo is_array($seats) ? implode(', ', $seats) : $seats; ?></p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-400 mb-1">Jumlah Tiket</p>
                                <p class="font-semibold"><?php echo $booking['total_seats']; ?> tiket</p>
                            </div>
                            
                            <div class="bg-gray-700 p-4 rounded-lg">
                                <p class="text-sm text-gray-400 mb-1">Status Tiket</p>
                                <?php if ($booking['booking_status'] === 'used'): ?>
                                    <p class="text-lg font-bold text-yellow-400 text-center">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>SUDAH DIGUNAKAN
                                    </p>
                                    <?php if ($booking['used_at']): ?>
                                    <p class="text-sm text-gray-400 mt-1 text-center">
                                        Digunakan pada: <?php echo date('d/m/Y H:i', strtotime($booking['used_at'])); ?>
                                    </p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-lg font-bold text-green-400 text-center">
                                        <i class="fas fa-check mr-2"></i>BELUM DIGUNAKAN
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1 text-center">
                                        Tiket valid dan siap digunakan
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($booking['booking_status'] !== 'used'): ?>
                        <form method="POST" class="mt-6">
                            <input type="hidden" name="qr_data" value="<?php echo htmlspecialchars($scannedData); ?>">
                            <button 
                                type="submit" 
                                name="action" 
                                value="validate"
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-lg transition text-lg">
                                <i class="fas fa-check-double mr-2"></i>Validasi & Tandai Digunakan
                            </button>
                            <p class="text-xs text-gray-400 text-center mt-2">
                                Tombol ini akan menandai tiket sebagai sudah digunakan
                            </p>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- Invalid Ticket -->
                    <div class="bg-red-600 p-6 text-center">
                        <i class="fas fa-times-circle text-6xl mb-3"></i>
                        <h2 class="text-2xl font-bold">TIKET TIDAK VALID</h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="bg-red-900 border border-red-700 rounded-lg p-4 text-center">
                            <p class="text-lg font-semibold"><?php echo htmlspecialchars($validationResult['message']); ?></p>
                        </div>
                        
                        
                    </div>
                <?php endif; ?>
                
                <div class="bg-gray-700 p-4 text-center">
                    <button 
                        onclick="location.reload()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-redo mr-2"></i>Scan Tiket Lain
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="bg-gray-800 rounded-xl p-6 text-center">
                <h3 class="font-semibold mb-4">Quick Actions</h3>
                <div class="flex gap-3 justify-center">
                    <a href="admin/index.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard Admin
                    </a>
                    <button onclick="location.reload()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh Halaman
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        let html5QrCode = null;
        let isScannerActive = false;
        
        function switchTab(tab) {
            if (tab === 'camera') {
                document.getElementById('cameraSection').classList.remove('hidden');
                document.getElementById('manualSection').classList.add('hidden');
                document.getElementById('tabCamera').classList.remove('bg-gray-700', 'hover:bg-gray-600');
                document.getElementById('tabCamera').classList.add('bg-red-600');
                document.getElementById('tabManual').classList.remove('bg-red-600');
                document.getElementById('tabManual').classList.add('bg-gray-700', 'hover:bg-gray-600');
                startScanner();
            } else {
                document.getElementById('cameraSection').classList.add('hidden');
                document.getElementById('manualSection').classList.remove('hidden');
                document.getElementById('tabManual').classList.remove('bg-gray-700', 'hover:bg-gray-600');
                document.getElementById('tabManual').classList.add('bg-red-600');
                document.getElementById('tabCamera').classList.remove('bg-red-600');
                document.getElementById('tabCamera').classList.add('bg-gray-700', 'hover:bg-gray-600');
                stopScanner();
            }
        }
        
        function startScanner() {
            if (html5QrCode || isScannerActive) {
                return; // Already started
            }
            
            console.log("Starting QR Scanner...");
            html5QrCode = new Html5Qrcode("qr-reader");
            isScannerActive = true;
            
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_QR_CODE],
                aspectRatio: 1.0
            };
            
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess,
                onScanError
            ).then(() => {
                console.log("QR Scanner started successfully");
                document.getElementById('qr-reader').classList.add('scanner-active');
            }).catch(err => {
                console.error("Unable to start scanning", err);
                alert("Tidak dapat mengakses kamera. Silakan gunakan input manual.\n\nError: " + err.message);
                switchTab('manual');
                isScannerActive = false;
            });
        }
        
        function stopScanner() {
            if (html5QrCode && isScannerActive) {
                console.log("Stopping QR Scanner...");
                html5QrCode.stop().then(() => {
                    console.log("QR Scanner stopped successfully");
                    html5QrCode = null;
                    isScannerActive = false;
                    document.getElementById('qr-reader').classList.remove('scanner-active');
                }).catch(err => {
                    console.error("Error stopping scanner:", err);
                    html5QrCode = null;
                    isScannerActive = false;
                });
            }
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            console.log("QR Scan Success:", decodedText);
            
            // Show loading state
            document.getElementById('qr-reader').style.opacity = '0.7';
            
            // Stop scanning
            stopScanner();
            
            // Extract booking code from QR data
            let bookingCode = decodedText;
            
            // Try to parse as JSON first
            try {
                const jsonData = JSON.parse(decodedText);
                if (jsonData.booking_code) {
                    bookingCode = jsonData.booking_code;
                    console.log("Extracted from JSON:", bookingCode);
                }
            } catch (e) {
                // Not JSON, use raw decoded text
                console.log("Using raw QR data:", bookingCode);
            }
            
            // Submit form with QR data
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="qr_data" value="${bookingCode}">
                <input type="hidden" name="action" value="check">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function onScanError(errorMessage) {
            // Ignore scan errors (happens continuously when no QR in view)
        }
        
        // Function untuk test button
        function setTestCode(code) {
            document.querySelector('input[name="qr_data"]').value = code;
            // Auto focus ke input field
            document.querySelector('input[name="qr_data"]').focus();
        }
        
        // Start scanner on page load for camera tab
        window.addEventListener('load', function() {
            if (!document.getElementById('cameraSection').classList.contains('hidden')) {
                setTimeout(startScanner, 1000); // Delay sedikit untuk inisialisasi
            }
        });
        
        // Cleanup scanner when page unloads
        window.addEventListener('beforeunload', function() {
            stopScanner();
        });
    </script>
</body>
</html>