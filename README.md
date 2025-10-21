# Video Platform

Một nền tảng video đơn giản được xây dựng bằng PHP + HTML + Tailwind CSS.

## Tính năng

### Trang chủ (index.php)
- Hiển thị tất cả video dưới dạng lưới
- Hiển thị thông tin: tiêu đề, lượt xem, ngày đăng
- Responsive design cho mobile và desktop
- Phân loại video theo loại (Video, Clip, Ảnh, Hỏng)

### Xem video (video.php)
- Yêu cầu nhập mã code để xem nội dung
- **Mỗi người dùng có một mã code riêng cho mỗi video**
- Mã code được lưu trong session của người dùng
- Tích hợp API rút gọn link yeumoney.com (rút gọn 3 lần)
- Lưu session để không cần nhập lại code
- Hỗ trợ nhiều loại nội dung:
  - Video: Phát video trực tiếp
  - Clip: Hiển thị nội dung HTML (CKEditor)
  - Ảnh: Hiển thị ảnh
  - Fake: Chuyển về trang chủ

### Trang Admin (admin.php)
- Đăng nhập với mật khẩu: `admin123`
- Thêm/Sửa/Xóa video
- Hỗ trợ CKEditor cho nội dung clip
- Quản lý 4 loại nội dung khác nhau
- Hiển thị thống kê lượt xem

### Lấy mã code (code.php)
- Hiển thị mã code từ URL parameter
- Dùng cho API rút gọn link

## Cấu trúc file

```
├── index.php          # Trang chủ
├── video.php          # Xem video
├── admin.php          # Trang quản trị
├── code.php           # Lấy mã code
├── api/
│   └── videos.php     # API lấy danh sách video
└── data/
    └── videos.json    # Database lưu trữ video
```

## Cài đặt

1. Upload tất cả file lên hosting hỗ trợ PHP
2. Đảm bảo thư mục `data/` có quyền ghi (chmod 755)
3. Truy cập `admin.php` để quản lý video
4. Mật khẩu admin mặc định: `admin123`

## Sử dụng

1. **Thêm video**: Đăng nhập trang admin, điền thông tin và chọn loại nội dung
2. **Xem video**: Click vào video ở trang chủ
3. **Lấy mã code**: 
   - Mỗi người dùng sẽ có một mã code riêng cho video đó
   - Click vào link rút gọn được tạo tự động để lấy mã code
   - Nhập mã code vào ô nhập để xem video
4. **Lưu ý**: Mã code được lưu trong session, không cần nhập lại cho cùng một video

## API

### GET /api/videos.php
Trả về danh sách tất cả video dưới dạng JSON.

Response:
```json
[
  {
    "id": 1234567890,
    "title": "Tiêu đề video",
    "description": "Mô tả video",
    "content": "Link nội dung",
    "type": "video|clip|image|fake",
    "views": 100,
    "created_at": "2024-01-01 12:00:00"
  }
]
```

## Tùy chỉnh

- Đổi mật khẩu admin: Sửa biến `$adminPassword` trong `admin.php`
- Tùy chỉnh giao diện: Sửa CSS classes trong các file HTML
- Thay đổi API token: Sửa biến `$token` trong `video.php`

## Lưu ý

- Hệ thống sử dụng file JSON để lưu trữ dữ liệu
- **Mỗi người dùng có một mã code riêng cho mỗi video (lưu trong session)**
- Session được dùng để lưu trạng thái đăng nhập và mã code đã nhập
- API rút gọn link của yeumoney.com được tích hợp sẵn
- Responsive design hoạt động tốt trên mọi thiết bị
- Code được tạo động khi người dùng truy cập video lần đầu