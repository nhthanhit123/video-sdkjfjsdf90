<?php
session_start();

// Check if user is admin (simple password protection)
$adminPassword = 'admin123'; // Change this in production
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
                $newVideo = [
                    'id' => time(),
                    'title' => $_POST['title'],
                    'description' => $_POST['description'] ?? '',
                    'content' => $_POST['content'],
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
                        $video['content'] = $_POST['content'];
                        $video['type'] = $_POST['type'];
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
        
        file_put_contents($videosFile, json_encode($videos, JSON_PRETTY_PRINT));
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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <?php if (!$isLoggedIn): ?>
        <!-- Login Screen -->
        <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary to-secondary">
            <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
                <div class="text-center mb-8">
                    <i class="fas fa-lock text-4xl text-primary mb-4"></i>
                    <h1 class="text-2xl font-bold text-gray-800">Admin Login</h1>
                    <p class="text-gray-600">Nhập mật khẩu để truy cập trang quản trị</p>
                </div>
                
                <?php if (isset($loginError)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $loginError; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-6">
                        <label for="password" class="block text-gray-700 font-medium mb-2">Mật khẩu</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="Nhập mật khẩu">
                            <i class="fas fa-key absolute right-3 top-3.5 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                        <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Admin Panel -->
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <i class="fas fa-cog text-primary text-2xl"></i>
                        <h1 class="text-2xl font-bold text-gray-800">Admin Panel</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="text-gray-600 hover:text-primary transition">
                            <i class="fas fa-home mr-2"></i>Trang chủ
                        </a>
                        <a href="admin.php?logout=1" class="text-red-600 hover:text-red-700 transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8">
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>Thao tác thành công!
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Add/Edit Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">
                            <i class="fas fa-plus-circle mr-2 text-primary"></i>
                            <span id="formTitle">Thêm Video Mới</span>
                        </h2>
                        
                        <form method="POST" id="videoForm">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="id" id="formId">
                            
                            <div class="mb-4">
                                <label for="title" class="block text-gray-700 font-medium mb-2">Tiêu đề</label>
                                <input type="text" id="title" name="title" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                            
                            <div class="mb-4">
                                <label for="description" class="block text-gray-700 font-medium mb-2">Mô tả</label>
                                <textarea id="description" name="description" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="type" class="block text-gray-700 font-medium mb-2">Loại nội dung</label>
                                <select id="type" name="type" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        onchange="toggleContentField()">
                                    <option value="">Chọn loại</option>
                                    <option value="video">Video</option>
                                    <option value="clip">Clip (nội dung text)</option>
                                    <option value="image">Ảnh</option>
                                    <option value="fake">Video hỏng (chuyển về trang chủ)</option>
                                </select>
                            </div>
                            
                            <div class="mb-6">
                                <label for="content" class="block text-gray-700 font-medium mb-2">
                                    <span id="contentLabel">Nội dung</span>
                                </label>
                                <div id="videoContent">
                                    <input type="url" id="content" name="content" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                           placeholder="Nhập link video/ảnh">
                                </div>
                                <div id="clipContent" class="hidden">
                                    <textarea id="content_editor" name="content" rows="10"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                                </div>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-lg hover:bg-primary/90 transition font-medium">
                                    <i class="fas fa-save mr-2"></i>Lưu
                                </button>
                                <button type="button" onclick="resetForm()" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 transition font-medium">
                                    <i class="fas fa-times mr-2"></i>Hủy
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Videos List -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">
                            <i class="fas fa-list mr-2 text-primary"></i>
                            Danh sách Video
                        </h2>
                        
                        <?php if (empty($videos)): ?>
                            <div class="text-center py-12">
                                <i class="fas fa-video-slash text-6xl text-gray-300 mb-4"></i>
                                <p class="text-xl text-gray-500 mb-2">Chưa có video nào</p>
                                <p class="text-gray-400">Hãy thêm video mới</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="text-left py-3 px-2">Tiêu đề</th>
                                            <th class="text-left py-3 px-2">Loại</th>
                                            <th class="text-left py-3 px-2">Views</th>
                                            <th class="text-left py-3 px-2">Ngày tạo</th>
                                            <th class="text-center py-3 px-2">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($videos as $video): ?>
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="py-3 px-2">
                                                    <div class="max-w-xs">
                                                        <p class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($video['title']); ?></p>
                                                        <p class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($video['id']); ?></p>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-2">
                                                    <span class="bg-<?php echo $video['type'] === 'video' ? 'blue' : ($video['type'] === 'clip' ? 'green' : ($video['type'] === 'image' ? 'yellow' : 'red')); ?>-100 text-<?php echo $video['type'] === 'video' ? 'blue' : ($video['type'] === 'clip' ? 'green' : ($video['type'] === 'image' ? 'yellow' : 'red')); ?>-800 px-2 py-1 rounded text-xs">
                                                        <?php 
                                                        $labels = ['video' => 'Video', 'clip' => 'Clip', 'image' => 'Ảnh', 'fake' => 'Hỏng'];
                                                        echo $labels[$video['type']] ?? 'Unknown';
                                                        ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-2">
                                                    <span class="text-gray-600"><?php echo number_format($video['views']); ?></span>
                                                </td>
                                                <td class="py-3 px-2">
                                                    <span class="text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($video['created_at'])); ?></span>
                                                </td>
                                                <td class="py-3 px-2">
                                                    <div class="flex justify-center space-x-2">
                                                        <button onclick="editVideo(<?php echo $video['id']; ?>)" 
                                                                class="text-blue-600 hover:text-blue-800 transition">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button onclick="deleteVideo(<?php echo $video['id']; ?>)" 
                                                                class="text-red-600 hover:text-red-800 transition">
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
        </main>

        <script>
            // Initialize CKEditor
            let editor;
            
            function toggleContentField() {
                const type = document.getElementById('type').value;
                const videoContent = document.getElementById('videoContent');
                const clipContent = document.getElementById('clipContent');
                const contentLabel = document.getElementById('contentLabel');
                const contentInput = document.getElementById('content');
                
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
                
                if (editor) {
                    editor.setData('');
                }
                
                toggleContentField();
            }
        </script>
    <?php endif; ?>
</body>
</html>