# GRAND HOTEL - HỆ THỐNG GIAO DIỆN KHÁCH SẠN 5 SAO (FRONTEND)

Đồ án môn học: Lập trình Web - Giao diện Đặt phòng và Quản trị Khách sạn Cao cấp (Frontend HTML5/CSS3).

## Cấu Trúc Dự Án

- `frontend/`: Toàn bộ mã nguồn giao diện khách sạn và cổng quản trị.
  - `index.html`: Trang chủ giới thiệu thương hiệu Grand Hotel, câu chuyện 5 sao và thanh tìm kiếm phòng nhanh.
  - `rooms.html`: Danh mục phòng nghỉ với bộ lọc thông minh (khoảng giá, loại giường, tiện ích) và banner ưu đãi độc quyền.
  - `room-detail.html`: Trang chi tiết từng hạng phòng, bộ sưu tập hình ảnh, thông số kỹ thuật và chính sách nghỉ dưỡng.
  - `booking.html`: Biểu mẫu đặt phòng trực tuyến, chọn dịch vụ gia tăng và phương thức thanh toán an toàn.
  - `booking-success.html`: Trang xác nhận đặt phòng thành công kèm mã đặt phòng và nút in/tải hóa đơn điện tử.
  - `check-booking.html`: Cổng tra cứu kỳ nghỉ dành riêng cho khách hàng theo Số điện thoại/Email và Ngày nhận phòng hoặc Tháng đi.
  - `services.html`: Danh mục dịch vụ tiện ích đẳng cấp (Hồ bơi vô cực, Spa trị liệu, Ẩm thực Fine Dining, Đưa đón xe sang).
  - `about.html`: Trang giới thiệu lịch sử hình thành, giá trị cốt lõi và đội ngũ điều hành Grand Hotel.
  - `contact.html`: Kênh liên hệ trực tiếp, biểu mẫu gửi phản hồi và bản đồ Google Maps tích hợp.
  - `admin/`: Phân hệ quản trị dành cho bộ phận điều hành:
    - `login.html`: Cổng đăng nhập phân quyền và đăng ký tài khoản khách hàng mới.
    - `dashboard.html`: Bảng điều khiển tổng quan thống kê doanh thu, tỷ lệ lấp đầy phòng và đơn đặt mới.
    - `room-list.html`: Bảng quản lý danh sách phòng, trạng thái buồng phòng và bộ lọc hạng phòng.
    - `room-add.html`: Biểu mẫu thêm mới và cập nhật thông số buồng phòng.
    - `booking-list.html`: Bảng điều phối đơn đặt phòng, cập nhật trạng thái thanh toán và check-in/check-out.
  - `assets/css/`: Hệ thống định kiểu CSS phân tầng:
    - `base.css`: Thiết lập biến màu sắc (Navy `#0b1a2c`, Gold `#d8ac34`), typography, typography reset và header/footer dùng chung.
    - `style-home.css`: Định kiểu chuyên biệt cho trang chủ và các thành phần trình chiếu.
    - `style-rooms.css`: Định kiểu bố cục 3 cột cân đối cho danh mục phòng (Bộ lọc - Danh sách - Ưu đãi).
    - `style-booking.css`: Định kiểu biểu mẫu đặt phòng và thẻ voucher tra cứu kỳ nghỉ.
    - `style-admin.css`: Định kiểu giao diện quản trị với thanh bên sidebar và bảng số liệu trực quan.

## Công Nghệ Ứng Dụng

- HTML5 Semantic: Chuẩn hóa ngữ nghĩa hỗ trợ tối ưu SEO và cấu trúc trang rõ ràng.
- CSS3 Modern: Tận dụng CSS Grid, Flexbox, CSS Variables và hiệu ứng chuyển động mượt mà.
- Thiết kế Responsive: Tương thích hoàn hảo trên mọi kích thước màn hình (Desktop, Tablet, Mobile).
