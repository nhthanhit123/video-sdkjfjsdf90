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

// Check if user has already entered correct code
$hasAccess = isset($_SESSION['video_access'][$videoId]) && $_SESSION['video_access'][$videoId] === true;

// Handle code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $enteredCode = $_POST['code'];
    
    if ($enteredCode === $video['code']) {
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
        file_put_contents($videosFile, json_encode($videos, JSON_PRETTY_PRINT));
    } else {
        $error = 'Mã code không chính xác!';
    }
}

// Generate code if not exists
if (empty($video['code'])) {
    $video['code'] = generateRandomCode();
    foreach ($videos as &$v) {
        if ($v['id'] == $videoId) {
            $v['code'] = $video['code'];
            break;
        }
    }
    file_put_contents($videosFile, json_encode($videos, JSON_PRETTY_PRINT));
}

function generateRandomCode() {
    return rand(1000, 9999);
}

function createShortUrl($longUrl) {
    $apiUrl = 'https://yeumoney.com/QL_api.php';
    $token = 'f671ec129c1dca119827a9b28d859dc8c7eac69d954b97aa387f448f042b1a18';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'token' => $token,
        'format' => 'json',
        'url' => $longUrl
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($result && isset($result['status']) && $result['status'] === 'success' && isset($result['shortenedUrl'])) {
        return $result['shortenedUrl'];
    }
    
    return $longUrl; // Fallback to original URL if shortening fails
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
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-play-circle text-primary text-2xl"></i>
                    <h1 class="text-2xl font-bold text-gray-800">Video Platform</h1>
                </div>
                <nav class="flex items-center space-x-6">
                    <a href="index.php" class="text-gray-600 hover:text-primary transition">
                        <i class="fas fa-home mr-2"></i>Trang chủ
                    </a>
                    <a href="admin.php" class="text-gray-600 hover:text-primary transition">
                        <i class="fas fa-cog mr-2"></i>Admin
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="mb-6">
                <ol class="flex items-center space-x-2 text-sm">
                    <li><a href="index.php" class="text-gray-600 hover:text-primary">Trang chủ</a></li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-800"><?php echo htmlspecialchars($video['title']); ?></li>
                </ol>
            </nav>

            <?php if ($hasAccess): ?>
                <!-- Video Content -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="aspect-video bg-black">
                        <?php if ($video['type'] === 'video'): ?>
                            <video controls class="w-full h-full">
                                <source src="<?php echo htmlspecialchars($video['content']); ?>" type="video/mp4">
                                Trình duyệt của bạn không hỗ trợ video.
                            </video>
                        <?php elseif ($video['type'] === 'image'): ?>
                            <img src="<?php echo htmlspecialchars($video['content']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" class="w-full h-full object-contain">
                        <?php elseif ($video['type'] === 'clip'): ?>
                            <div class="w-full h-full p-8 bg-white">
                                <div class="prose max-w-none">
                                    <?php echo $video['content']; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-6">
                        <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($video['title']); ?></h1>
                        
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-4 text-sm text-gray-600">
                                <span><i class="fas fa-eye mr-1"></i><?php echo number_format($video['views']); ?> lượt xem</span>
                                <span><i class="fas fa-calendar mr-1"></i><?php echo date('d/m/Y', strtotime($video['created_at'])); ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="bg-primary text-white px-3 py-1 rounded-full text-sm">
                                    <?php 
                                    $labels = ['video' => 'Video', 'clip' => 'Clip', 'image' => 'Ảnh'];
                                    echo $labels[$video['type']] ?? 'Unknown';
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <h3 class="font-semibold text-gray-800 mb-2">Mô tả</h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($video['description'] ?? 'Không có mô tả'); ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Code Input Form -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="text-center mb-8">
                        <i class="fas fa-lock text-6xl text-primary mb-4"></i>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Yêu cầu mã code</h2>
                        <p class="text-gray-600">Vui lòng nhập mã code để xem nội dung này</p>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="max-w-md mx-auto">
                        <div class="mb-6">
                            <label for="code" class="block text-gray-700 font-medium mb-2">Mã code</label>
                            <div class="relative">
                                <input type="text" id="code" name="code" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="Nhập mã code 4 số">
                                <i class="fas fa-key absolute right-3 top-3.5 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                            <i class="fas fa-unlock mr-2"></i>Xác nhận
                        </button>
                    </form>
                    
                    <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">Lấy mã code:</h3>
                        <p class="text-gray-600 text-sm mb-3">Sử dụng link rút gọn bên dưới để lấy mã code:</p>
                        <?php
                        $shortUrl = getMultipleShortUrls('https://' . $_SERVER['HTTP_HOST'] . '/code.php', $video['code']);
                        ?>
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <a href="<?php echo htmlspecialchars($shortUrl); ?>" target="_blank" class="text-primary hover:underline break-all">
                                <?php echo htmlspecialchars($shortUrl); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2024 Video Platform. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>