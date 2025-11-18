<?php
require_once 'includes/config.php';

$siteName = getSetting('site_name', 'LAYAR ISOLA');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi - <?php echo $siteName; ?></title>
    
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
                <h1 class="text-lg font-bold">Kebijakan Privasi</h1>
                <div class="w-16"></div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <section class="py-8">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="bg-gray-800 rounded-xl p-6 md:p-8">
                <div class="text-center mb-8">
                    <i class="fas fa-shield-alt text-6xl text-red-500 mb-4"></i>
                    <h2 class="text-3xl font-bold">KEBIJAKAN PRIVASI - LAYAR ISOLA</h2>
                    <p class="text-gray-400 mt-2">Terakhir diperbarui: 17 November 2025</p>
                </div>

                <div class="space-y-8 text-gray-300">
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <p class="text-center">
                            Layar Isola menghormati privasi setiap pengguna yang melakukan pemesanan tiket melalui situs resmi kami. 
                            Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pribadi Anda.
                        </p>
                    </div>

                    <!-- 1. Informasi yang Kami Kumpulkan -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">1. Informasi yang Kami Kumpulkan</h3>
                        <p class="mb-3">Kami dapat mengumpulkan informasi berikut:</p>
                        
                        <div class="ml-4 space-y-4">
                            <div>
                                <h4 class="font-semibold text-lg mb-2">a. Informasi Pribadi</h4>
                                <ul class="list-disc list-inside space-y-1 ml-4">
                                    <li>Nama lengkap</li>
                                    <li>Alamat email</li>
                                    <li>Nomor telepon</li>
                                    <li>Institusi (opsional)</li>
                                </ul>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-lg mb-2">b. Informasi Transaksi</h4>
                                <ul class="list-disc list-inside space-y-1 ml-4">
                                    <li>Data pembelian tiket</li>
                                    <li>Metode pembayaran (tanpa menyimpan data kartu)</li>
                                    <li>Waktu dan detail pemutaran film</li>
                                </ul>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-lg mb-2">c. Data Teknis</h4>
                                <ul class="list-disc list-inside space-y-1 ml-4">
                                    <li>Alamat IP</li>
                                    <li>Informasi perangkat</li>
                                    <li>Aktivitas saat mengakses website</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Cara Kami Menggunakan Informasi Anda -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">2. Cara Kami Menggunakan Informasi Anda</h3>
                        <p class="mb-3">Informasi yang dikumpulkan akan digunakan untuk:</p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Memproses pemesanan tiket</li>
                            <li>Mengirimkan konfirmasi pembelian</li>
                            <li>Memberikan informasi terkait acara</li>
                            <li>Pengembangan layanan & evaluasi program</li>
                            <li>Keamanan dan pencegahan penyalahgunaan sistem</li>
                            <li>Keperluan dokumentasi internal HMFT</li>
                        </ul>
                        <div class="mt-3 p-3 bg-yellow-900 border border-yellow-700 rounded-lg">
                            <p class="font-semibold text-yellow-300">
                                Kami <strong>tidak menjual</strong> atau membagikan data pribadi Anda untuk kepentingan komersial pihak ketiga.
                            </p>
                        </div>
                    </div>

                    <!-- 3. Penyimpanan dan Keamanan Data -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">3. Penyimpanan dan Keamanan Data</h3>
                        <p class="mb-3">Kami menjaga data Anda dengan langkah-langkah yang wajar untuk mencegah:</p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Akses tidak sah</li>
                            <li>Penggunaan yang tidak sah</li>
                            <li>Kehilangan data</li>
                        </ul>
                        <p class="mt-3">Data disimpan pada server internal yang digunakan oleh tim Isola Screen dan HMFT.</p>
                    </div>

                    <!-- 4. Pembagian Data kepada Pihak Ketiga -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">4. Pembagian Data kepada Pihak Ketiga</h3>
                        <p class="mb-3">Data hanya dapat dibagikan kepada:</p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Penyedia layanan pembayaran (jika digunakan)</li>
                            <li>Pihak internal Prodi Film & Televisi / HMFT untuk keperluan pelaporan</li>
                        </ul>
                        <div class="mt-3 p-3 bg-yellow-900 border border-yellow-700 rounded-lg">
                            <p class="font-semibold text-yellow-300">
                                Kami <strong>tidak membagikan data kepada pihak luar</strong> di luar kepentingan operasional.
                            </p>
                        </div>
                    </div>

                    <!-- 5. Hak Pengguna -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">5. Hak Pengguna</h3>
                        <p class="mb-3">Anda berhak untuk:</p>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Meminta penghapusan data pribadi</li>
                            <li>Meminta salinan data yang kami simpan</li>
                            <li>Mengubah informasi yang salah atau tidak akurat</li>
                        </ul>
                        <p class="mt-3">Permohonan dapat dikirim melalui kontak resmi Layar Isola.</p>
                    </div>

                    <!-- 6. Cookies -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">6. Cookies</h3>
                        <p class="leading-relaxed">
                            Website kami mungkin menggunakan cookies untuk meningkatkan pengalaman pengguna.<br>
                            Anda dapat menonaktifkannya melalui pengaturan browser.
                        </p>
                    </div>

                    <!-- 7. Perubahan Kebijakan -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">7. Perubahan Kebijakan</h3>
                        <p class="leading-relaxed">
                            Kebijakan Privasi ini dapat berubah sewaktu-waktu.<br>
                            Perubahan akan diumumkan melalui website resmi.
                        </p>
                    </div>

                    <!-- 8. Kontak Kami -->
                    <div>
                        <h3 class="text-xl font-bold text-red-400 mb-3">8. Kontak Kami</h3>
                        <p class="mb-3">Jika Anda memiliki pertanyaan mengenai Kebijakan Privasi ini, hubungi:</p>
                        <div class="bg-gray-700 p-4 rounded-lg">
                            <p><strong>Email:</strong> <?php echo getSetting('contact_email', 'layarisola@upi.edu'); ?></p>
                            <p><strong>Instagram:</strong> @layarisola</p>
                            <p><strong>HMFT UPI</strong></p>
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