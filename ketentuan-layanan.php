<?php
require_once 'includes/config.php';

$siteName = getSetting('site_name', 'LAYAR ISOLA');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ketentuan Layanan - <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-gray-900 shadow-lg border-b border-gray-800">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="index.php" class="flex items-center space-x-2 hover:text-red-500 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                    <span class="font-semibold">Kembali</span>
                </a>
                <h1 class="text-lg font-bold">Ketentuan Layanan</h1>
                <div class="w-16"></div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <section class="py-8">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="bg-gray-800 rounded-xl p-6 md:p-8">
                <div class="text-center mb-8">
                    <i class="fas fa-file-contract text-6xl text-red-500 mb-4"></i>
                    <h2 class="text-3xl font-bold">KETENTUAN LAYANAN - LAYAR ISOLA</h2>
                    <p class="text-gray-400 mt-2">Terakhir diperbarui: 17 November 2025</p>
                </div>

                <div class="space-y-8 text-gray-300">
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <p class="text-center italic">
                            Dengan menggunakan website pemesanan tiket Layar Isola, Anda dianggap telah membaca, 
                            memahami, dan menyetujui Ketentuan Layanan berikut.
                        </p>
                    </div>

                    <!-- 1. Definisi -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">1. Definisi</h3>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li><strong>"Layar Isola"</strong>: Program pemutaran film yang dikelola HMFT dan Prodi Film & Televisi UPI.</li>
                            <li><strong>"Pengguna"</strong>: Individu yang mengakses atau memesan tiket melalui website.</li>
                            <li><strong>"Layanan"</strong>: Sistem pemesanan tiket dan informasi pemutaran film.</li>
                        </ul>
                    </div>

                    <!-- 2. Penggunaan Layanan -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">2. Penggunaan Layanan</h3>
                        <p class="mb-3">Pengguna setuju untuk:</p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Menggunakan website hanya untuk pemesanan yang sah</li>
                            <li>Tidak melakukan penyalahgunaan (hacking, duplikasi tiket, dll)</li>
                            <li>Mengisi data yang benar dan dapat dipertanggungjawabkan</li>
                        </ul>
                    </div>

                    <!-- 3. Harga dan Pembayaran -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">3. Harga dan Pembayaran</h3>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Harga tiket ditampilkan secara jelas pada halaman pemesanan.</li>
                            <li>Pembayaran dilakukan melalui metode yang disediakan.</li>
                            <li>Semua transaksi bersifat final dan <strong>tidak dapat dikembalikan</strong> kecuali acara dibatalkan oleh penyelenggara.</li>
                        </ul>
                    </div>

                    <!-- 4. Kebijakan Pembatalan -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">4. Kebijakan Pembatalan</h3>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Jika acara dibatalkan, pengguna berhak mendapatkan pengembalian dana penuh.</li>
                            <li>Jika pengguna berhalangan hadir, tiket <strong>tidak dapat ditukar atau direfund</strong>.</li>
                        </ul>
                    </div>

                    <!-- 5. Akses ke Acara -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">5. Akses ke Acara</h3>
                        <p class="mb-3">Pengguna wajib:</p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Membawa tiket digital atau bukti pembayaran</li>
                            <li>Mengikuti aturan venue (ketertiban, larangan merekam, dll)</li>
                            <li>Hadir sesuai waktu yang ditentukan</li>
                        </ul>
                    </div>

                    <!-- 6. Hak Kekayaan Intelektual -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">6. Hak Kekayaan Intelektual</h3>
                        <p class="mb-3">Semua konten film yang diputar adalah milik masing-masing film maker.</p>
                        <p class="mb-2"><strong>Dilarang keras:</strong></p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Merekam, memotret layar, atau menyebarkan cuplikan tanpa izin</li>
                            <li>Membajak atau menyalahgunakan karya film</li>
                        </ul>
                    </div>

                    <!-- 7. Pembatasan Tanggung Jawab -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">7. Pembatasan Tanggung Jawab</h3>
                        <p class="mb-2">Layar Isola tidak bertanggung jawab atas:</p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Gangguan teknis pada perangkat pengguna</li>
                            <li>Keterlambatan karena faktor eksternal</li>
                            <li>Kehilangan barang pribadi selama acara</li>
                        </ul>
                    </div>

                    <!-- 8. Perubahan Layanan -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">8. Perubahan Layanan</h3>
                        <p class="leading-relaxed">
                            Penyelenggara berhak mengubah jadwal, lokasi, atau ketentuan acara dengan pemberitahuan 
                            melalui website atau media sosial resmi.
                        </p>
                    </div>

                    <!-- 9. Penyelesaian Sengketa -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">9. Penyelesaian Sengketa</h3>
                        <p class="leading-relaxed">
                            Segala bentuk perselisihan akan diselesaikan secara musyawarah antara pengguna dan pihak penyelenggara.
                        </p>
                    </div>

                    <!-- 10. Ketentuan Pembayaran Tripay -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">10. Ketentuan Pembayaran Tripay</h3>
                        <p class="mb-3">Layanan pembayaran kami diproses melalui Tripay. Dengan menggunakan layanan ini, Anda juga menyetujui:</p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Semua transaksi pembayaran tunduk pada ketentuan dan kebijakan Tripay</li>
                            <li>Data pembayaran diproses secara aman oleh sistem Tripay</li>
                            <li>Layar Isola tidak menyimpan data kartu kredit atau informasi pembayaran sensitif lainnya</li>
                            <li>Masalah teknis terkait pembayaran akan dikoordinasikan dengan pihak Tripay</li>
                            <li>Untuk ketentuan lengkap mengenai layanan pembayaran, silakan kunjungi: 
                                <a href="https://tripay.co.id/page/terms-and-conditions" target="_blank" class="text-red-400 hover:text-red-300 underline">
                                    https://tripay.co.id/page/terms-and-conditions
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- 11. Persetujuan -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">11. Persetujuan</h3>
                        <div class="bg-gray-700 p-4 rounded-lg">
                            <p class="text-center font-semibold">
                                Dengan memesan tiket melalui website ini, pengguna menyatakan telah menyetujui seluruh Ketentuan Layanan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 py-8 mt-8">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <div class="flex items-center justify-center space-x-3 mb-4">
                    <img src="LOGO LAYAR ISOLA.webp" style="width: 150px;" alt="<?php echo $siteName; ?>">
                </div>
                <p class="text-gray-500 text-sm">
                    &copy; <?php echo date('Y'); ?> <?php echo $siteName; ?>. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>