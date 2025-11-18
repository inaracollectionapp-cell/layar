<?php
require_once 'includes/config.php';

$keyword = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$date = isset($_GET['date']) ? sanitize($_GET['date']) : '';

$films = [];
if (!empty($keyword)) {
    $filmsResult = searchFilms($keyword);
} else {
    $filmsResult = getActiveFilms();
}

$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Film - <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .film-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .film-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .film-cover {
            aspect-ratio: 2/3;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-gray-900 shadow-lg border-b border-gray-800">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between mb-4">
                <a href="index.php" class="flex items-center space-x-2 hover:text-red-500 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                    <span class="font-semibold">Kembali</span>
                </a>
                <h1 class="text-lg font-bold"><?php echo $siteName; ?></h1>
                <div class="w-16"></div>
            </div>
            
            <!-- Search Form -->
            <form method="GET" class="relative">
                <input 
                    type="text" 
                    name="q" 
                    value="<?php echo htmlspecialchars($keyword); ?>"
                    placeholder="Cari film..."
                    class="w-full px-4 py-3 pl-12 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-red-500 text-white"
                    autofocus>
                <i class="fas fa-search absolute left-4 top-4 text-gray-400"></i>
                <?php if (!empty($keyword)): ?>
                    <a href="search.php" class="absolute right-4 top-4 text-gray-400 hover:text-white">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </header>

    <!-- Results -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            
            <?php if (!empty($keyword)): ?>
                <h2 class="text-xl font-bold mb-6">
                    Hasil pencarian untuk "<?php echo htmlspecialchars($keyword); ?>"
                </h2>
            <?php else: ?>
                <h2 class="text-xl font-bold mb-6">
                    Semua Film
                </h2>
            <?php endif; ?>

            <?php if ($filmsResult && $filmsResult->num_rows > 0): ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                    <?php while ($film = $filmsResult->fetch_assoc()): ?>
                        <a href="film-detail.php?id=<?php echo $film['id']; ?>" class="film-card block bg-gray-800 rounded-xl overflow-hidden shadow-xl">
                            <div class="relative">
                                <?php 
                                $coverPath = !empty($film['cover_image']) 
                                    ? 'assets/images/films/' . $film['cover_image'] 
                                    : 'https://via.placeholder.com/300x450/1f2937/ffffff?text=' . urlencode($film['title']); 
                                ?>
                                <img src="<?php echo $coverPath; ?>" 
                                     alt="<?php echo htmlspecialchars($film['title']); ?>" 
                                     class="film-cover w-full"
                                     onerror="this.src='https://via.placeholder.com/300x450/1f2937/ffffff?text=No+Image'">
                                
                                <div class="absolute top-3 right-3 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($film['rating']); ?>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h4 class="font-bold text-lg mb-2 line-clamp-2" title="<?php echo htmlspecialchars($film['title']); ?>">
                                    <?php echo htmlspecialchars($film['title']); ?>
                                </h4>
                                
                                <div class="flex items-center text-sm text-gray-400 mb-2">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span><?php echo $film['duration']; ?> menit</span>
                                </div>
                                
                                <div class="flex items-center text-sm text-gray-400 mb-3">
                                    <i class="fas fa-theater-masks mr-2"></i>
                                    <span class="line-clamp-1"><?php echo htmlspecialchars($film['genre']); ?></span>
                                </div>
                                
                                <button class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition">
                                    <i class="fas fa-ticket-alt mr-2"></i>Pesan Tiket
                                </button>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16 bg-gray-800 rounded-xl">
                    <i class="fas fa-search text-6xl text-gray-600 mb-4"></i>
                    <p class="text-xl text-gray-400">
                        <?php echo !empty($keyword) ? 'Tidak ada film yang ditemukan' : 'Belum ada film yang tersedia'; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

</body>
</html>
