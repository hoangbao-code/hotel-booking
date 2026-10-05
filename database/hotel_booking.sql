SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `hotel_db`;
CREATE DATABASE `hotel_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hotel_db`;

CREATE TABLE `users` (
  `user_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NULL UNIQUE,
  `phone` VARCHAR(20) NULL,
  `role` ENUM('admin', 'receptionist', 'customer') NOT NULL DEFAULT 'customer',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `room_categories` (
  `category_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT UNSIGNED NULL,
  `category_name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `room_categories`(`category_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `rooms` (
  `room_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `room_number` VARCHAR(50) NOT NULL UNIQUE,
  `room_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `price` DECIMAL(15,2) UNSIGNED NOT NULL,
  `sale_price` DECIMAL(15,2) UNSIGNED NULL,
  `short_description` VARCHAR(500) NULL,
  `thumbnail` VARCHAR(255) NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rooms_category` FOREIGN KEY (`category_id`) REFERENCES `room_categories`(`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `room_specifications` (
  `spec_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT UNSIGNED NOT NULL,
  `spec_name` VARCHAR(100) NOT NULL,
  `spec_value` VARCHAR(255) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT `fk_specs_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`room_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `room_inventory` (
  `inventory_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT UNSIGNED NOT NULL UNIQUE,
  `total_rooms` INT UNSIGNED NOT NULL DEFAULT 1,
  `reserved_rooms` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_inventory_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`room_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_inventory_reserved` CHECK (`reserved_rooms` <= `total_rooms`)
) ENGINE=InnoDB;

CREATE TABLE `bookings` (
  `booking_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_code` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `check_in` DATE NOT NULL,
  `check_out` DATE NOT NULL,
  `total_amount` DECIMAL(15,2) UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('pending', 'confirmed', 'checkedin', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `booking_items` (
  `item_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT UNSIGNED NOT NULL,
  `room_id` INT UNSIGNED NOT NULL,
  `room_name` VARCHAR(255) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(15,2) UNSIGNED NOT NULL,
  `subtotal` DECIMAL(15,2) UNSIGNED NOT NULL,
  CONSTRAINT `fk_items_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`room_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `payments` (
  `payment_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT UNSIGNED NOT NULL,
  `payment_method` ENUM('COD', 'Bank Transfer', 'VNPay', 'Momo') NOT NULL,
  `amount` DECIMAL(15,2) UNSIGNED NOT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `paid_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `status`) VALUES
(1, 'admin', '123456', 'Quản Trị Viên Khách Sạn', 'admin@grandhotel.vn', '0901000001', 'admin', 1),
(2, 'reception01', '123456', 'Lễ Tân Minh Thư', 'letan01@grandhotel.vn', '0901000002', 'receptionist', 1),
(3, 'khach01', '123456', 'Nguyễn Văn An', 'an.nguyen@gmail.com', '0912345678', 'customer', 1),
(4, 'khach02', '123456', 'Trần Thị Bích', 'bich.tran@gmail.com', '0988777666', 'customer', 1),
(5, 'khach03', '123456', 'Lê Hoàng Nam', 'nam.le@gmail.com', '0933222111', 'customer', 1);

INSERT INTO `room_categories` (`category_id`, `parent_id`, `category_name`, `slug`, `description`, `status`) VALUES
(1, NULL, 'Standard', 'standard', 'Hạng phòng cơ bản tiện nghi', 1),
(2, NULL, 'Deluxe', 'deluxe', 'Hạng phòng sang trọng rộng rãi', 1),
(3, 2, 'Deluxe City View', 'deluxe-city-view', 'Phòng Deluxe hướng nhìn toàn cảnh thành phố', 1),
(4, 2, 'Deluxe River View', 'deluxe-river-view', 'Phòng Deluxe hướng nhìn view sông êm đềm', 1),
(5, NULL, 'Suite', 'suite', 'Hạng phòng thượng hạng cao cấp', 1),
(6, 5, 'Executive Suite', 'executive-suite', 'Phòng Suite phong cách doanh nhân', 1),
(7, 5, 'President Suite', 'president-suite', 'Phòng tổng thống hoàng gia', 1);

INSERT INTO `rooms` (`room_id`, `category_id`, `room_number`, `room_name`, `slug`, `price`, `sale_price`, `short_description`, `thumbnail`, `status`) VALUES
(1, 1, 'P.101', 'Standard Cozy Single', 'standard-cozy-single', 950000, 850000, 'Phòng đơn ấm cúng tiện nghi cho chuyến công tác', 'standard-101.jpg', 1),
(2, 3, 'P.201', 'Deluxe Double City View', 'deluxe-double-city-view', 1650000, 1500000, 'Cửa sổ panorama kính lớn ngắm nhìn toàn cảnh thành phố', 'deluxe-201.jpg', 1),
(3, 4, 'P.202', 'Deluxe River View', 'deluxe-river-view', 1800000, 1650000, 'Không gian thoáng đãng ngắm trọn bình minh hướng sông', 'deluxe-202.jpg', 1),
(4, 3, 'P.301', 'Deluxe Twin Two Beds', 'deluxe-twin-two-beds', 1900000, 1750000, 'Phòng 2 giường đơn riêng biệt cho bạn bè và đồng nghiệp', 'deluxe-301.jpg', 1),
(5, 6, 'P.401', 'Executive Premium Suite', 'executive-premium-suite', 3100000, 2800000, 'Có phòng khách riêng biệt, bồn sục Jacuzzi thư giãn', 'suite-401.jpg', 1),
(6, 7, 'P.601', 'Royal President Suite', 'royal-president-suite', 7200000, 6500000, 'Căn penthouse đỉnh cao với quản gia phục vụ riêng', 'suite-601.jpg', 1);

INSERT INTO `room_specifications` (`spec_id`, `room_id`, `spec_name`, `spec_value`, `sort_order`) VALUES
(1, 1, 'Diện tích', '28 m²', 1),
(2, 1, 'Giường ngủ', '1 Giường Đơn', 2),
(3, 1, 'Sức chứa', '1 Khách', 3),
(4, 1, 'Hướng nhìn', 'Nội khu yên tĩnh', 4),
(5, 2, 'Diện tích', '36 m²', 1),
(6, 2, 'Giường ngủ', '1 Giường King lớn', 2),
(7, 2, 'Sức chứa', '2 Khách', 3),
(8, 2, 'Hướng nhìn', 'Toàn cảnh thành phố', 4);

INSERT INTO `room_inventory` (`inventory_id`, `room_id`, `total_rooms`, `reserved_rooms`) VALUES
(1, 1, 10, 2),
(2, 2, 8, 1),
(3, 3, 6, 2),
(4, 4, 8, 1),
(5, 5, 4, 1),
(6, 6, 2, 0);

INSERT INTO `bookings` (`booking_id`, `booking_code`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `check_in`, `check_out`, `total_amount`, `status`, `created_at`) VALUES
(1, 'GH2026-9988', 3, 'Nguyễn Văn An', '0912345678', 'an.nguyen@gmail.com', '2026-10-10', '2026-10-12', 3000000, 'completed', '2026-09-02 10:00:00'),
(2, 'GH2026-9989', 4, 'Trần Thị Bích', '0988777666', 'bich.tran@gmail.com', '2026-10-11', '2026-10-13', 1700000, 'completed', '2026-09-04 14:20:00'),
(3, 'GH2026-9990', 5, 'Lê Hoàng Nam', '0933222111', 'nam.le@gmail.com', '2026-10-14', '2026-10-16', 5600000, 'completed', '2026-09-06 09:30:00'),
(4, 'GH2026-9991', 3, 'Nguyễn Văn An', '0912345678', 'an.nguyen@gmail.com', '2026-10-18', '2026-10-20', 3300000, 'completed', '2026-09-10 11:15:00'),
(5, 'GH2026-9992', 4, 'Trần Thị Bích', '0988777666', 'bich.tran@gmail.com', '2026-10-22', '2026-10-24', 3000000, 'completed', '2026-09-12 16:45:00'),
(6, 'GH2026-9993', 5, 'Lê Hoàng Nam', '0933222111', 'nam.le@gmail.com', '2026-10-25', '2026-10-27', 6500000, 'pending', '2026-09-14 08:30:00');

INSERT INTO `booking_items` (`item_id`, `booking_id`, `room_id`, `room_name`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 2, 'Deluxe Double City View', 2, 1500000, 3000000),
(2, 2, 1, 'Standard Cozy Single', 2, 850000, 1700000),
(3, 3, 5, 'Executive Premium Suite', 2, 2800000, 5600000),
(4, 4, 3, 'Deluxe River View', 2, 1650000, 3300000),
(5, 5, 2, 'Deluxe Double City View', 2, 1500000, 3000000),
(6, 6, 6, 'Royal President Suite', 1, 6500000, 6500000);

INSERT INTO `payments` (`payment_id`, `booking_id`, `payment_method`, `amount`, `payment_status`, `paid_at`, `created_at`) VALUES
(1, 1, 'Bank Transfer', 3000000, 'paid', '2026-09-02 10:15:00', '2026-09-02 10:15:00'),
(2, 2, 'VNPay', 1700000, 'paid', '2026-09-04 14:30:00', '2026-09-04 14:30:00'),
(3, 3, 'Bank Transfer', 5600000, 'paid', '2026-09-06 09:45:00', '2026-09-06 09:45:00'),
(4, 4, 'Momo', 3300000, 'paid', '2026-09-10 11:25:00', '2026-09-10 11:25:00'),
(5, 5, 'Bank Transfer', 3000000, 'paid', '2026-09-12 17:00:00', '2026-09-12 17:00:00'),
(6, 6, 'COD', 6500000, 'pending', NULL, '2026-09-14 08:30:00');

SET FOREIGN_KEY_CHECKS = 1;
