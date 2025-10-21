<?php
session_start();

// Check if user is admin
$adminPassword = 'nhthanhit12345';
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$isLoggedIn) {
    header('Location: admin.php');
    exit;
}

// Function to get all files from directory
function getFilesFromDir($dir, $type) {
    $files = [];
    if (is_dir($dir)) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                $filePath = $dir . '/' . $item;
                if (is_file($filePath)) {
                    $files[] = [
                        'name' => $item,
                        'path' => $filePath,
                        'size' => filesize($filePath),
                        'modified' => filemtime($filePath),
                        'type' => $type
                    ];
                }
            }
        }
    }
    return $files;
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $filePath = $_POST['file_path'] ?? '';
    
    // Security check - ensure file is in uploads directory
    if (strpos($filePath, 'uploads/') === 0 && file_exists($filePath)) {
        unlink($filePath);
        header('Location: file-manager.php?success=1');
        exit;
    }
}

// Get all files
$videoFiles = getFilesFromDir('uploads/videos', 'video');
$imageFiles = getFilesFromDir('uploads/images', 'image');
$thumbnailFiles = getFilesFromDir('uploads/thumbnails', 'thumbnail');

// Combine and sort all files
$allFiles = array_merge($videoFiles, $imageFiles, $thumbnailFiles);
usort($allFiles, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

// Filter by type if specified
$filterType = $_GET['filter'] ?? 'all';
if ($filterType !== 'all') {
    $allFiles = array_filter($allFiles, function($file) use ($filterType) {
        return $file['type'] === $filterType;
    });
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager - Video Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0f0f0f;
            --bg-secondary: #1a1a1a;
            --bg-card: #252525;
            --accent: #ff6b35;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --border: #333333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--accent);
            border-radius: 4px;
        }

        /* Glass Morphism Effect */
        .glass {
            background: rgba(37, 37, 37, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Card Hover Effects */
        .card-hover {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.2);
        }

        /* File Item Styling */
        .file-item {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .file-item:hover {
            background: rgba(255, 107, 53, 0.05);
            border-left-color: var(--accent);
        }

        /* Filter Button */
        .filter-btn {
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: var(--accent);
            color: white;
        }

        /* File Type Icons */
        .file-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .file-icon.video {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }

        .file-icon.image {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .file-icon.thumbnail {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }

        /* Preview Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        /* Background Pattern */
        .bg-pattern {
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(255, 107, 53, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 107, 53, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 107, 53, 0.05) 0%, transparent 50%);
        }
    </style>
</head>
<body class="bg-pattern">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col">
            <div class="p-6 border-b border-gray-800">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-play text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Video Platform</h2>
                        <p class="text-xs text-gray-400">Admin Panel</p>
                    </div>
                </div>
            </div>
            
            <nav class="flex-1 p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="admin.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-800 text-gray-300 transition">
                            <i class="fas fa-film"></i>
                            <span>Quản lý Video</span>
                        </a>
                    </li>
                    <li>
                        <a href="file-manager.php" class="flex items-center space-x-3 p-3 rounded-lg bg-orange-500/20 text-orange-400 border border-orange-500/30">
                            <i class="fas fa-folder"></i>
                            <span>Quản lý File</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-800 text-gray-300 transition">
                            <i class="fas fa-home"></i>
                            <span>Trang Chủ</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-800 text-gray-300 transition">
                            <i class="fas fa-chart-bar"></i>
                            <span>Thống Kê</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-800 text-gray-300 transition">
                            <i class="fas fa-cog"></i>
                            <span>Cài Đặt</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="p-4 border-t border-gray-800">
                <a href="admin.php?logout=1" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-red-500/20 text-red-400 transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng Xuất</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-gray-900 border-b border-gray-800 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">Quản Lý File</h1>
                        <p class="text-gray-400 text-sm">Xem và quản lý tất cả các file đã upload</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm text-gray-400">Admin</p>
                            <p class="text-xs text-gray-500">Online</p>
                        </div>
                        <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full"></div>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-500/20 border border-green-500/50 text-green-400 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        Xóa file thành công!
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="glass rounded-lg p-4 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm">Tổng số file</p>
                                <p class="text-2xl font-bold text-white"><?php echo count($allFiles); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file text-blue-500"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="glass rounded-lg p-4 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm">Video</p>
                                <p class="text-2xl font-bold text-white"><?php echo count($videoFiles); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-video text-blue-500"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="glass rounded-lg p-4 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm">Ảnh</p>
                                <p class="text-2xl font-bold text-white"><?php echo count($imageFiles); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-image text-green-500"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="glass rounded-lg p-4 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm">Thumbnail</p>
                                <p class="text-2xl font-bold text-white"><?php echo count($thumbnailFiles); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-photo-video text-yellow-500"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="glass rounded-lg p-4 mb-6">
                    <div class="flex flex-wrap gap-2">
                        <a href="?filter=all" class="filter-btn px-4 py-2 rounded-lg <?php echo $filterType === 'all' ? 'active' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                            <i class="fas fa-list mr-2"></i>Tất cả
                        </a>
                        <a href="?filter=video" class="filter-btn px-4 py-2 rounded-lg <?php echo $filterType === 'video' ? 'active' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                            <i class="fas fa-video mr-2"></i>Video
                        </a>
                        <a href="?filter=image" class="filter-btn px-4 py-2 rounded-lg <?php echo $filterType === 'image' ? 'active' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                            <i class="fas fa-image mr-2"></i>Ảnh
                        </a>
                        <a href="?filter=thumbnail" class="filter-btn px-4 py-2 rounded-lg <?php echo $filterType === 'thumbnail' ? 'active' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                            <i class="fas fa-photo-video mr-2"></i>Thumbnail
                        </a>
                    </div>
                </div>

                <!-- Files List -->
                <div class="glass rounded-lg p-6">
                    <h2 class="text-xl font-bold text-white mb-6">Danh sách File</h2>
                    
                    <?php if (empty($allFiles)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-folder-open text-6xl text-gray-600 mb-4"></i>
                            <p class="text-xl text-gray-400 mb-2">Không có file nào</p>
                            <p class="text-gray-500">Chưa có file nào được upload</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($allFiles as $file): ?>
                                <div class="file-item bg-gray-800/50 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="file-icon <?php echo $file['type']; ?>">
                                                <i class="fas fa-<?php echo $file['type'] === 'video' ? 'video' : ($file['type'] === 'image' ? 'image' : 'photo-video'); ?>"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-white"><?php echo htmlspecialchars($file['name']); ?></p>
                                                <div class="flex items-center space-x-4 text-sm text-gray-400">
                                                    <span><i class="fas fa-folder mr-1"></i><?php echo ucfirst($file['type']); ?></span>
                                                    <span><i class="fas fa-database mr-1"></i><?php echo number_format($file['size'] / 1024 / 1024, 2); ?> MB</span>
                                                    <span><i class="fas fa-clock mr-1"></i><?php echo date('d/m/Y H:i', $file['modified']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <?php if ($file['type'] === 'image' || $file['type'] === 'thumbnail'): ?>
                                                <button onclick="previewFile('<?php echo htmlspecialchars($file['path']); ?>', '<?php echo htmlspecialchars($file['type']); ?>')" 
                                                        class="p-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?php echo htmlspecialchars($file['path']); ?>" download
                                               class="p-2 bg-green-500/20 text-green-400 rounded-lg hover:bg-green-500/30 transition">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa file này?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($file['path']); ?>">
                                                <button type="submit" class="p-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closePreview()">&times;</span>
            <div id="previewContainer"></div>
        </div>
    </div>

    <script>
        function previewFile(filePath, fileType) {
            const modal = document.getElementById('previewModal');
            const container = document.getElementById('previewContainer');
            
            if (fileType === 'image' || fileType === 'thumbnail') {
                container.innerHTML = `<img src="${filePath}" alt="Preview" class="max-w-full max-h-full object-contain">`;
            }
            
            modal.classList.add('active');
        }
        
        function closePreview() {
            document.getElementById('previewModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePreview();
            }
        });
    </script>
</body>
</html>