<?php
require_once '../includes/config.php';
requireAdminLogin();

$success = '';
$error = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_sponsor'])) {
        // Add new sponsor
        $name = sanitize($_POST['name']);
        $url = sanitize($_POST['url']);
        $sortOrder = intval($_POST['sort_order']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadSponsorImage($_FILES['image']);
            if ($uploadResult['success']) {
                $imageName = $uploadResult['filename'];
                
                $stmt = $conn->prepare("INSERT INTO sponsors (name, image, url, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssii", $name, $imageName, $url, $sortOrder, $isActive);
                
                if ($stmt->execute()) {
                    $success = 'Sponsor berhasil ditambahkan';
                } else {
                    $error = 'Gagal menambahkan sponsor';
                }
            } else {
                $error = $uploadResult['message'];
            }
        } else {
            $error = 'Gambar sponsor harus diupload';
        }
    }
    elseif (isset($_POST['edit_sponsor'])) {
        // Edit sponsor
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $url = sanitize($_POST['url']);
        $sortOrder = intval($_POST['sort_order']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Upload new image
            $uploadResult = uploadSponsorImage($_FILES['image']);
            if ($uploadResult['success']) {
                $imageName = $uploadResult['filename'];
                
                // Delete old image
                $oldSponsor = getSponsorById($id);
                if ($oldSponsor && !empty($oldSponsor['image'])) {
                    deleteFile($oldSponsor['image'], __DIR__ . '/../assets/images/sponsors/');
                }
                
                $stmt = $conn->prepare("UPDATE sponsors SET name = ?, image = ?, url = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("sssiii", $name, $imageName, $url, $sortOrder, $isActive, $id);
            } else {
                $error = $uploadResult['message'];
            }
        } else {
            // Keep existing image
            $stmt = $conn->prepare("UPDATE sponsors SET name = ?, url = ?, sort_order = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssiii", $name, $url, $sortOrder, $isActive, $id);
        }
        
        if (!isset($error) || empty($error)) {
            if ($stmt->execute()) {
                $success = 'Sponsor berhasil diupdate';
            } else {
                $error = 'Gagal mengupdate sponsor';
            }
        }
    }
    elseif (isset($_POST['delete_sponsor'])) {
        // Delete sponsor
        $id = intval($_POST['id']);
        $sponsor = getSponsorById($id);
        
        if ($sponsor) {
            // Delete image file
            if (!empty($sponsor['image'])) {
                deleteFile($sponsor['image'], __DIR__ . '/../assets/images/sponsors/');
            }
            
            $stmt = $conn->prepare("DELETE FROM sponsors WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = 'Sponsor berhasil dihapus';
            } else {
                $error = 'Gagal menghapus sponsor';
            }
        }
    }
}

// Get all sponsors
$sponsors = $conn->query("SELECT * FROM sponsors ORDER BY sort_order ASC, created_at DESC");

$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Sponsor - Admin <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Kelola Sponsor</h1>
                <p class="text-gray-400">Kelola banner sponsor untuk carousel homepage</p>
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

            <!-- Add Sponsor Form -->
            <div class="bg-gray-800 rounded-xl p-6 mb-6">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-plus-circle text-red-500 mr-3"></i>Tambah Sponsor Baru
                </h2>
                
                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Nama Sponsor</label>
                        <input type="text" name="name" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">URL Sponsor</label>
                        <input type="url" name="url" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" placeholder="https://example.com">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Gambar Sponsor</label>
                        <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                        <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG, WebP. Max: 5MB</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Urutan</label>
                            <input type="number" name="sort_order" value="0" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" checked class="mr-2">
                            <label for="is_active" class="text-sm font-semibold">Aktif</label>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <button type="submit" name="add_sponsor" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition">
                            <i class="fas fa-save mr-2"></i>Tambah Sponsor
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sponsors List -->
            <div class="bg-gray-800 rounded-xl p-6">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-list text-red-500 mr-3"></i>Daftar Sponsor
                </h2>
                
                <?php if ($sponsors->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-700">
                                    <th class="text-left py-3 px-4">Gambar</th>
                                    <th class="text-left py-3 px-4">Nama</th>
                                    <th class="text-left py-3 px-4">URL</th>
                                    <th class="text-left py-3 px-4">Urutan</th>
                                    <th class="text-left py-3 px-4">Status</th>
                                    <th class="text-left py-3 px-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($sponsor = $sponsors->fetch_assoc()): ?>
                                <tr class="border-b border-gray-700 hover:bg-gray-750">
                                    <td class="py-3 px-4">
                                        <img src="../assets/images/sponsors/<?php echo htmlspecialchars($sponsor['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($sponsor['name']); ?>" 
                                             class="w-16 h-10 object-cover rounded"
                                             onerror="this.src='https://via.placeholder.com/64x40/1f2937/ffffff?text=SPONSOR'">
                                    </td>
                                    <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($sponsor['name']); ?></td>
                                    <td class="py-3 px-4">
                                        <?php if (!empty($sponsor['url'])): ?>
                                            <a href="<?php echo htmlspecialchars($sponsor['url']); ?>" target="_blank" class="text-blue-400 hover:text-blue-300">
                                                <?php echo htmlspecialchars($sponsor['url']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4"><?php echo $sponsor['sort_order']; ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $sponsor['is_active'] ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300'; ?>">
                                            <?php echo $sponsor['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <!-- Edit Button -->
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($sponsor)); ?>)" 
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            
                                            <!-- Delete Form -->
                                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus sponsor ini?')">
                                                <input type="hidden" name="id" value="<?php echo $sponsor['id']; ?>">
                                                <button type="submit" name="delete_sponsor" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                                    <i class="fas fa-trash mr-1"></i>Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-images text-4xl mb-3"></i>
                        <p>Belum ada sponsor</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-2xl">
            <h3 class="text-xl font-bold mb-4">Edit Sponsor</h3>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="edit_sponsor" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Nama Sponsor</label>
                        <input type="text" name="name" id="edit_name" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">URL Sponsor</label>
                        <input type="url" name="url" id="edit_url" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Gambar Sponsor</label>
                        <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                        <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak ingin mengubah gambar</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Urutan</label>
                            <input type="number" name="sort_order" id="edit_sort_order" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500">
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="edit_is_active" class="mr-2">
                            <label for="edit_is_active" class="text-sm font-semibold">Aktif</label>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        Batal
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Update Sponsor
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(sponsor) {
            document.getElementById('edit_id').value = sponsor.id;
            document.getElementById('edit_name').value = sponsor.name;
            document.getElementById('edit_url').value = sponsor.url || '';
            document.getElementById('edit_sort_order').value = sponsor.sort_order;
            document.getElementById('edit_is_active').checked = sponsor.is_active == 1;
            
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>