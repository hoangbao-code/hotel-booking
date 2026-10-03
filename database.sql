-- ==============================================================================
-- DATABASE SCHEMA CHO DỰ ÁN ĐẶT PHÒNG KHÁCH SẠN (HOTEL)
-- Chuẩn mực: InnoDB, utf8mb4, ràng buộc toàn vẹn RESTRICT, CHECK constraints
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS hotel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_db;
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS checkouts;
DROP TABLE IF EXISTS service_orders;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS checkins;
DROP TABLE IF EXISTS booking_details;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS room_prices;
DROP TABLE IF EXISTS room_types;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. BẢNG USERS (Khách hàng & Quản trị viên)
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- 2. BẢNG ROOM_TYPES (Loại phòng)
CREATE TABLE room_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uq_room_types_name (name),
    CONSTRAINT chk_price CHECK (price_per_night > 0),
    CONSTRAINT chk_capacity CHECK (capacity BETWEEN 1 AND 10)
) ENGINE=InnoDB;

-- 3. BẢNG ROOMS (Phòng vật lý)
CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_type_id INT UNSIGNED NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    status ENUM('active','maintenance') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_rooms_number (room_number),
    KEY idx_rooms_type (room_type_id),
    CONSTRAINT fk_rooms_type FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. BẢNG BOOKINGS (Đơn đặt phòng)
CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests TINYINT UNSIGNED NOT NULL,
    status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_bookings_user (user_id),
    KEY idx_bookings_status (status),
    KEY idx_availability (room_id, check_in, check_out),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    CONSTRAINT chk_dates CHECK (check_out > check_in),
    CONSTRAINT chk_guests CHECK (guests BETWEEN 1 AND 20)
) ENGINE=InnoDB;

-- ==============================================================================
-- DỮ LIỆU BAN ĐẦU (SEED DATA)
-- ==============================================================================

INSERT INTO room_types (name, description, price_per_night, capacity, image_url) VALUES
('Standard', 'Phòng tiêu chuẩn, giường đôi, 20m²', 500000, 2, 'standard.jpg'),
('Deluxe', 'Phòng cao cấp, tầm nhìn thành phố, 28m²', 800000, 3, 'deluxe.jpg'),
('Suite', 'Căn hộ cao cấp, phòng khách riêng, 45m²', 1500000, 4, 'suite.jpg');

INSERT INTO rooms (room_type_id, room_number) VALUES
(1,'101'),(1,'102'),(1,'103'),(2,'201'),(2,'202'),(3,'301'),(3,'302');
