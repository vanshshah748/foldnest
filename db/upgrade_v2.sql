-- =============================================
-- FoldNest E-Commerce — Database Upgrade v2
-- Run this AFTER the original schema.sql and sprint3_schema.sql
-- =============================================

USE ecommerce_db;

-- =============================================
-- 1. ALTER USERS TABLE — Add phone, status, updated_at
-- =============================================
ALTER TABLE users
    ADD COLUMN phone VARCHAR(15) DEFAULT NULL AFTER email,
    ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER role,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- =============================================
-- 2. CREATE USER ADDRESSES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    alternate_mobile VARCHAR(15) DEFAULT NULL,
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255) DEFAULT NULL,
    landmark VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    country VARCHAR(50) DEFAULT 'India',
    address_type ENUM('Home', 'Office') DEFAULT 'Home',
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_addresses (user_id),
    INDEX idx_pincode (pincode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 3. CREATE ADMINS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) DEFAULT NULL,
    role ENUM('super_admin', 'admin', 'manager') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 4. ALTER ORDERS TABLE — Add tracking, payment, delivery fields
-- =============================================
ALTER TABLE orders
    ADD COLUMN tracking_id VARCHAR(20) DEFAULT NULL AFTER id,
    ADD COLUMN address_id INT DEFAULT NULL AFTER user_id,
    ADD COLUMN payment_method VARCHAR(50) DEFAULT 'COD' AFTER total_amount,
    ADD COLUMN payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending' AFTER payment_method,
    ADD COLUMN estimated_delivery DATE DEFAULT NULL AFTER payment_status,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    MODIFY COLUMN status ENUM('pending','confirmed','packed','shipped','out_for_delivery','delivered','cancelled') DEFAULT 'pending';

-- Add indexes for faster lookups
ALTER TABLE orders
    ADD UNIQUE INDEX idx_tracking (tracking_id),
    ADD INDEX idx_order_status (status),
    ADD INDEX idx_user_orders (user_id);

-- =============================================
-- 5. ALTER PRODUCTS TABLE — Add active status and updated_at
-- =============================================
ALTER TABLE products
    ADD COLUMN is_active TINYINT(1) DEFAULT 1,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- =============================================
-- 6. ADD INDEX TO CART ITEMS for faster lookups
-- =============================================
-- (The unique key already exists from sprint3, so just add a status index if needed)

-- =============================================
-- MIGRATION COMPLETE
-- =============================================
