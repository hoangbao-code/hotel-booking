# HỆ THỐNG ĐẶT PHÒNG KHÁCH SẠN (PURE PHP + MYSQL PDO)
**Khách Sạn:** Grand Oasis Resort & Spa  
**Kiến trúc:** PHP 8.x thuần (không framework, không Composer), MySQL InnoDB (utf8mb4), HTML5/CSS3/Vanilla JS  
**Môi trường:** XAMPP (Apache + MySQL)

---

## 1. Tổng Quan Dự Án & Phạm Vi

Hệ thống được thiết kế và xây dựng theo chuẩn mực lập trình viên Senior, phục vụ một khách sạn / resort duy nhất với các nguyên tắc nghiệp vụ cốt lõi:
- **Thanh toán trực tiếp:** Khách đặt phòng không cần thanh toán online, không cần thẻ tín dụng — thanh toán 100% tại quầy khi nhận phòng (*Pay on Arrival*).
- **Không gửi email:** Mọi thông tin đặt phòng được theo dõi trực tiếp trong tài khoản khách hàng.
- **Không phụ thuộc thư viện ngoài:** Không Composer, không npm, hoàn toàn là PHP thuần + PDO và HTML/CSS/JS vanilla, dễ dàng cài đặt, bảo trì và bảo vệ đồ án.

---

## 2. Cấu Trúc Thư Mục Hệ Thống

```
hotel/
├── database.sql               # Script DDL 4 bảng, ràng buộc toàn vẹn & dữ liệu mẫu
├── create_admin.php           # Script khởi tạo tài khoản mẫu an toàn
├── README.md                  # Tài liệu hướng dẫn cài đặt & bảo vệ đồ án
│
├── config/
│   ├── db.php                 # Kết nối PDO (ERRMODE_EXCEPTION, EMULATE_PREPARES=false, utf8mb4)
│   └── functions.php          # Session an toàn, CSRF, sanitize e(), flash message, base_url, auth
│
├── includes/
│   ├── header.php             # HTML head, Google Fonts, topbar, menu điều hướng động, flash alerts
│   └── footer.php             # Footer 5 sao sang trọng, thông tin liên hệ, script main.js
│
├── assets/
│   ├── css/style.css          # Hệ thống thiết kế resort 5 sao, typography Serif/Sans, responsive
│   └── js/main.js             # Vanilla JS: validate date picker, auto +1 đêm, chống double-submit
│
├── uploads/                   # Ảnh các hạng phòng thực tế & banner resort độ phân giải cao
│   ├── .htaccess              # Chặn hoàn toàn việc thực thi mã PHP trong thư mục uploads
│   ├── standard.jpg
│   ├── deluxe.jpg
│   ├── suite.jpg
│   ├── hero.jpg
│   └── placeholder.jpg
│
├── [PHÂN HỆ KHÁCH HÀNG / PUBLIC]
├── index.php                  # Trang chủ, hero banner, form tìm phòng trống, tiện ích, đánh giá
├── rooms.php                  # Danh sách hạng phòng & kết quả tìm kiếm khả dụng (Truy vấn Q3)
├── book.php                   # Trang xác nhận đặt phòng, phân tích chi phí, chính sách thanh toán
├── process_booking.php        # Transaction đặt phòng: SELECT FOR UPDATE + Q4 + CSRF + PRG
├── my_bookings.php            # Lịch sử đơn đặt phòng của chính khách hàng, trạng thái, hủy đơn
├── booking_voucher.php        # Phiếu xác nhận đặt phòng chính thức hỗ trợ in ấn / xuất PDF
├── cancel_booking.php         # Xử lý hủy booking nguyên tử (Atomic Update Q5)
├── register.php               # Đăng ký tài khoản thành viên, hash mật khẩu an toàn
├── login.php                  # Đăng nhập bảo mật, CSRF, chống Session Fixation
├── logout.php                 # Đăng xuất an toàn, xóa toàn bộ session
│
└── [PHÂN HỆ QUẢN TRỊ VIÊN / ADMIN]
    ├── admin/_guard.php       # Bảo vệ Server-side: kiểm tra role !== 'admin' -> trả về HTTP 403 Forbidden
    ├── admin/_nav.php         # Menu phụ quản trị chuyển đổi giữa các tab
    ├── admin/index.php        # Dashboard: 4 thẻ thống kê + Sơ đồ buồng phòng thời gian thực (Room Matrix PMS)
    ├── admin/bookings.php     # Quản lý đơn: 4 tab lọc, tìm kiếm nhanh, duyệt/hủy Q6, xem Voucher
    ├── admin/room_types.php   # CRUD loại phòng, kiểm tra ràng buộc khóa ngoại Q7 (chặn xóa khi còn phòng)
    └── admin/rooms.php        # Quản lý phòng: Thêm phòng mới, bật/tắt bảo trì, chặn xóa khi đã có booking (Q7)

```

---

## 3. Lược Đồ Cơ Sở Dữ Liệu (DDL)

Hệ thống sử dụng đúng 4 bảng quan hệ theo chuẩn InnoDB:
- `users`: Tài khoản khách hàng (`role='customer'`) và quản trị viên (`role='admin'`).
- `room_types`: Hạng phòng lưu trú (`price_per_night > 0`, `capacity BETWEEN 1 AND 10`).
- `rooms`: Phòng vật lý (`status ENUM('active','maintenance')`, liên kết `room_types.id` với `ON DELETE RESTRICT`).
- `bookings`: Đơn đặt phòng (`status ENUM('pending','confirmed','cancelled')`, liên kết `user_id` và `room_id` với `ON DELETE RESTRICT`, ràng buộc `check_out > check_in` và `guests BETWEEN 1 AND 20`).

---

## 4. Tài Khoản Đăng Nhập Thử Nghiệm

| Vai trò | Email | Mật khẩu | Quyền hạn |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@hotel.vn` | `admin123` | Toàn quyền truy cập phân hệ `/admin`, duyệt đơn, quản lý phòng và loại phòng |
| **Khách hàng (Customer)** | `khach@hotel.vn` | `khach123` | Tìm kiếm phòng, đặt phòng, xem lịch sử và tự hủy đơn của chính mình |

---

## 5. Hiện Thực 8 Quy Tắc Nghiệp Vụ Cốt Lõi (Q1 - Q8)

1. **Q1 (Điều kiện giao nhau của ngày):**  
   - Biểu thức: `b.check_in < :check_out AND b.check_out > :check_in`.  
   - Đảm bảo khách trả phòng lúc 12:00 thì khách mới có thể nhận phòng lúc 14:00 cùng ngày mà không bị xung đột.
2. **Q2 (Định nghĩa phòng trống thực tế):**  
   - `rooms.status = 'active'` VÀ không có booking nào có `status <> 'cancelled'` bị trùng ngày.
3. **Q3 (Truy vấn khả dụng theo loại phòng):**  
   - Sử dụng subquery `LEFT JOIN bookings` với điều kiện Q1, gom nhóm theo loại phòng và đếm `COUNT(r.id) AS available_rooms`.
4. **Q4 (Transaction đặt phòng 4 bước chống Race Condition):**  
   - B1: Khóa dòng vật lý bằng `SELECT id FROM rooms WHERE room_type_id = ? FOR UPDATE`.  
   - B2: Đọc lại phòng trống thực tế trong transaction với `LIMIT 1`.  
   - B3: Tính tổng tiền hoàn toàn trên Server từ `price_per_night * nights` (tuyệt đối không nhận từ client).  
   - B4: `INSERT` đơn hàng `status = 'pending'`, gán `room_id`, commit và chuyển hướng PRG về `my_bookings.php`.
5. **Q5 (Khách hủy booking nguyên tử):**  
   - `UPDATE bookings SET status = 'cancelled' WHERE id = :id AND user_id = :user_id AND status IN ('pending', 'confirmed') AND check_in >= CURDATE()`.
6. **Q6 (Admin duyệt & hủy phòng):**  
   - Duyệt đơn: `UPDATE bookings SET status = 'confirmed' WHERE id = :id AND status = 'pending'`.  
   - Hủy đơn: `UPDATE bookings SET status = 'cancelled' WHERE id = :id AND status IN ('pending', 'confirmed') AND check_in >= CURDATE()`.
7. **Q7 (Ràng buộc khóa ngoại FK Restrict):**  
   - Chặn xóa loại phòng nếu đang có phòng con trực thuộc.  
   - Chặn xóa phòng vật lý nếu đã có đơn đặt phòng liên kết (gợi ý chuyển sang trạng thái "Bảo trì").
8. **Q8 (Bảo toàn dữ liệu lịch sử):**  
   - Không bao giờ xóa cứng dòng đơn (`DELETE FROM bookings`), thao tác hủy chỉ cập nhật `status = 'cancelled'`.

---

## 6. Bảo Mật Toàn Diện (Security Checklist)

- [x] **Chống SQL Injection:** 100% truy vấn dữ liệu động dùng Prepared Statements qua PDO (`EMULATE_PREPARES = false`).
- [x] **Chống Cross-Site Scripting (XSS):** Mọi dữ liệu in ra màn hình đều qua hàm `e()` với `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- [x] **Chống Cross-Site Request Forgery (CSRF):** Sinh token 64 ký tự hex ngẫu nhiên, kiểm tra chặt chẽ mọi form POST bằng `verify_csrf()` và `hash_equals()`.
- [x] **Chống Session Fixation:** Thực hiện `session_regenerate_id(true)` ngay khi người dùng đăng nhập hoặc đăng ký thành công.
- [x] **Bảo vệ Phân quyền Server-Side:** File `admin/_guard.php` kiểm tra session role ở đầu mỗi file trong `/admin`, trả về mã lỗi chuẩn `HTTP 403 Forbidden` nếu truy cập trái phép.
- [x] **Chống thực thi mã trong thư mục upload:** File `uploads/.htaccess` chặn đứng hoàn toàn việc chạy file `.php` nếu kẻ xấu tải file lên.
- [x] **Chống Double Submit:** Javascript vanilla tự động vô hiệu hóa nút submit và hiển thị trạng thái đang xử lý ngay sau khi gửi form.
