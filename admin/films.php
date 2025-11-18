<?php
require_once '../includes/config.php';
requireAdminLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $duration = intval($_POST['duration']);
        $genre = sanitize($_POST['genre']);
        $rating = sanitize($_POST['rating']);
        $releaseDate = sanitize($_POST['release_date']);
        $director = sanitize($_POST['director']);
        $cast = sanitize($_POST['cast']);
        $trailerUrl = sanitize($_POST['trailer_url']);
        $status = sanitize($_POST['status']);
        $isDoubleFeature = isset($_POST['is_double_feature']) ? 1 : 0;
        $secondFilmId = $isDoubleFeature && !empty($_POST['second_film_id']) ? intval($_POST['second_film_id']) : null;
        
        $coverImage = '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['cover_image']);
            if ($upload['success']) {
                $coverImage = $upload['filename'];
                if ($action === 'edit' && $id > 0) {
                    $oldFilm = getFilmById($id);
                    if ($oldFilm && !empty($oldFilm['cover_image'])) {
                        deleteFile($oldFilm['cover_image']);
                    }
                }
            }
        }
        
        $secondCoverImage = '';
        if ($isDoubleFeature && isset($_FILES['second_cover_image']) && $_FILES['second_cover_image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['second_cover_image']);
            if ($upload['success']) {
                $secondCoverImage = $upload['filename'];
                if ($action === 'edit' && $id > 0) {
                    $oldFilm = getFilmById($id);
                    if ($oldFilm && !empty($oldFilm['second_cover_image'])) {
                        deleteFile($oldFilm['second_cover_image']);
                    }
                }
            }
        }
        
        $trailerFile = '';
        if (isset($_FILES['trailer_file']) && $_FILES['trailer_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/videos/trailers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = uniqid() . '_' . time() . '.' . pathinfo($_FILES['trailer_file']['name'], PATHINFO_EXTENSION);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['trailer_file']['tmp_name'], $targetPath)) {
                $trailerFile = $fileName;
                if ($action === 'edit' && $id > 0) {
                    $oldFilm = getFilmById($id);
                    if ($oldFilm && !empty($oldFilm['trailer_file'])) {
                        $oldPath = $uploadDir . $oldFilm['trailer_file'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                }
            }
        }
        
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO films (title, description, duration, genre, rating, release_date, cover_image, director, cast, trailer_url, trailer_file, is_double_feature, second_film_id, second_cover_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissssssssiiss", $title, $description, $duration, $genre, $rating, $releaseDate, $coverImage, $director, $cast, $trailerUrl, $trailerFile, $isDoubleFeature, $secondFilmId, $secondCoverImage, $status);
            
            if ($stmt->execute()) {
                $success = 'Film berhasil ditambahkan';
            } else {
                $error = 'Gagal menambahkan film';
            }
        } else {
            $updateFields = [];
            $updateTypes = '';
            $updateValues = [];
            
            $updateFields[] = "title = ?"; $updateTypes .= 's'; $updateValues[] = &$title;
            $updateFields[] = "description = ?"; $updateTypes .= 's'; $updateValues[] = &$description;
            $updateFields[] = "duration = ?"; $updateTypes .= 'i'; $updateValues[] = &$duration;
            $updateFields[] = "genre = ?"; $updateTypes .= 's'; $updateValues[] = &$genre;
            $updateFields[] = "rating = ?"; $updateTypes .= 's'; $updateValues[] = &$rating;
            $updateFields[] = "release_date = ?"; $updateTypes .= 's'; $updateValues[] = &$releaseDate;
            $updateFields[] = "director = ?"; $updateTypes .= 's'; $updateValues[] = &$director;
            $updateFields[] = "cast = ?"; $updateTypes .= 's'; $updateValues[] = &$cast;
            $updateFields[] = "trailer_url = ?"; $updateTypes .= 's'; $updateValues[] = &$trailerUrl;
            $updateFields[] = "is_double_feature = ?"; $updateTypes .= 'i'; $updateValues[] = &$isDoubleFeature;
            $updateFields[] = "status = ?"; $updateTypes .= 's'; $updateValues[] = &$status;
            
            if ($coverImage) {
                $updateFields[] = "cover_image = ?"; $updateTypes .= 's'; $updateValues[] = &$coverImage;
            }
            if ($trailerFile) {
                $updateFields[] = "trailer_file = ?"; $updateTypes .= 's'; $updateValues[] = &$trailerFile;
            }
            if ($secondCoverImage) {
                $updateFields[] = "second_cover_image = ?"; $updateTypes .= 's'; $updateValues[] = &$secondCoverImage;
            }
            if ($secondFilmId !== null) {
                $updateFields[] = "second_film_id = ?"; $updateTypes .= 'i'; $updateValues[] = &$secondFilmId;
            } else {
                $updateFields[] = "second_film_id = NULL";
            }
            
            $updateTypes .= 'i';
            $updateValues[] = &$id;
            
            $sql = "UPDATE films SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($updateTypes, ...$updateValues);
            
            if ($stmt->execute()) {
                $success = 'Film berhasil diupdate';
            } else {
                $error = 'Gagal mengupdate film';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $film = getFilmById($id);
        
        if ($film) {
            if (!empty($film['cover_image'])) {
                deleteFile($film['cover_image']);
            }
            
            $stmt = $conn->prepare("DELETE FROM films WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = 'Film berhasil dihapus';
            } else {
                $error = 'Gagal menghapus film';
            }
        }
    }
}

$films = $conn->query("SELECT * FROM films ORDER BY created_at DESC");
$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Film - Admin <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Manajemen Film</h1>
                    <p class="text-gray-400">Kelola film yang ditayangkan</p>
                </div>
                <button onclick="openAddModal()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>Tambah Film
                </button>
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

            <div class="bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Cover</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Judul</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Genre</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Durasi</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Rating</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if ($films && $films->num_rows > 0): ?>
                                <?php while ($film = $films->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-700 transition">
                                        <td class="px-6 py-4">
                                            <?php 
                                            $coverPath = !empty($film['cover_image']) 
                                                ? '../assets/images/films/' . $film['cover_image'] 
                                                : 'https://via.placeholder.com/60x90/1f2937/ffffff?text=No+Image'; 
                                            ?>
                                            <img src="<?php echo $coverPath; ?>" alt="<?php echo htmlspecialchars($film['title']); ?>" class="w-12 h-18 object-cover rounded" onerror="this.src='https://via.placeholder.com/60x90/1f2937/ffffff?text=No+Image'">
                                        </td>
                                        <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($film['title']); ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($film['genre']); ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo $film['duration']; ?> menit</td>
                                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($film['rating']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="<?php echo $film['status'] === 'active' ? 'bg-green-600' : 'bg-gray-600'; ?> text-white text-xs px-3 py-1 rounded-full">
                                                <?php echo ucfirst($film['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button onclick='editFilm(<?php echo json_encode($film); ?>)' class="text-blue-400 hover:text-blue-300 mr-3" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteFilm(<?php echo $film['id']; ?>, '<?php echo htmlspecialchars($film['title']); ?>')" class="text-red-400 hover:text-red-300" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                        Belum ada film
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="filmModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="p-6 border-b border-gray-700 flex justify-between items-center sticky top-0 bg-gray-800 z-10">
                <h2 id="modalTitle" class="text-2xl font-bold">Tambah Film</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="filmId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Judul Film *</label>
                        <input type="text" name="title" id="title" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Genre *</label>
                        <input type="text" name="genre" id="genre" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="Action, Drama, dll" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Deskripsi *</label>
                    <textarea name="description" id="description" rows="3" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Durasi (menit) *</label>
                        <input type="number" name="duration" id="duration" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Rating *</label>
                        <select name="rating" id="rating" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                            <option value="G">G</option>
                            <option value="PG">PG</option>
                            <option value="PG-13">PG-13</option>
                            <option value="R">R</option>
                            <option value="NC-17">NC-17</option>
                            <option value="SU">SU</option>
                            <option value="13+">13+</option>
                            <option value="17+">17+</option>
                            <option value="21+">21+</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Tanggal Rilis *</label>
                        <input type="date" name="release_date" id="release_date" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Sutradara</label>
                        <input type="text" name="director" id="director" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">URL Trailer (YouTube/Vimeo)</label>
                        <input type="url" name="trailer_url" id="trailer_url" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="https://youtube.com/...">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Pemeran Utama</label>
                    <input type="text" name="cast" id="cast" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="Pisahkan dengan koma">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Upload File Trailer (MP4)</label>
                    <input type="file" name="trailer_file" id="trailer_file" accept="video/*" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                    <p class="text-xs text-gray-400 mt-1">Format: MP4, WebM. Max: 50MB</p>
                </div>
                
                <div class="mb-4 bg-gray-700 p-4 rounded-lg">
                    <label class="flex items-center mb-3 cursor-pointer">
                        <input type="checkbox" name="is_double_feature" id="is_double_feature" class="mr-3 w-5 h-5" onchange="toggleDoubleFeature()">
                        <span class="font-semibold">Double Feature (2 Film dalam 1 Tayangan)</span>
                    </label>
                    
                    <div id="doubleFeatureFields" class="hidden space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Pilih Film Kedua</label>
                            <select name="second_film_id" id="second_film_id" class="w-full px-4 py-2 bg-gray-600 border border-gray-500 rounded-lg focus:outline-none focus:border-red-500">
                                <option value="">-- Pilih Film --</option>
                                <?php
                                $allFilms = $conn->query("SELECT id, title FROM films ORDER BY title ASC");
                                while ($f = $allFilms->fetch_assoc()) {
                                    echo '<option value="' . $f['id'] . '">' . htmlspecialchars($f['title']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold mb-2">Cover Image Film Kedua</label>
                            <input type="file" name="second_cover_image" id="second_cover_image" accept="image/*" class="w-full px-4 py-2 bg-gray-600 border border-gray-500 rounded-lg focus:outline-none focus:border-red-500">
                            <p class="text-xs text-gray-400 mt-1">Cover untuk film kedua dalam double feature</p>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Cover Image</label>
                        <input type="file" name="cover_image" id="cover_image" accept="image/*" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                        <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG, WEBP. Max: 5MB</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Status *</label>
                        <select name="status" id="status" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script>
        function openAddModal() {
            document.getElementById('filmModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Tambah Film';
            document.getElementById('formAction').value = 'add';
            document.querySelector('form').reset();
            document.getElementById('filmId').value = '';
            document.getElementById('doubleFeatureFields').classList.add('hidden');
        }

        function editFilm(film) {
            document.getElementById('filmModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Edit Film';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('filmId').value = film.id;
            document.getElementById('title').value = film.title;
            document.getElementById('description').value = film.description;
            document.getElementById('duration').value = film.duration;
            document.getElementById('genre').value = film.genre;
            document.getElementById('rating').value = film.rating;
            document.getElementById('release_date').value = film.release_date;
            document.getElementById('director').value = film.director || '';
            document.getElementById('cast').value = film.cast || '';
            document.getElementById('trailer_url').value = film.trailer_url || '';
            document.getElementById('status').value = film.status;
            
            if (film.is_double_feature == 1) {
                document.getElementById('is_double_feature').checked = true;
                document.getElementById('doubleFeatureFields').classList.remove('hidden');
                if (film.second_film_id) {
                    document.getElementById('second_film_id').value = film.second_film_id;
                }
            } else {
                document.getElementById('is_double_feature').checked = false;
                document.getElementById('doubleFeatureFields').classList.add('hidden');
            }
        }

        function closeModal() {
            document.getElementById('filmModal').classList.add('hidden');
        }

        function deleteFilm(id, title) {
            if (confirm('Apakah Anda yakin ingin menghapus film "' + title + '"?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        function toggleDoubleFeature() {
            const checkbox = document.getElementById('is_double_feature');
            const fields = document.getElementById('doubleFeatureFields');
            if (checkbox.checked) {
                fields.classList.remove('hidden');
            } else {
                fields.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
