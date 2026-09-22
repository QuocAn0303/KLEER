-- ============================================
-- KLEER - MariaDB Init Script
-- Chạy tự động khi MariaDB khởi động lần đầu
-- ============================================

-- Tạo database nếu chưa có
CREATE DATABASE IF NOT EXISTS kleer_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tạo user nếu chưa có
CREATE USER IF NOT EXISTS 'kleer_user'@'%' IDENTIFIED BY 'kleer_password_here_change_me';
GRANT ALL PRIVILEGES ON kleer_db.* TO 'kleer_user'@'%';
FLUSH PRIVILEGES;

-- Cấu hình cho WooCommerce
SET GLOBAL max_allowed_packet = 104857600;
