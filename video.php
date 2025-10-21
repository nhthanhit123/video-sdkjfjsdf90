<?php
session_start();

// Check if video ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$videoId = $_GET['id'];

// Read videos data
$videosFile = 'data/videos.json';
if (!file_exists($videosFile)) {
    header('Location: index.php');
    exit;
}

$videos = json_decode(file_get_contents($videosFile), true);
$video = null;

foreach ($videos as $v) {
    if ($v['id'] == $videoId) {
        $video = $v;
        break;
    }
}

if (!$video) {
    header('Location: index.php');
    exit;
}

// Handle fake video type
if ($video['type'] === 'fake') {
    header('Location: index.php');
    exit;
}

// Generate unique code for this user and video if not exists
$sessionKey = 'video_code_' . $videoId;
if (!isset($_SESSION[$sessionKey])) {
    $_SESSION[$sessionKey] = generateRandomCode();
}

$userCode = $_SESSION[$sessionKey];

// Check if user has already entered correct code
$hasAccess = isset($_SESSION['video_access'][$videoId]) && $_SESSION['video_access'][$videoId] === true;

// Handle code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $enteredCode = $_POST['code'];
    
    if ($enteredCode == $userCode) {
        $_SESSION['video_access'][$videoId] = true;
        $hasAccess = true;
        
        // Update view count
        $video['views']++;
        foreach ($videos as &$v) {
            if ($v['id'] == $videoId) {
                $v['views'] = $video['views'];
                break;
            }
        }
        file_put_contents($videosFile, json_encode($videos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } else {
        $error = 'Mã code không chính xác!';
    }
}

function generateRandomCode() {
    return rand(1000, 9999);
}

function createShortUrl($longUrl) {
    $apiUrl = 'https://yeumoney.com/QL_api.php';
    $token  = 'f671ec129c1dca119827a9b28d859dc8c7eac69d954b97aa387f448f042b1a18';

    // Ghép URL GET
    $requestUrl = $apiUrl . '?' . http_build_query([
        'token'  => $token,
        'format' => 'json',
        'url'    => $longUrl
    ]);

    // Khởi tạo cURL GET
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $requestUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($result && isset($result['status']) && $result['status'] === 'success' && isset($result['shortenedUrl'])) {
        return $result['shortenedUrl'];
    }
    return $longUrl; // fallback nếu lỗi
}

function getMultipleShortUrls($baseUrl, $code) {
    $url1 = createShortUrl($baseUrl . '?code=' . $code);
    $url2 = createShortUrl($url1);
    $url3 = createShortUrl($url2);
    return $url3;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - Video Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            min-height: 100vh;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .sidebar {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .nav-item {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 4px 0;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 16px;
            background: #000;
        }
        
        .video-container video,
        .video-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .lock-icon {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .code-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .code-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
        }
        
        .short-link {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .short-link:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            space-x: 8px;
        }
        
        .breadcrumb a {
            color: #9ca3af;
            transition: color 0.3s ease;
        }
        
        .breadcrumb a:hover {
            color: #ff6b6b;
        }
    </style>
</head>
<body class="text-white">
    <!-- Sidebar -->
    <div class="fixed left-0 top-0 h-full w-64 sidebar z-50">
        <div class="p-6">
            <div class="flex items-center space-x-3 mb-8">
                <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-pink-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-play text-white"></i>
                </div>
                <h1 class="text-xl font-bold gradient-text">Video Platform</h1>
            </div>
            
            <nav class="space-y-2">
                <a href="index.php" class="nav-item flex items-center space-x-3 px-4 py-3 text-gray-300 hover:text-white">
                    <i class="fas fa-home w-5"></i>
                    <span>Trang chủ</span>
                </a>
                <a href="admin.php" class="nav-item flex items-center space-x-3 px-4 py-3 text-gray-300 hover:text-white">
                    <i class="fas fa-cog w-5"></i>
                    <span>Quản trị</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64">
        <!-- Header -->
        <header class="sticky top-0 z-40 glass-card border-b border-gray-800">
            <div class="container mx-auto px-6 py-4">
                <div class="breadcrumb">
                    <a href="index.php" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-home mr-2"></i>Trang chủ
                    </a>
                    <span class="text-gray-600 mx-2">/</span>
                    <span class="text-white"><?php echo htmlspecialchars($video['title']); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-6 py-8">
            <div class="max-w-6xl mx-auto">
                <?php if ($hasAccess): ?>
                    <!-- Video Content -->
                    <div class="glass-card overflow-hidden fade-in">
                        <div class="video-container">
                            <?php if ($video['type'] == 'video'): ?>
                                <video controls class="w-full h-full">
                                    <source src="<?php echo htmlspecialchars($video['content']); ?>" type="video/mp4">
                                    Trình duyệt của bạn không hỗ trợ video.
                                </video>
                            <?php elseif ($video['type'] === 'image'): ?>
                                <img src="<?php echo htmlspecialchars($video['content']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>">
                            <?php elseif ($video['type'] === 'clip'): ?>
                                <div class="w-full h-full p-8 bg-white text-black">
                                    <div class="prose max-w-none">
                                        <?php echo $video['content']; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-8">
                            <h1 class="text-3xl font-bold text-white mb-6"><?php echo htmlspecialchars($video['title']); ?></h1>
                            
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center space-x-6 text-sm text-gray-400">
                                    <span class="flex items-center">
                                        <i class="fas fa-eye mr-2"></i>
                                        <?php echo number_format($video['views']); ?> lượt xem
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-calendar mr-2"></i>
                                        <?php echo date('d/m/Y', strtotime($video['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="px-4 py-2 bg-gradient-to-r from-red-500 to-pink-500 rounded-full text-sm font-semibold">
                                        <?php 
                                        $labels = ['video' => 'Video', 'clip' => 'Clip', 'image' => 'Ảnh'];
                                        echo $labels[$video['type']] ?? 'Unknown';
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-800 pt-6">
                                <h3 class="text-xl font-semibold text-white mb-4">Mô tả</h3>
                                <p class="text-gray-300 leading-relaxed"><?php echo htmlspecialchars($video['description'] ?? 'Không có mô tả'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Code Input Form -->
                    <div class="glass-card p-12 fade-in">
                        <div class="text-center mb-12">
                            <div class="w-24 h-24 bg-gradient-to-br from-red-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-6 lock-icon">
                                <i class="fas fa-lock text-4xl text-white"></i>
                            </div>
                            <h2 class="text-3xl font-bold text-white mb-4">Yêu cầu mã code</h2>
                            <p class="text-gray-400 text-lg">Vui lòng nhập mã code để xem nội dung này</p>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <div class="glass-card border border-red-500 text-red-400 px-6 py-4 rounded-lg mb-8">
                                <i class="fas fa-exclamation-circle mr-3"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="max-w-md mx-auto mb-12">
                            <div class="mb-6">
                                <label for="code" class="block text-gray-300 font-medium mb-3">Mã code</label>
                                <div class="relative">
                                    <input type="text" id="code" name="code" required
                                           class="w-full px-6 py-4 code-input rounded-lg text-white placeholder-gray-500 focus:outline-none"
                                           placeholder="Nhập mã code 4 số">
                                    <i class="fas fa-key absolute right-4 top-4 text-gray-500"></i>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full submit-btn text-white py-4 rounded-lg font-semibold">
                                <i class="fas fa-unlock mr-3"></i>Xác nhận
                            </button>
                        </form>
                        
                        <div class="glass-card p-8">
                            <h3 class="text-xl font-semibold text-white mb-4">Lấy mã code:</h3>
                            <p class="text-gray-400 mb-6">Sử dụng link rút gọn bên dưới để lấy mã code của bạn:</p>
                            <?php
                            $shortUrl = getMultipleShortUrls('https://' . $_SERVER['HTTP_HOST'] . '/code.php', $userCode);
                            ?>
                            <div class="short-link p-4 rounded-lg">
                                <a href="<?php echo htmlspecialchars($shortUrl); ?>" target="_blank" class="text-cyan-400 hover:text-cyan-300 break-all">
                                    <?php echo htmlspecialchars($shortUrl); ?>
                                </a>
                            </div>
                            <p class="text-sm text-gray-500 mt-4">
                                <i class="fas fa-info-circle mr-2"></i>
                                Mỗi người dùng có một mã code riêng cho video này
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>