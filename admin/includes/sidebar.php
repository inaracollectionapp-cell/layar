<aside id="sidebar" class="fixed lg:sticky top-0 left-0 h-screen bg-gray-800 w-64 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 overflow-y-auto">
    <div class="p-6 border-b border-gray-700">
        <div class="flex items-center space-x-3">
        
            <div>
                <h2 class="font-bold text-lg"><?php echo getSetting('site_name', 'ISOLA SCREEN'); ?></h2>
                <p class="text-xs text-gray-400">Admin Panel</p>
            </div>
        </div>
    </div>

    <nav class="p-4">
        <ul class="space-y-2">
            <li>
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gray-700 text-red-500' : ''; ?>">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="films.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'films.php' ? 'bg-gray-700 text-red-500' : ''; ?>">
                    <i class="fas fa-film w-5"></i>
                    <span>Manajemen Film</span>
                </a>
            </li>
            <li>
                <a href="schedules.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'schedules.php' ? 'bg-gray-700 text-red-500' : ''; ?>">
                    <i class="fas fa-calendar-alt w-5"></i>
                    <span>Jadwal Tayang</span>
                </a>
            </li>
            <li>
                <a href="transactions.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'bg-gray-700 text-red-500' : ''; ?>">
                    <i class="fas fa-receipt w-5"></i>
                    <span>Transaksi</span>
                </a>
            </li>
            <li>
                <a href="create-offline-booking.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'create-offline-booking.php' ? 'bg-gray-700 text-red-500' : ''; ?>">
                    <i class="fas fa-ticket-alt w-5"></i>
                    <span>Booking Offline</span>
                </a>
            </li>
            <li>
                <a href="scan-reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'scan-reports.php' ? 'bg-gray-700 text-red-500' : ''; ?>">
                    <i class="fas fa-qrcode w-5"></i>
                    <span>Laporan Scan</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-gray-700 text-red-500' : ''; ?>">
                    <i class="fas fa-cog w-5"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
            <!-- Tambah di bagian menu -->
            <li class="mb-2">
                <a href="sponsors.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg transition <?php echo basename($_SERVER['PHP_SELF']) == 'sponsors.php' ? 'bg-gray-700 text-white' : ''; ?>">
                    <i class="fas fa-images mr-3"></i>
                    <span>Kelola Sponsor</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700 bg-gray-800">
        <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-red-600 transition text-red-500 hover:text-white">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        }
    });
</script>