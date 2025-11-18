<?php
require_once '../includes/config.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Username atau password salah';
        }
    } else {
        $error = 'Username atau password salah';
    }
}

$siteName = getSetting('site_name', 'ISOLA SCREEN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo $siteName; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto">
            
            <div class="text-center mb-8">
                <i class="fas fa-user-shield text-6xl text-red-500 mb-4"></i>
                <h1 class="text-3xl font-bold mb-2"><?php echo $siteName; ?></h1>
                <p class="text-gray-400">Admin Panel Login</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-700 text-white px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="bg-gray-800 rounded-xl p-8 shadow-2xl">
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2">Username</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-4 text-gray-400"></i>
                        <input 
                            type="text" 
                            name="username" 
                            class="w-full pl-12 pr-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500"
                            placeholder="Masukkan username"
                            required
                            autofocus>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-4 text-gray-400"></i>
                        <input 
                            type="password" 
                            name="password" 
                            class="w-full pl-12 pr-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-red-500"
                            placeholder="Masukkan password"
                            required>
                    </div>
                </div>
                
                <button 
                    type="submit" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>

            <div class="text-center mt-6">
                <a href="../index.php" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Website
                </a>
            </div>

           
        </div>
    </div>

</body>
</html>
