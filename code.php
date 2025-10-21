<?php
session_start();

// Get code from URL parameter
$code = $_GET['code'] ?? '';

// Validate code format (4 digits)
if (!preg_match('/^\d{4}$/', $code)) {
    die('Mã code không hợp lệ!');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lấy Mã Code - Video Platform</title>
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
        
        .code-display {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 0.5rem;
            padding: 2rem;
            border-radius: 16px;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes glow {
            from { box-shadow: 0 0 20px rgba(255, 107, 107, 0.5); }
            to { box-shadow: 0 0 30px rgba(78, 205, 196, 0.5); }
        }
        
        .copy-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .copy-btn.copied {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .step-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .step-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(4px);
        }
        
        .countdown {
            font-size: 1.2rem;
            color: #ff6b6b;
            font-weight: 600;
        }
    </style>
</head>
<body class="text-white">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-2xl w-full">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="w-20 h-20 bg-gradient-to-br from-red-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-key text-3xl text-white"></i>
                </div>
                <h1 class="text-4xl font-bold gradient-text mb-4">Mã Code Của Bạn</h1>
                <p class="text-gray-400 text-lg">Sao chép mã code bên dưới để xem video</p>
            </div>

            <!-- Code Display -->
            <div class="glass-card p-8 mb-8">
                <div class="text-center">
                    <div class="code-display text-white mb-6" id="codeDisplay">
                        <?php echo htmlspecialchars($code); ?>
                    </div>
                    <button onclick="copyCode()" class="copy-btn px-8 py-3 rounded-lg text-white font-semibold" id="copyBtn">
                        <i class="fas fa-copy mr-3"></i>
                        <span id="copyBtnText">Sao chép mã code</span>
                    </button>
                </div>
            </div>

            <!-- Instructions -->
            <div class="glass-card p-8 mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">Hướng dẫn sử dụng:</h2>
                <div class="space-y-4">
                    <div class="step-item p-4 rounded-lg">
                        <div class="flex items-start space-x-4">
                            <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-pink-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-sm">1</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white mb-2">Sao chép mã code</h3>
                                <p class="text-gray-400">Nhấn vào nút "Sao chép mã code" để sao chép mã 4 số</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step-item p-4 rounded-lg">
                        <div class="flex items-start space-x-4">
                            <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-pink-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-sm">2</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white mb-2">Quay lại trang video</h3>
                                <p class="text-gray-400">Quay lại trang video và dán mã code vào ô nhập</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step-item p-4 rounded-lg">
                        <div class="flex items-start space-x-4">
                            <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-pink-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-sm">3</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white mb-2">Xem video</h3>
                                <p class="text-gray-400">Sau khi nhập đúng mã code, bạn có thể xem nội dung video</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Important Notice -->
            <div class="glass-card p-6 border-l-4 border-red-500">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-exclamation-triangle text-red-500 mt-1"></i>
                    <div>
                        <h3 class="font-semibold text-white mb-2">Lưu ý quan trọng:</h3>
                        <p class="text-gray-400 text-sm">
                            Mã code này chỉ dành cho bạn và video hiện tại. Mỗi người dùng sẽ có mã code riêng. 
                            Vui lòng không chia sẻ mã code này với người khác.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Auto Redirect -->
            <div class="text-center mt-8">
                <p class="text-gray-500 text-sm mb-4">Trang sẽ tự động đóng sau <span class="countdown" id="countdown">30</span> giây</p>
                <a href="javascript:window.close()" class="text-gray-400 hover:text-white transition text-sm">
                    <i class="fas fa-times mr-2"></i>Đóng trang
                </a>
            </div>
        </div>
    </div>

    <script>
        // Copy code function
        function copyCode() {
            const code = '<?php echo htmlspecialchars($code); ?>';
            const copyBtn = document.getElementById('copyBtn');
            const copyBtnText = document.getElementById('copyBtnText');
            
            navigator.clipboard.writeText(code).then(function() {
                copyBtn.classList.add('copied');
                copyBtnText.innerHTML = '<i class="fas fa-check mr-3"></i>Đã sao chép!';
                
                setTimeout(() => {
                    copyBtn.classList.remove('copied');
                    copyBtnText.innerHTML = '<i class="fas fa-copy mr-3"></i>Sao chép mã code';
                }, 3000);
            }).catch(function(err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = code;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                copyBtn.classList.add('copied');
                copyBtnText.innerHTML = '<i class="fas fa-check mr-3"></i>Đã sao chép!';
                
                setTimeout(() => {
                    copyBtn.classList.remove('copied');
                    copyBtnText.innerHTML = '<i class="fas fa-copy mr-3"></i>Sao chép mã code';
                }, 3000);
            });
        }
        
        // Countdown timer
        let seconds = 30;
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdown);
                window.close();
            }
        }, 1000);
        
        // Auto copy on page load
        window.addEventListener('load', () => {
            setTimeout(() => {
                copyCode();
            }, 1000);
        });
        
        // Prevent back button
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function () {
            window.history.pushState(null, null, window.location.href);
        };
    </script>
</body>
</html>