-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS leave_management;

USE leave_management;

-- Create the users table
CREATE TABLE IF NOT EXISTS users (
                                     id INT AUTO_INCREMENT PRIMARY KEY,
                                     name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
    );

-- Create the leave_requests table with admin_comment column
CREATE TABLE IF NOT EXISTS leave_requests (
                                              id INT AUTO_INCREMENT PRIMARY KEY,
                                              user_id INT NOT NULL,
                                              leave_type ENUM('sick', 'vacation', 'personal', 'emergency', 'other') NOT NULL,
    reason TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_comment TEXT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    );

-- Create activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
                                             id INT AUTO_INCREMENT PRIMARY KEY,
                                             user_id INT NULL,
                                             action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    );

-- Add test user accounts with specific timestamp
INSERT INTO users (name, email, password, role, created_at)
VALUES
    ('Test', 'test@gmail.com', '$2y$10$3fW.Y8AFl0gUpHl1o1.fiuGQgNJ5E5kz9abLF483DTwH53XMcNnvO', 'user', '2025-06-29 17:46:05'),
    ('admin', 'admin@gmail.com', '$2y$10$qFnmvhYoM4ywLCyuY.dwK.ZLquBkbqIzQIZhA383k4Zt2NAByR.wK', 'admin', '2025-06-29 17:46:05');

-- Add initial activity log
INSERT INTO activity_logs (action, details, ip_address, created_at)
VALUES ('System Initialized', 'Database setup completed', '127.0.0.1', '2025-06-29 17:46:05');