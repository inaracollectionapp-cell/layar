<?php
require_once 'includes/config.php';

$siteName = getSetting('site_name', 'ISOLA SCREEN');

// Get active sponsors for carousel
$sponsors = getActiveSponsors(5); // Max 5 sponsors

// Data untuk carousel slides - ambil dari sponsors aktif
$carouselSlides = [];
if ($sponsors && $sponsors->num_rows > 0) {
    while ($sponsor = $sponsors->fetch_assoc()) {
        $carouselSlides[] = [
            'id' => $sponsor['id'],
            'name' => $sponsor['name'],
            'image' => 'assets/images/sponsors/' . $sponsor['image'],
            'url' => $sponsor['url']
        ];
    }
} else {
    // Fallback slides jika tidak ada sponsor - GUNAKAN LOCAL IMAGES
    $carouselSlides = [
        [
            'id' => 1,
            'name' => '',
            'image' => 'assets/images/hero-banner-1.jpg',
            'url' => '#'
        ],
        [
            'id' => 2,
            'name' => '',
            'image' => 'assets/images/hero-banner-2.jpg',
            'url' => '#'
        ],
        [
            'id' => 3,
            'name' => '',
            'image' => 'assets/images/hero-banner-3.jpg',
            'url' => '#'
        ]
    ];
}

// Get active films untuk section "Sedang Tayang"
$films = getActiveFilms();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $siteName; ?> - Pemesanan Tiket Bioskop</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        .film-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .film-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .film-cover {
            aspect-ratio: 2/3;
            object-fit: cover;
            width: 100%;
        }
        .film-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .film-title {
            min-height: 3rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .film-info {
            flex: 1;
        }
        
        /* Sponsor Carousel Styles - ASPECT RATIO 19:6 */
        .sponsor-carousel {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            margin: 20px auto;
            max-width: 1200px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            aspect-ratio: 19/6; /* Rasio 19:6 */
        }
        .sponsor-carousel-inner {
            display: flex;
            transition: transform 0.5s ease;
            border-radius: 12px;
            height: 100%;
        }
        .sponsor-carousel-item {
            min-width: 100%;
            transition: opacity 0.5s ease;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
        }
        .sponsor-carousel-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }
        .sponsor-carousel-control {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
        }
        .sponsor-carousel-control:hover {
            background-color: rgba(0, 0, 0, 0.8);
            transform: translateY(-50%) scale(1.1);
        }
        .sponsor-carousel-control.prev {
            left: 15px;
        }
        .sponsor-carousel-control.next {
            right: 15px;
        }
        .sponsor-carousel-indicators {
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 8px;
            z-index: 10;
        }
        .sponsor-carousel-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .sponsor-carousel-indicator.active {
            background-color: white;
            transform: scale(1.2);
        }
        
        /* Sponsor Name Overlay */
        .sponsor-name-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0) 100%);
            padding: 20px 15px 15px;
            border-radius: 0 0 12px 12px;
            text-align: center;
        }
        .sponsor-name {
            font-size: 20px;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        /* Clickable sponsor link */
        .sponsor-link {
            display: block;
            cursor: pointer;
            text-decoration: none;
            height: 100%;
        }
        .sponsor-link:hover .sponsor-name {
            color: #fbbf24;
        }

        /* Grid container untuk film cards */
        .films-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        

        /* Responsive Design untuk Banner 19:6 */
        @media (max-width: 1024px) {
            .sponsor-carousel {
                aspect-ratio: 19/6;
                margin: 15px auto;
            }
            .sponsor-name {
                font-size: 18px;
            }
        }

        @media (max-width: 768px) {
            .sponsor-carousel {
                aspect-ratio: 19/6;
                border-radius: 8px;
                margin: 10px auto;
            }
            .sponsor-carousel-inner {
                border-radius: 8px;
            }
            .sponsor-carousel-item {
                border-radius: 8px;
            }
            .sponsor-carousel-image {
                border-radius: 8px;
            }
            .sponsor-name {
                font-size: 16px;
            }
            .sponsor-name-overlay {
                padding: 15px 10px 10px;
            }
            .sponsor-carousel-control {
                width: 35px;
                height: 35px;
            }
            .sponsor-carousel-control.prev {
                left: 10px;
            }
            .sponsor-carousel-control.next {
                right: 10px;
            }
        }

        @media (max-width: 640px) {
            .films-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .sponsor-carousel {
                aspect-ratio: 19/6;
            }
            
            .sponsor-name {
                font-size: 14px;
            }
            
            .sponsor-carousel-control {
                width: 30px;
                height: 30px;
            }
            
            .sponsor-carousel-control i {
                font-size: 14px;
            }
            
            .sponsor-carousel-indicators {
                bottom: 10px;
            }
            
            .sponsor-carousel-indicator {
                width: 8px;
                height: 8px;
            }
        }

        @media (max-width: 480px) {
            .sponsor-carousel {
                aspect-ratio: 19/6;
            }
            
            .sponsor-name {
                font-size: 12px;
            }
            
            .sponsor-name-overlay {
                padding: 10px 8px 8px;
            }
            
            .sponsor-name-overlay p {
                font-size: 10px;
            }
        }

        /* Fallback image styling */
        .fallback-image {
            background: linear-gradient(135deg, #1f2937, #374151);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            text-align: center;
            height: 100%;
            border-radius: inherit;
        }

        /* Ensure aspect ratio works in older browsers */
        @supports not (aspect-ratio: 19/6) {
            .sponsor-carousel {
                height: 0;
                padding-bottom: 31.58%; /* 6/19 * 100% = 31.58% */
            }
            .sponsor-carousel-inner {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    
    <!-- Header / Navigation -->
    <header class="sticky top-0 z-50 bg-gradient-to-b from-gray-900 to-gray-800 shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                <img src="LOGO LAYAR ISOLA.webp" style="width: 150px;">
                </div>
                <a href="search.php" class="p-2 hover:bg-gray-700 rounded-full transition">
                    <i class="fas fa-search text-xl"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section with Sponsor Carousel -->
    <section class="py-4 bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="sponsor-carousel">
                <div class="sponsor-carousel-inner">
                    <?php foreach ($carouselSlides as $index => $slide): ?>
                        <?php if (!empty($slide['url']) && $slide['url'] != '#'): ?>
                            <a href="<?php echo htmlspecialchars($slide['url']); ?>" target="_blank" class="sponsor-link sponsor-carousel-item relative" data-index="<?php echo $index; ?>">
                        <?php else: ?>
                            <div class="sponsor-carousel-item relative" data-index="<?php echo $index; ?>">
                        <?php endif; ?>
                        
                            <img 
                                src="<?php echo htmlspecialchars($slide['image']); ?>" 
                                alt="<?php echo htmlspecialchars($slide['name']); ?>" 
                                class="sponsor-carousel-image"
                                onerror="this.classList.add('fallback-image'); this.outerHTML = '<div class=\'sponsor-carousel-image fallback-image\'>' + this.alt + '</div>'">
                            
                            <!-- Sponsor Name Overlay -->
                            <div class="sponsor-name-overlay">
                              
                                <?php if (!empty($slide['url']) && $slide['url'] != '#'): ?>
                                    <p class="text-gray-300 text-sm mt-1">Klik untuk mengunjungi website</p>
                                <?php endif; ?>
                            </div>
                        
                        <?php if (!empty($slide['url']) && $slide['url'] != '#'): ?>
                            </a>
                        <?php else: ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Carousel Controls -->
                <button class="sponsor-carousel-control prev">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="sponsor-carousel-control next">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <!-- Carousel Indicators -->
                <div class="sponsor-carousel-indicators">
                    <?php foreach ($carouselSlides as $index => $slide): ?>
                        <div class="sponsor-carousel-indicator <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Now Showing Section -->
<section class="py-8 bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold flex items-center">
                <i class="fas fa-fire text-red-500 mr-3"></i>Sedang Tayang
            </h3>
        </div>

        <?php 
        // Reset films pointer untuk digunakan kembali
        if ($films && $films->num_rows > 0): ?>
            <div class="films-grid">
                <?php while ($film = $films->fetch_assoc()): ?>
                    <?php
                    // Cek apakah ini double feature dan ambil data film kedua
                    $secondFilm = null;
                    if ($film['is_double_feature'] == 1 && $film['second_film_id']) {
                        $secondFilm = getFilmById($film['second_film_id']);
                    }
                    
                    $coverPath = !empty($film['cover_image']) 
                        ? 'assets/images/films/' . $film['cover_image'] 
                        : 'assets/images/no-image.jpg';
                        
                    $secondCoverPath = $secondFilm && !empty($secondFilm['cover_image']) 
                        ? 'assets/images/films/' . $secondFilm['cover_image'] 
                        : 'assets/images/no-image.jpg';
                    ?>
                    
                    <a href="film-detail.php?id=<?php echo $film['id']; ?>" class="film-card bg-gray-800 rounded-xl overflow-hidden shadow-xl">
                        <div class="relative p-4">
                            <?php if ($film['is_double_feature'] == 1 && $secondFilm): ?>
                                <!-- Double Feature Layout -->
                                <div class="double-feature-container flex items-center justify-center gap-3 mb-3">
                                    <div class="text-center flex-1">
                                        <img src="<?php echo htmlspecialchars($coverPath); ?>" 
                                             alt="<?php echo htmlspecialchars($film['title']); ?>" 
                                             class="w-full rounded-lg shadow-lg film-cover"
                                             style="aspect-ratio: 2/3; object-fit: cover;"
                                             onerror="this.src='assets/images/no-image.jpg'">
                                        <p class="mt-2 text-xs font-semibold text-gray-300 line-clamp-2 leading-tight">
                                            <?php echo htmlspecialchars($film['title']); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="plus-sign text-yellow-400 font-bold text-lg">+</div>
                                    
                                    <div class="text-center flex-1">
                                        <img src="<?php echo htmlspecialchars($secondCoverPath); ?>" 
                                             alt="<?php echo htmlspecialchars($secondFilm['title']); ?>" 
                                             class="w-full rounded-lg shadow-lg film-cover"
                                             style="aspect-ratio: 2/3; object-fit: cover;"
                                             onerror="this.src='assets/images/no-image.jpg'">
                                        <p class="mt-2 text-xs font-semibold text-gray-300 line-clamp-2 leading-tight">
                                            <?php echo htmlspecialchars($secondFilm['title']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Double Feature Badge -->
                                <div class="absolute top-3 right-3 bg-yellow-600 text-white text-xs font-bold px-2 py-1 rounded flex items-center">
                                    <i class="fas fa-film mr-1 text-xs"></i>
                                    <span>DOUBLE</span>
                                </div>
                                
                            <?php else: ?>
                                <!-- Single Film Layout -->
                                <img src="<?php echo htmlspecialchars($coverPath); ?>" 
                                     alt="<?php echo htmlspecialchars($film['title']); ?>" 
                                     class="film-cover rounded-lg"
                                     onerror="this.src='assets/images/no-image.jpg'">
                                
                                <!-- Rating Badge -->
                                <?php if (!empty($film['rating'])): ?>
                                <div class="absolute top-3 right-3 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($film['rating']); ?>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="film-content p-4 pt-0">
                            <div class="film-info">
                                <h4 class="font-bold text-lg mb-2 film-title" title="<?php 
                                    if ($film['is_double_feature'] == 1 && $secondFilm) {
                                        echo htmlspecialchars($film['title'] . ' & ' . $secondFilm['title']);
                                    } else {
                                        echo htmlspecialchars($film['title']);
                                    }
                                ?>">
                                    <?php 
                                    if ($film['is_double_feature'] == 1 && $secondFilm) {
                                        echo htmlspecialchars($film['title'] . ' & ' . $secondFilm['title']);
                                    } else {
                                        echo htmlspecialchars($film['title']);
                                    }
                                    ?>
                                </h4>
                                
                                <?php if (!empty($film['duration'])): ?>
                                <div class="flex items-center text-sm text-gray-400 mb-2">
                                    <i class="fas fa-clock mr-2"></i>
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
                                <?php endif; ?>
                                
                                <?php if (!empty($film['genre'])): ?>
                                <div class="flex items-center text-sm text-gray-400 mb-3">
                                    <i class="fas fa-theater-masks mr-2"></i>
                                    <span class="line-clamp-1">
                                        <?php 
                                        if ($film['is_double_feature'] == 1 && $secondFilm) {
                                            echo htmlspecialchars($film['genre'] . ' & ' . $secondFilm['genre']);
                                        } else {
                                            echo htmlspecialchars($film['genre']);
                                        }
                                        ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <button class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition mt-auto">
                                <i class="fas fa-ticket-alt mr-2"></i>Pesan Tiket
                            </button>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16 bg-gray-800 rounded-xl">
                <i class="fas fa-film text-6xl text-gray-600 mb-4"></i>
                <p class="text-xl text-gray-400">Belum ada film yang sedang tayang</p>
            </div>
        <?php endif; ?>
    </div>
</section>
    

    <!-- Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 py-8">
        <div class="container mx-auto px-4">
            <div class="text-center mb-4">
                <div class="flex items-center justify-center space-x-3 mb-4">
                    <img src="LOGO LAYAR ISOLA.webp" style="width: 150px;">
                </div>
              
            </div>

            <div class="flex justify-center space-x-6 mb-6">
                <a href="kebijakan-privasi.php" class="text-gray-400 hover:text-white text-sm transition">
                    Kebijakan Privasi
                </a>
                <a href="ketentuan-layanan.php" class="text-gray-400 hover:text-white text-sm transition">
                    Ketentuan Layanan
                </a>
            </div>
            <div class="flex flex-col md:flex-row justify-center items-center space-y-2 md:space-y-0 md:space-x-6 mb-6">
                <div class="flex items-center text-gray-400">
                    <i class="fas fa-envelope mr-2"></i>
                    <span><?php echo getSetting('contact_email', 'info@isolascreen.com'); ?></span>
                </div>
                <div class="flex items-center text-gray-400">
                    <i class="fas fa-phone mr-2"></i>
                    <span><?php echo getSetting('contact_phone', '021-12345678'); ?></span>
                </div>
            </div>
            
            <div class="text-center text-gray-500 text-sm border-t border-gray-800 pt-6">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $siteName; ?>. All rights reserved.</p>
                
            </div>
        </div>
    </footer>

    <script>
        // Sponsor Carousel Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const carouselInner = document.querySelector('.sponsor-carousel-inner');
            const carouselItems = document.querySelectorAll('.sponsor-carousel-item');
            const indicators = document.querySelectorAll('.sponsor-carousel-indicator');
            const prevBtn = document.querySelector('.sponsor-carousel-control.prev');
            const nextBtn = document.querySelector('.sponsor-carousel-control.next');
            
            if (!carouselInner || carouselItems.length === 0) return;
            
            let currentIndex = 0;
            const totalItems = carouselItems.length;
            
            // Function to update carousel position
            function updateCarousel() {
                carouselInner.style.transform = `translateX(-${currentIndex * 100}%)`;
                
                // Update active indicator
                indicators.forEach((indicator, index) => {
                    if (index === currentIndex) {
                        indicator.classList.add('active');
                    } else {
                        indicator.classList.remove('active');
                    }
                });
            }
            
            // Next slide
            function nextSlide() {
                currentIndex = (currentIndex + 1) % totalItems;
                updateCarousel();
            }
            
            // Previous slide
            function prevSlide() {
                currentIndex = (currentIndex - 1 + totalItems) % totalItems;
                updateCarousel();
            }
            
            // Auto advance slides
            let slideInterval = setInterval(nextSlide, 5000);
            
            // Pause auto-advance on hover
            const carousel = document.querySelector('.sponsor-carousel');
            if (carousel) {
                carousel.addEventListener('mouseenter', () => {
                    clearInterval(slideInterval);
                });
                
                carousel.addEventListener('mouseleave', () => {
                    slideInterval = setInterval(nextSlide, 5000);
                });
            }
            
            // Event listeners for controls
            if (prevBtn) prevBtn.addEventListener('click', prevSlide);
            if (nextBtn) nextBtn.addEventListener('click', nextSlide);
            
            // Event listeners for indicators
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    currentIndex = index;
                    updateCarousel();
                });
            });
            
            // Touch swipe functionality
            let startX = 0;
            let endX = 0;
            
            if (carousel) {
                carousel.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                });
                
                carousel.addEventListener('touchmove', (e) => {
                    endX = e.touches[0].clientX;
                });
                
                carousel.addEventListener('touchend', () => {
                    const diffX = startX - endX;
                    
                    // Minimum swipe distance
                    if (Math.abs(diffX) > 50) {
                        if (diffX > 0) {
                            // Swipe left - next slide
                            nextSlide();
                        } else {
                            // Swipe right - previous slide
                            prevSlide();
                        }
                    }
                });
            }

            // Set equal height for film cards
            function setEqualCardHeights() {
                const filmCards = document.querySelectorAll('.film-card');
                if (filmCards.length === 0) return;
                
                let maxHeight = 0;
                
                // Reset heights first
                filmCards.forEach(card => {
                    card.style.height = 'auto';
                });
                
                // Find the maximum height
                filmCards.forEach(card => {
                    const cardHeight = card.offsetHeight;
                    if (cardHeight > maxHeight) {
                        maxHeight = cardHeight;
                    }
                });
                
                // Set all cards to the maximum height
                filmCards.forEach(card => {
                    card.style.height = maxHeight + 'px';
                });
            }

            // Set equal heights on load and resize
            window.addEventListener('load', setEqualCardHeights);
            window.addEventListener('resize', setEqualCardHeights);
        });
    </script>
</body>
</html>