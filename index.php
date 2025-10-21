<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Platform</title>
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
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Tất cả video</h2>
            <p class="text-gray-600">Khám phá các video mới nhất</p>
        </div>

        <!-- Video Grid -->
        <div id="videoGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Videos will be loaded here -->
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="text-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl text-primary mb-4"></i>
            <p class="text-gray-600">Đang tải video...</p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="text-center py-12 hidden">
            <i class="fas fa-video-slash text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-500 mb-2">Chưa có video nào</p>
            <p class="text-gray-400">Hãy thêm video mới trong trang admin</p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2024 Video Platform. All rights reserved.</p>
        </div>
    </footer>

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
                
                videoGrid.innerHTML = videos.map(video => `
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow cursor-pointer" onclick="viewVideo(${video.id})">
                        <div class="relative">
                            ${video.type === 'image' ? 
                                `<img src="${video.content}" alt="${video.title}" class="w-full h-48 object-cover">` :
                                video.type === 'video' ?
                                `<div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-play-circle text-4xl text-primary"></i>
                                </div>` :
                                video.type === 'clip' ?
                                `<div class="w-full h-48 bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
                                    <i class="fas fa-newspaper text-4xl text-white"></i>
                                </div>` :
                                `<div class="w-full h-48 bg-gray-300 flex items-center justify-center">
                                    <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
                                </div>`
                            }
                            <div class="absolute top-2 right-2 bg-black bg-opacity-60 text-white px-2 py-1 rounded text-sm">
                                ${getVideoTypeLabel(video.type)}
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">${video.title}</h3>
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <span><i class="fas fa-eye mr-1"></i>${formatNumber(video.views)}</span>
                                <span><i class="fas fa-calendar mr-1"></i>${formatDate(video.created_at)}</span>
                            </div>
                        </div>
                    </div>
                `).join('');
                
            } catch (error) {
                console.error('Error loading videos:', error);
                document.getElementById('loadingState').innerHTML = `
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                    <p class="text-gray-600">Có lỗi xảy ra khi tải video</p>
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