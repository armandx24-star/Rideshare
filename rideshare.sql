
CREATE DATABASE rideshare ;
USE rideshare;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS driver_rejections;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS rides;
DROP TABLE IF EXISTS fare_settings;
DROP TABLE IF EXISTS admin;
DROP TABLE IF EXISTS drivers;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_pic VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    vehicle_type ENUM('bike','mini','sedan') NOT NULL DEFAULT 'mini',
    vehicle_number VARCHAR(50) NOT NULL,
    license_number VARCHAR(50) NOT NULL,
    profile_pic VARCHAR(255) DEFAULT NULL,
    document_path VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    online_status TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE fare_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_type ENUM('bike','mini','sedan') NOT NULL,
    base_fare DECIMAL(10,2) NOT NULL DEFAULT 50.00,
    per_km_rate DECIMAL(10,2) NOT NULL DEFAULT 10.00,
    minimum_fare DECIMAL(10,2) NOT NULL DEFAULT 70.00,
    night_surcharge_percent DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vehicle (vehicle_type)
) ENGINE=InnoDB;
CREATE TABLE rides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    driver_id INT DEFAULT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    drop_location VARCHAR(255) NOT NULL,
    pickup_lat DECIMAL(10,8) NOT NULL,
    pickup_lng DECIMAL(11,8) NOT NULL,
    drop_lat DECIMAL(10,8) NOT NULL,
    drop_lng DECIMAL(11,8) NOT NULL,
    distance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    vehicle_type ENUM('bike','mini','sedan') NOT NULL DEFAULT 'mini',
    fare DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','accepted','ongoing','completed','cancelled') NOT NULL DEFAULT 'pending',
    cancellation_reason VARCHAR(255) DEFAULT NULL,
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL UNIQUE,
    user_to_driver TINYINT(1) DEFAULT NULL COMMENT '1-5 star rating from user to driver',
    driver_to_user TINYINT(1) DEFAULT NULL COMMENT '1-5 star rating from driver to user',
    user_comment VARCHAR(255) DEFAULT NULL,
    driver_comment VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE driver_rejections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL,
    driver_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rejection (ride_id, driver_id),
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
) ENGINE=InnoDB;


INSERT INTO admin (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO fare_settings (vehicle_type, base_fare, per_km_rate, minimum_fare, night_surcharge_percent) VALUES
('bike',  30.00, 7.00,  50.00, 20.00),
('mini',  50.00, 10.00, 70.00, 20.00),
('sedan', 80.00, 14.00, 100.00, 20.00);

INSERT INTO users (name, email, phone, password) VALUES
('Demo User', 'user@demo.com', '9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO drivers (name, email, phone, password, vehicle_type, vehicle_number, license_number, status, online_status) VALUES
('Demo Driver', 'driver@demo.com', '9123456780', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mini', 'MH01AB1234', 'DL1234567890', 'approved', 0);
