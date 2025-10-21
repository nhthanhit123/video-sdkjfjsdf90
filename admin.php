<?php
session_start();

// Check if user is admin (simple password protection)
$adminPassword = 'nhthanhit12345'; // Change this in production
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
        $isLoggedIn = true;
    } else {
        $loginError = 'Mật khẩu không chính xác!';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    $isLoggedIn = false;
    header('Location: admin.php');
    exit;
}

// Function to generate thumbnail from video
function generateVideoThumbnail($videoPath, $thumbnailPath, $time = 5) {
    if (!file_exists($videoPath)) {
        return false;
    }
    
    // Try to use ffmpeg first
    $ffmpegPath = '/usr/bin/ffmpeg'; // Adjust path as needed
    if (file_exists($ffmpegPath)) {
        $command = "$ffmpegPath -i $videoPath -ss $time -vframes 1 -y $thumbnailPath 2>&1";
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($thumbnailPath)) {
            return true;
        }
    }
    
    // Fallback: create a placeholder thumbnail
    $placeholder = imagecreatetruecolor(320, 180);
    $bgColor = imagecolorallocate($placeholder, 37, 37, 37);
    imagefill($placeholder, 0, 0, $bgColor);
    
    // Add play icon
    $playColor = imagecolorallocate($placeholder, 255, 107, 53);
    $centerX = 160;
    $centerY = 90;
    $size = 30;
    
    // Draw play triangle
    $points = [
        $centerX - $size/2, $centerY - $size,
        $centerX - $size/2, $centerY + $size,
        $centerX + $size/2, $centerY
    ];
    imagefilledpolygon($placeholder, $points, 3, $playColor);
    
    imagepng($placeholder, $thumbnailPath);
    imagedestroy($placeholder);
    
    return true;
}

// Function to handle file upload
function handleFileUpload($file, $type) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }
    
    $allowedTypes = [
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ];
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedTypes[$type])) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Check file size (max 100MB for video, 10MB for image)
    $maxSize = $type === 'video' ? 100 * 1024 * 1024 : 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $fileExtension;
    $uploadDir = "uploads/{$type}s/";
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Generate thumbnail for video
        $thumbnailPath = '';
        if ($type === 'video') {
            $thumbnailDir = 'uploads/thumbnails/';
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            $thumbnailFilename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
            $thumbnailPath = $thumbnailDir . $thumbnailFilename;
            generateVideoThumbnail($uploadPath, $thumbnailPath);
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $uploadPath,
            'thumbnail' => $thumbnailPath
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

// Handle CRUD operations
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $videosFile = 'data/videos.json';
    $videos = [];
    
    if (file_exists($videosFile)) {
        $videos = json_decode(file_get_contents($videosFile), true);
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $content = $_POST['content'] ?? '';
                $thumbnail = '';
                
                // Handle file upload
                if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) {
                    $type = $_POST['type'];
                    $uploadResult = handleFileUpload($_FILES['upload_file'], $type);
                    
                    if ($uploadResult['success']) {
                        $content = $uploadResult['path'];
                        $thumbnail = $uploadResult['thumbnail'];
                    } else {
                        $uploadError = $uploadResult['error'];
                    }
                }
                
                $newVideo = [
                    'id' => time(),
                    'title' => $_POST['title'],
                    'description' => $_POST['description'] ?? '',
                    'content' => $content,
                    'thumbnail' => $thumbnail,
                    'type' => $_POST['type'],
                    'views' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $videos[] = $newVideo;
                break;
                
            case 'edit':
                foreach ($videos as &$video) {
                    if ($video['id'] == $_POST['id']) {
                        $video['title'] = $_POST['title'];
                        $video['description'] = $_POST['description'] ?? '';
                        $video['type'] = $_POST['type'];
                        
                        // Handle file upload
                        if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) {
                            $type = $_POST['type'];
                            $uploadResult = handleFileUpload($_FILES['upload_file'], $type);
                            
                            if ($uploadResult['success']) {
                                $video['content'] = $uploadResult['path'];
                                if (!empty($uploadResult['thumbnail'])) {
                                    $video['thumbnail'] = $uploadResult['thumbnail'];
                                }
                            } else {
                                $uploadError = $uploadResult['error'];
                            }
                        } else {
                            // Keep existing content if no new file uploaded
                            $video['content'] = $_POST['content'] ?? $video['content'];
                        }
                        break;
                    }
                }
                break;
                
            case 'delete':
                $videos = array_filter($videos, function($video) {
                    return $video['id'] != $_POST['id'];
                });
                $videos = array_values($videos);
                break;
        }
        
        // Ensure data directory exists
        if (!is_dir('data')) {
            mkdir('data', 0755, true);
        }
        
        file_put_contents($videosFile, json_encode($videos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: admin.php?success=1');
        exit;
    }
}

// Read videos for display
$videos = [];
if (file_exists('data/videos.json')) {
    $videos = json_decode(file_get_contents('data/videos.json'), true);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Video Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
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

        /* Sidebar Animation */
        .sidebar {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar:hover {
            transform: translateX(5px);
        }

        /* Card Hover Effects */
        .card-hover {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card-hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.1), transparent);
            transition: left 0.5s;
        }

        .card-hover:hover::before {
            left: 100%;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
        }

        /* Button Effects */
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #ff8559);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-primary:hover::after {
            width: 300px;
            height: 300px;
        }

        /* Input Styling */
        .input-field {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .input-field:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
            outline: none;
        }

        /* Table Styling */
        .table-row {
            transition: all 0.2s ease;
        }

        .table-row:hover {
            background: rgba(255, 107, 53, 0.05);
            transform: scale(1.01);
        }

        /* Upload Area */
        .upload-area {
            border: 2px dashed var(--border);
            transition: all 0.3s ease;
        }

        .upload-area:hover {
            border-color: var(--accent);
            background: rgba(255, 107, 53, 0.05);
        }

        .upload-area.dragover {
            border-color: var(--accent);
            background: rgba(255, 107, 53, 0.1);
        }

        /* File Preview */
        .file-preview {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .file-preview img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 107, 53, 0.3);
            border-radius: 50%;
            border-top-color: var(--accent);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Success Animation */
        .success-animation {
            animation: slideInRight 0.5s ease-out;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
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
    <?php if (!$isLoggedIn): ?>
        <!-- Login Screen -->
        <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-black to-gray-900">
            <div class="absolute inset-0 bg-black opacity-50"></div>
            <div class="relative z-10 glass p-8 rounded-2xl shadow-2xl w-full max-w-md border border-gray-700">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-orange-500 to-red-600 rounded-full mb-4">
                        <i class="fas fa-play text-white text-3xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2">Video Platform</h1>
                    <p class="text-gray-400">Admin Control Panel</p>
                </div>
                
                <?php if (isset($loginError)): ?>
                    <div class="bg-red-500/20 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $loginError; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="password" class="block text-gray-300 font-medium mb-2">Mật khẩu Admin</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                   class="input-field w-full px-4 py-3 rounded-lg pl-12"
                                   placeholder="Nhập mật khẩu quản trị">
                            <i class="fas fa-shield-alt absolute left-4 top-3.5 text-gray-500"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full py-3 rounded-lg font-semibold text-white relative">
                        <span class="relative z-10 flex items-center justify-center">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Đăng Nhập
                        </span>
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Admin Dashboard -->
        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            <aside class="sidebar w-64 bg-gray-900 border-r border-gray-800 flex flex-col">
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
                            <a href="admin.php" class="flex items-center space-x-3 p-3 rounded-lg bg-orange-500/20 text-orange-400 border border-orange-500/30">
                                <i class="fas fa-film"></i>
                                <span>Quản lý Video</span>
                            </a>
                        </li>
                        <li>
                            <a href="file-manager.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-800 text-gray-300 transition">
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
                <!-- Top Header -->
                <header class="bg-gray-900 border-b border-gray-800 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">Quản Lý Video</h1>
                            <p class="text-gray-400 text-sm">Thêm, sửa, xóa và quản lý nội dung video</p>
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
                        <div class="success-animation bg-green-500/20 border border-green-500/50 text-green-400 px-4 py-3 rounded-lg mb-6 flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            Thao tác thành công!
                        </div>
                    <?php endif; ?>

                    <?php if (isset($uploadError)): ?>
                        <div class="bg-red-500/20 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg mb-6 flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo $uploadError; ?>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <!-- Add/Edit Form -->
                        <div class="xl:col-span-1">
                            <div class="glass rounded-xl p-6 card-hover">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-white flex items-center">
                                        <i class="fas fa-plus-circle text-orange-500 mr-2"></i>
                                        <span id="formTitle">Thêm Video Mới</span>
                                    </h2>
                                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                </div>
                                
                                <form method="POST" id="videoForm" class="space-y-4" enctype="multipart/form-data">
                                    <input type="hidden" name="action" id="formAction" value="add">
                                    <input type="hidden" name="id" id="formId">
                                    
                                    <div>
                                        <label for="title" class="block text-gray-300 font-medium mb-2">Tiêu đề Video</label>
                                        <input type="text" id="title" name="title" required
                                               class="input-field w-full px-4 py-3 rounded-lg"
                                               placeholder="Nhập tiêu đề video">
                                    </div>
                                    
                                    <div>
                                        <label for="description" class="block text-gray-300 font-medium mb-2">Mô tả</label>
                                        <textarea id="description" name="description" rows="3"
                                                  class="input-field w-full px-4 py-3 rounded-lg resize-none"
                                                  placeholder="Mô tả nội dung video"></textarea>
                                    </div>
                                    
                                    <div>
                                        <label for="type" class="block text-gray-300 font-medium mb-2">Loại Nội Dung</label>
                                        <select id="type" name="type" required
                                                class="input-field w-full px-4 py-3 rounded-lg"
                                                onchange="toggleContentField()">
                                            <option value="">-- Chọn loại --</option>
                                            <option value="video">🎥 Video</option>
                                            <option value="clip">📝 Clip (Nội dung text)</option>
                                            <option value="image">🖼️ Ảnh</option>
                                            <option value="fake">❌ Video Hỏng</option>
                                        </select>
                                    </div>
                                    
                                    <!-- File Upload Area -->
                                    <div id="uploadArea" class="hidden">
                                        <label class="block text-gray-300 font-medium mb-2">Upload File</label>
                                        <div class="upload-area rounded-lg p-6 text-center cursor-pointer" onclick="document.getElementById('upload_file').click()">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-500 mb-2"></i>
                                            <p class="text-gray-400 text-sm">Click để upload hoặc kéo thả file vào đây</p>
                                            <p class="text-gray-500 text-xs mt-1">Max: Video 100MB, Ảnh 10MB</p>
                                            <input type="file" id="upload_file" name="upload_file" class="hidden" onchange="handleFileSelect(this)">
                                        </div>
                                        <div id="filePreview" class="mt-3 hidden"></div>
                                    </div>
                                    
                                    <!-- URL Input Area -->
                                    <div id="urlArea">
                                        <label for="content" class="block text-gray-300 font-medium mb-2">
                                            <span id="contentLabel">Nội dung</span>
                                        </label>
                                        <div id="videoContent">
                                            <input type="text" id="content" name="content" 
                                                   class="input-field w-full px-4 py-3 rounded-lg"
                                                   placeholder="Nhập link video/ảnh">
                                        </div>
                                        <div id="clipContent" class="hidden">
                                            <textarea id="content_editor" name="content" rows="8"
                                                      class="input-field w-full px-4 py-3 rounded-lg resize-none"
                                                      placeholder="Nhập nội dung text/html"></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-3 pt-4">
                                        <button type="submit" class="btn-primary flex-1 py-3 rounded-lg font-semibold text-white relative">
                                            <span class="relative z-10 flex items-center justify-center">
                                                <i class="fas fa-save mr-2"></i>
                                                Lưu Video
                                            </span>
                                        </button>
                                        <button type="button" onclick="resetForm()" class="flex-1 bg-gray-700 hover:bg-gray-600 py-3 rounded-lg font-semibold text-gray-300 transition">
                                            <i class="fas fa-times mr-2"></i>
                                            Hủy
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Videos List -->
                        <div class="xl:col-span-2">
                            <div class="glass rounded-xl p-6 card-hover">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold text-white flex items-center">
                                        <i class="fas fa-list text-orange-500 mr-2"></i>
                                        Danh Sách Video
                                    </h2>
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-gray-700 text-gray-300 px-3 py-1 rounded-full text-sm">
                                            <?php echo count($videos); ?> video
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if (empty($videos)): ?>
                                    <div class="text-center py-12">
                                        <i class="fas fa-video-slash text-6xl text-gray-600 mb-4"></i>
                                        <p class="text-xl text-gray-400 mb-2">Chưa có video nào</p>
                                        <p class="text-gray-500">Hãy thêm video mới để bắt đầu</p>
                                    </div>
                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="w-full">
                                            <thead>
                                                <tr class="border-b border-gray-700">
                                                    <th class="text-left py-3 px-4 text-gray-300 font-medium">Tiêu đề</th>
                                                    <th class="text-left py-3 px-4 text-gray-300 font-medium">Loại</th>
                                                    <th class="text-left py-3 px-4 text-gray-300 font-medium">Views</th>
                                                    <th class="text-left py-3 px-4 text-gray-300 font-medium">Ngày tạo</th>
                                                    <th class="text-center py-3 px-4 text-gray-300 font-medium">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($videos as $video): ?>
                                                    <tr class="table-row border-b border-gray-800">
                                                        <td class="py-4 px-4">
                                                            <div class="flex items-center space-x-3">
                                                                <?php if (!empty($video['thumbnail'])): ?>
                                                                    <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="Thumbnail" class="w-12 h-8 object-cover rounded">
                                                                <?php elseif ($video['type'] === 'image'): ?>
                                                                    <img src="<?php echo htmlspecialchars($video['content']); ?>" alt="Preview" class="w-12 h-8 object-cover rounded">
                                                                <?php else: ?>
                                                                    <div class="w-12 h-8 bg-gray-700 rounded flex items-center justify-center">
                                                                        <i class="fas fa-<?php echo $video['type'] === 'video' ? 'play' : ($video['type'] === 'clip' ? 'newspaper' : 'image'); ?> text-gray-500 text-sm"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <p class="font-medium text-white truncate max-w-xs"><?php echo htmlspecialchars($video['title']); ?></p>
                                                                    <p class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($video['id']); ?></p>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="py-4 px-4">
                                                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                                                <?php echo $video['type'] === 'video' ? 'bg-blue-500/20 text-blue-400' : 
                                                                          ($video['type'] === 'clip' ? 'bg-green-500/20 text-green-400' : 
                                                                          ($video['type'] === 'image' ? 'bg-yellow-500/20 text-yellow-400' : 
                                                                          'bg-red-500/20 text-red-400')); ?>">
                                                                <?php 
                                                                $labels = ['video' => 'Video', 'clip' => 'Clip', 'image' => 'Ảnh', 'fake' => 'Hỏng'];
                                                                echo $labels[$video['type']] ?? 'Unknown';
                                                                ?>
                                                            </span>
                                                        </td>
                                                        <td class="py-4 px-4">
                                                            <span class="text-gray-300"><?php echo number_format($video['views']); ?></span>
                                                        </td>
                                                        <td class="py-4 px-4">
                                                            <span class="text-gray-300 text-sm"><?php echo date('d/m/Y', strtotime($video['created_at'])); ?></span>
                                                        </td>
                                                        <td class="py-4 px-4">
                                                            <div class="flex justify-center space-x-2">
                                                                <button onclick="editVideo(<?php echo $video['id']; ?>)" 
                                                                        class="text-blue-400 hover:text-blue-300 transition p-2">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button onclick="deleteVideo(<?php echo $video['id']; ?>)" 
                                                                        class="text-red-400 hover:text-red-300 transition p-2">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <script>
            // Initialize CKEditor
            let editor;
            
            function toggleContentField() {
                const type = document.getElementById('type').value;
                const uploadArea = document.getElementById('uploadArea');
                const urlArea = document.getElementById('urlArea');
                const videoContent = document.getElementById('videoContent');
                const clipContent = document.getElementById('clipContent');
                const contentLabel = document.getElementById('contentLabel');
                const contentInput = document.getElementById('content');
                
                // Show/hide upload area based on type
                if (type === 'video' || type === 'image') {
                    uploadArea.classList.remove('hidden');
                    urlArea.classList.remove('hidden');
                } else {
                    uploadArea.classList.add('hidden');
                    urlArea.classList.remove('hidden');
                }
                
                if (type === 'clip') {
                    videoContent.classList.add('hidden');
                    clipContent.classList.remove('hidden');
                    contentLabel.textContent = 'Nội dung (HTML)';
                    
                    if (!editor) {
                        editor = CKEDITOR.replace('content_editor');
                    }
                } else {
                    videoContent.classList.remove('hidden');
                    clipContent.classList.add('hidden');
                    
                    if (type === 'video') {
                        contentLabel.textContent = 'Link Video';
                        contentInput.placeholder = 'Nhập link video (MP4, WebM, etc.)';
                    } else if (type === 'image') {
                        contentLabel.textContent = 'Link Ảnh';
                        contentInput.placeholder = 'Nhập link ảnh (JPG, PNG, GIF, etc.)';
                    } else if (type === 'fake') {
                        contentLabel.textContent = 'Link (sẽ không hiển thị)';
                        contentInput.placeholder = 'Nhập bất kỳ link nào (video sẽ bị hỏng)';
                    } else {
                        contentLabel.textContent = 'Nội dung';
                        contentInput.placeholder = 'Nhập link';
                    }
                }
            }
            
            function handleFileSelect(input) {
                const file = input.files[0];
                const preview = document.getElementById('filePreview');
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        let previewHtml = '';
                        
                        if (file.type.startsWith('video/')) {
                            previewHtml = `
                                <div class="file-preview">
                                    <video controls class="w-full h-48 object-cover">
                                        <source src="${e.target.result}" type="${file.type}">
                                    </video>
                                    <div class="p-3">
                                        <p class="text-sm text-gray-300 truncate">${file.name}</p>
                                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                                    </div>
                                </div>
                            `;
                        } else if (file.type.startsWith('image/')) {
                            previewHtml = `
                                <div class="file-preview">
                                    <img src="${e.target.result}" alt="Preview" class="w-full h-48 object-cover">
                                    <div class="p-3">
                                        <p class="text-sm text-gray-300 truncate">${file.name}</p>
                                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        preview.innerHTML = previewHtml;
                        preview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            }
            
            // Drag and drop functionality
            const uploadArea = document.querySelector('.upload-area');
            
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    document.getElementById('upload_file').files = files;
                    handleFileSelect(document.getElementById('upload_file'));
                }
            });
            
            function editVideo(id) {
                const videos = <?php echo json_encode($videos); ?>;
                const video = videos.find(v => v.id === id);
                
                if (video) {
                    document.getElementById('formTitle').textContent = 'Chỉnh sửa Video';
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('formId').value = video.id;
                    document.getElementById('title').value = video.title;
                    document.getElementById('description').value = video.description || '';
                    document.getElementById('type').value = video.type;
                    
                    toggleContentField();
                    
                    if (video.type === 'clip' && editor) {
                        editor.setData(video.content);
                    } else {
                        document.getElementById('content').value = video.content;
                    }
                    
                    // Scroll to form
                    document.getElementById('videoForm').scrollIntoView({ behavior: 'smooth' });
                }
            }
            
            function deleteVideo(id) {
                if (confirm('Bạn có chắc chắn muốn xóa video này?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            
            function resetForm() {
                document.getElementById('videoForm').reset();
                document.getElementById('formTitle').textContent = 'Thêm Video Mới';
                document.getElementById('formAction').value = 'add';
                document.getElementById('formId').value = '';
                document.getElementById('filePreview').classList.add('hidden');
                document.getElementById('upload_file').value = '';
                
                if (editor) {
                    editor.setData('');
                }
                
                toggleContentField();
            }
        </script>
    <?php endif; ?>
</body>
</html>