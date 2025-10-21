<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Platform - Motchill Style</title>
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
        
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .video-thumbnail {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
        }
        
        .video-thumbnail::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.7) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .glass-card:hover .video-thumbnail::before {
            opacity: 1;
        }
        
        .play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .glass-card:hover .play-overlay {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1.1);
        }
        
        .type-badge {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        .skeleton {
            background: linear-gradient(90deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.05) 100%);
            background-size: 200% 100%;
            animation: skeleton 1.5s ease-in-out infinite;
        }
        
        @keyframes skeleton {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
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
                <a href="/" class="nav-item active flex items-center space-x-3 px-4 py-3 text-white">
                    <i class="fas fa-home w-5"></i>
                    <span>Trang chủ</span>
                </a>
                <a href="/admin.php" class="nav-item flex items-center space-x-3 px-4 py-3 text-gray-300 hover:text-white">
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
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-white mb-2">Tất cả video</h2>
                        <p class="text-gray-400">Khám phá các video mới nhất</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button class="p-2 rounded-lg glass-card hover:bg-white/10 transition">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="p-2 rounded-lg glass-card hover:bg-white/10 transition">
                            <i class="fas fa-bell"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Video Grid -->
        <main class="container mx-auto px-6 py-8">
            <div id="videoGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Videos will be loaded here -->
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="text-center py-12">
                <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 border-4 border-gray-700 border-t-red-500 rounded-full animate-spin"></div>
                    <p class="text-gray-400">Đang tải video...</p>
                </div>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="text-center py-12 hidden">
                <div class="flex flex-col items-center space-y-4">
                    <i class="fas fa-video-slash text-6xl text-gray-600"></i>
                    <p class="text-xl text-gray-400">Chưa có video nào</p>
                    <p class="text-gray-500">Hãy thêm video mới trong trang admin</p>
                    <a href="admin.php" class="px-6 py-3 bg-gradient-to-r from-red-500 to-pink-500 rounded-lg font-semibold hover:shadow-lg transition">
                        <i class="fas fa-plus mr-2"></i>Thêm video
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Load videos
        async function loadVideos() {
            try {
                const response = await fetch('api/videos.php');
                const videos = await response.json();
                
                const loadingState = document.getElementById('loadingState');
                const emptyState = document.getElementById('emptyState');
                const videoGrid = document.getElementById('videoGrid');
                
                loadingState.classList.add('hidden');
                
                if (videos.length === 0) {
                    emptyState.classList.remove('hidden');
                    return;
                }
                
                videoGrid.innerHTML = videos.map((video, index) => `
                    <div class="glass-card p-4 cursor-pointer fade-in" style="animation-delay: ${index * 0.1}s" onclick="viewVideo(${video.id})">
                        <div class="video-thumbnail mb-4">
                            ${getThumbnailHtml(video)}
                            <div class="absolute top-2 right-2 type-badge">
                                ${getVideoTypeLabel(video.type)}
                            </div>
                        </div>
                        <div class="space-y-2">
                            <h3 class="font-semibold text-white line-clamp-2">${video.title}</h3>
                            <div class="flex items-center justify-between text-sm text-gray-400">
                                <span><i class="fas fa-eye mr-1"></i>${formatNumber(video.views)}</span>
                                <span><i class="fas fa-calendar mr-1"></i>${formatDate(video.created_at)}</span>
                            </div>
                        </div>
                    </div>
                `).join('');
                
            } catch (error) {
                console.error('Error loading videos:', error);
                document.getElementById('loadingState').innerHTML = `
                    <div class="flex flex-col items-center space-y-4">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
                        <p class="text-gray-400">Có lỗi xảy ra khi tải video</p>
                        <button onclick="loadVideos()" class="px-4 py-2 bg-red-500 rounded-lg hover:bg-red-600 transition">
                            <i class="fas fa-redo mr-2"></i>Thử lại
                        </button>
                    </div>
                `;
            }
        }
        
        function getThumbnailHtml(video) {
            // Use thumbnail if available
            if (video.thumbnail) {
                return `
                    <img src="${video.thumbnail}" alt="${video.title}" class="w-full h-48 object-cover">
                    <div class="play-overlay">
                        <i class="fas fa-play text-gray-900 text-xl ml-1"></i>
                    </div>
                `;
            }
            
            // Fallback to type-based display
            if (video.type === 'image') {
                return `<img src="${video.content}" alt="${video.title}" class="w-full h-48 object-cover">`;
            } else if (video.type === 'video') {
                return `
                    <div class="w-full h-48 bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
                        <div class="play-overlay">
                            <i class="fas fa-play text-gray-900 text-xl ml-1"></i>
                        </div>
                        <i class="fas fa-play-circle text-4xl text-gray-600"></i>
                    </div>
                `;
            } else if (video.type === 'clip') {
                return `
                    <div class="w-full h-48 bg-gradient-to-br from-red-600 to-pink-600 flex items-center justify-center">
                        <i class="fas fa-newspaper text-4xl text-white"></i>
                    </div>
                `;
            } else {
                return `
                    <div class="w-full h-48 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
                    </div>
                `;
            }
        }
        
        function getVideoTypeLabel(type) {
            const labels = {
                'video': 'Video',
                'clip': 'Clip',
                'image': 'Ảnh',
                'fake': 'Hỏng'
            };
            return labels[type] || 'Unknown';
        }
        
        function formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }
        
        function viewVideo(id) {
            window.location.href = `video.php?id=${id}`;
        }
        
        // Load videos on page load
        document.addEventListener('DOMContentLoaded', loadVideos);
    </script>
</body>
</html>