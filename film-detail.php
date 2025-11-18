<?php
require_once 'includes/config.php';

$filmId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$filmId) {
    header('Location: index.php');
    exit();
}

$film = getFilmById($filmId);

if (!$film) {
    header('Location: index.php');
    exit();
}

// Get second film data if it's double feature
$secondFilm = null;
if ($film['is_double_feature'] == 1 && $film['second_film_id']) {
    $secondFilm = getFilmById($film['second_film_id']);
}

// Get schedules for this film
$schedulesResult = getFilmSchedules($filmId);

// Group schedules by date
$schedulesByDate = [];
while ($schedule = $schedulesResult->fetch_assoc()) {
    $date = $schedule['show_date'];
    if (!isset($schedulesByDate[$date])) {
        $schedulesByDate[$date] = [];
    }
    
    // Check if promo is active
    $today = date('Y-m-d');
    $promoStart = $schedule['promo_start_date'];
    $promoEnd = $schedule['promo_end_date'];
    $isPromoActive = $schedule['is_promo'] == 1 && 
                    (!$promoStart || $today >= $promoStart) && 
                    (!$promoEnd || $today <= $promoEnd);
    
    $schedule['is_promo_active'] = $isPromoActive;
    $schedule['final_price'] = $isPromoActive && !empty($schedule['promo_price']) ? $schedule['promo_price'] : $schedule['price'];
    
    $schedulesByDate[$date][] = $schedule;
}

$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($film['title']); ?> - <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .film-cover-bg {
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .film-cover-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(17, 24, 39, 0.7) 0%, rgba(17, 24, 39, 0.95) 70%, rgb(17, 24, 39) 100%);
        }
        .schedule-btn {
            transition: all 0.3s ease;
        }
        .schedule-btn:hover:not(:disabled) {
            transform: scale(1.05);
        }
        .schedule-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .promo-badge {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            font-weight: bold;
        }
        .double-feature-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        .film-poster {
            transition: transform 0.3s ease;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }
        .film-poster:hover {
            transform: translateY(-5px);
        }
        .plus-sign {
            font-size: 2rem;
            color: #fbbf24;
            font-weight: bold;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        .double-feature-badge {
            background: linear-gradient(45deg, #f59e0b, #d97706);
            color: white;
            font-weight: bold;
            border: 2px solid #fbbf24;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-gray-900 shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="index.php" class="flex items-center space-x-2 hover:text-red-500 transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                    <span class="font-semibold">Kembali</span>
                </a>
                <img src="LOGO LAYAR ISOLA.webp" style="width: 150px;">
                
            </div>
        </div>
    </header>

    <!-- Film Cover Hero -->
    <?php 
    $coverPath = !empty($film['cover_image']) 
        ? 'assets/images/films/' . $film['cover_image'] 
        : 'https://via.placeholder.com/1920x1080/1f2937/ffffff?text=' . urlencode($film['title']); 
    
    $secondCoverPath = $secondFilm && !empty($secondFilm['cover_image']) 
        ? 'assets/images/films/' . $secondFilm['cover_image'] 
        : 'https://via.placeholder.com/300x450/1f2937/ffffff?text=No+Image';
    ?>
    <section class="film-cover-bg" style="background-image: url('<?php echo $coverPath; ?>');">
        <div class="relative z-10 container mx-auto px-4 py-12">
            <div class="flex flex-col md:flex-row gap-6">
                <div class="flex-shrink-0">
                    <?php if ($film['is_double_feature'] == 1 && $secondFilm): ?>
                        <!-- Double Feature Layout -->
                        <div class="double-feature-container">
                            <div class="text-center">
                                <img src="<?php echo $coverPath; ?>" 
                                     alt="<?php echo htmlspecialchars($film['title']); ?>" 
                                     class="film-poster w-48 md:w-56 rounded-xl shadow-2xl"
                                     onerror="this.src='https://via.placeholder.com/300x450/1f2937/ffffff?text=No+Image'">
                                <p class="mt-2 text-sm font-semibold text-gray-300 max-w-[200px]"><?php echo htmlspecialchars($film['title']); ?></p>
                            </div>
                            
                            <div class="plus-sign">+</div>
                            
                            <div class="text-center">
                                <img src="<?php echo $secondCoverPath; ?>" 
                                     alt="<?php echo htmlspecialchars($secondFilm['title']); ?>" 
                                     class="film-poster w-48 md:w-56 rounded-xl shadow-2xl"
                                     onerror="this.src='https://via.placeholder.com/300x450/1f2937/ffffff?text=No+Image'">
                                <p class="mt-2 text-sm font-semibold text-gray-300 max-w-[200px]"><?php echo htmlspecialchars($secondFilm['title']); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Single Film Layout -->
                        <img src="<?php echo $coverPath; ?>" 
                             alt="<?php echo htmlspecialchars($film['title']); ?>" 
                             class="w-48 md:w-64 rounded-xl shadow-2xl mx-auto"
                             onerror="this.src='https://via.placeholder.com/300x450/1f2937/ffffff?text=No+Image'">
                    <?php endif; ?>
                </div>
                
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <div class="inline-block bg-red-600 text-white text-sm font-bold px-3 py-1 rounded">
                            <?php echo htmlspecialchars($film['rating']); ?>
                        </div>
                        
                        <?php if ($film['is_double_feature'] == 1 && $secondFilm): ?>
                            <div class="double-feature-badge text-white text-sm font-bold px-3 py-1 rounded flex items-center">
                                <i class="fas fa-film mr-2"></i>
                                <span>DOUBLE FEATURE</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">
                        <?php if ($film['is_double_feature'] == 1 && $secondFilm): ?>
                            <?php echo htmlspecialchars($film['title']); ?> & <?php echo htmlspecialchars($secondFilm['title']); ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars($film['title']); ?>
                        <?php endif; ?>
                    </h2>
                    
                    <div class="flex flex-wrap gap-4 mb-4 text-gray-300">
                        <div class="flex items-center">
                            <i class="fas fa-clock mr-2 text-red-500"></i>
                            <span>
                                <?php 
                                if ($film['is_double_feature'] == 1 && $secondFilm) {
                                    $totalDuration = $film['duration'] + $secondFilm['duration'];
                                    echo $totalDuration . ' menit (2 Film)';
                                } else {
                                    echo $film['duration'] . ' menit';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-theater-masks mr-2 text-red-500"></i>
                            <span>
                                <?php 
                                if ($film['is_double_feature'] == 1 && $secondFilm) {
                                    echo htmlspecialchars($film['genre']) . ' & ' . htmlspecialchars($secondFilm['genre']);
                                } else {
                                    echo htmlspecialchars($film['genre']);
                                }
                                ?>
                            </span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-calendar mr-2 text-red-500"></i>
                            <span><?php echo date('Y', strtotime($film['release_date'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($film['trailer_url']) || !empty($film['trailer_file'])): ?>
                    <button onclick="showTrailer()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg mb-4 transition">
                        <i class="fas fa-play-circle mr-2"></i>Tonton Trailer
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($film['director'])): ?>
                    <div class="mb-3">
                        <span class="text-gray-400">Sutradara:</span>
                        <span class="ml-2 text-white"><?php echo htmlspecialchars($film['director']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($film['cast'])): ?>
                    <div class="mb-4">
                        <span class="text-gray-400">Pemeran:</span>
                        <span class="ml-2 text-white"><?php echo htmlspecialchars($film['cast']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Film Description -->
                    <div class="mb-6">
                        <h3 class="text-xl font-bold mb-2 text-red-400">Sinopsis</h3>
                        <p class="text-gray-300 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($film['description'])); ?>
                        </p>
                    </div>
                    
                    <?php if ($film['is_double_feature'] == 1 && $secondFilm): ?>
                    <!-- Second Film Description -->
                    <div class="mb-6 p-4 bg-gray-800 rounded-lg border-l-4 border-yellow-500">
                        <h3 class="text-xl font-bold mb-2 text-yellow-400">Film Kedua: <?php echo htmlspecialchars($secondFilm['title']); ?></h3>
                        
                        <?php if (!empty($secondFilm['director'])): ?>
                        <div class="mb-2">
                            <span class="text-gray-400">Sutradara:</span>
                            <span class="ml-2 text-white"><?php echo htmlspecialchars($secondFilm['director']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($secondFilm['cast'])): ?>
                        <div class="mb-3">
                            <span class="text-gray-400">Pemeran:</span>
                            <span class="ml-2 text-white"><?php echo htmlspecialchars($secondFilm['cast']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <p class="text-gray-300 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($secondFilm['description'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($film['trailer_url'])): ?>
                    <a href="<?php echo htmlspecialchars($film['trailer_url']); ?>" 
                       target="_blank" 
                       class="inline-block bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg transition">
                        <i class="fas fa-play mr-2"></i>Tonton Trailer
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Schedules Section -->
    <section class="py-8 bg-gray-900">
        <div class="container mx-auto px-4">
            <h3 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-calendar-alt text-red-500 mr-3"></i>Pilih Jadwal Tayang
            </h3>

            <?php if (!empty($schedulesByDate)): ?>
                <?php foreach ($schedulesByDate as $date => $schedules): ?>
                    <div class="mb-8 bg-gray-800 rounded-xl p-6">
                        <h4 class="text-xl font-bold mb-4 text-red-400">
                            <?php echo formatDateIndo($date); ?>
                        </h4>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            <?php foreach ($schedules as $schedule): ?>
                                <?php 
                                $isFull = $schedule['available_seats'] <= 0;
                                $isActive = $schedule['status'] === 'active';
                                $isPromoActive = $schedule['is_promo_active'];
                                ?>
                                <button 
                                    onclick="selectSchedule(<?php echo $schedule['id']; ?>)"
                                    class="schedule-btn bg-gray-700 hover:bg-red-600 p-4 rounded-lg text-center <?php echo ($isFull || !$isActive) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                    <?php echo ($isFull || !$isActive) ? 'disabled' : ''; ?>>
                                    <div class="text-2xl font-bold mb-1">
                                        <?php echo formatTime($schedule['show_time']); ?>
                                    </div>
                                    <div class="text-sm text-gray-300 mb-2">
                                        <?php if ($isPromoActive && !empty($schedule['promo_price'])): ?>
                                            <div class="flex flex-col items-center">
                                                <span class="line-through text-gray-500 text-xs"><?php echo formatCurrency($schedule['price']); ?></span>
                                                <span class="text-green-400 font-bold text-lg"><?php echo formatCurrency($schedule['promo_price']); ?></span>
                                                <span class="promo-badge text-xs px-2 py-0.5 rounded mt-1">PROMO</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-lg font-semibold"><?php echo formatCurrency($schedule['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs <?php echo $isFull ? 'text-red-400' : 'text-green-400'; ?>">
                                        <i class="fas fa-couch mr-1"></i>
                                        <?php 
                                        if ($isFull) {
                                            echo 'Penuh';
                                        } else {
                                            echo $schedule['available_seats'] . ' kursi tersisa';
                                        }
                                        ?>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-16 bg-gray-800 rounded-xl">
                    <i class="fas fa-calendar-times text-6xl text-gray-600 mb-4"></i>
                    <p class="text-xl text-gray-400">Belum ada jadwal tayang untuk film ini</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 py-6">
        <div class="container mx-auto px-4 text-center text-gray-500 text-sm">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $siteName; ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Trailer Modal -->
    <div id="trailerModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4">
        <div class="max-w-4xl w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold">Trailer - <?php echo htmlspecialchars($film['title']); ?></h3>
                <button onclick="closeTrailer()" class="text-white hover:text-red-500 text-3xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="bg-black rounded-lg overflow-hidden">
                <?php if (!empty($film['trailer_file'])): ?>
                    <video id="trailerVideo" controls class="w-full">
                        <source src="assets/videos/trailers/<?php echo htmlspecialchars($film['trailer_file']); ?>" type="video/mp4">
                        Browser Anda tidak mendukung video player.
                    </video>
                <?php elseif (!empty($film['trailer_url'])): ?>
                    <?php
                    $trailerUrl = $film['trailer_url'];
                    if (strpos($trailerUrl, 'youtube.com') !== false || strpos($trailerUrl, 'youtu.be') !== false) {
                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $trailerUrl, $matches);
                        $videoId = $matches[1] ?? '';
                        if ($videoId) {
                            echo '<iframe id="trailerFrame" width="100%" height="500" src="https://www.youtube.com/embed/' . $videoId . '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                        }
                    } else {
                        echo '<iframe id="trailerFrame" width="100%" height="500" src="' . htmlspecialchars($trailerUrl) . '" frameborder="0" allowfullscreen></iframe>';
                    }
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function selectSchedule(scheduleId) {
            window.location.href = 'booking.php?schedule=' + scheduleId;
        }
        
        function showTrailer() {
            document.getElementById('trailerModal').classList.remove('hidden');
        }
        
        function closeTrailer() {
            document.getElementById('trailerModal').classList.add('hidden');
            const video = document.getElementById('trailerVideo');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
            const frame = document.getElementById('trailerFrame');
            if (frame) {
                frame.src = frame.src;
            }
        }
        
        document.getElementById('trailerModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeTrailer();
            }
        });
    </script>
</body>
</html>