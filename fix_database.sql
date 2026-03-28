USE rideshare;

ALTER TABLE users ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL DEFAULT '' AFTER phone;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL AFTER password;

ALTER TABLE drivers ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL DEFAULT '' AFTER phone;
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS vehicle_type ENUM('bike','mini','sedan') NOT NULL DEFAULT 'mini' AFTER password;
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS vehicle_number VARCHAR(50) NOT NULL DEFAULT '' AFTER vehicle_type;
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS license_number VARCHAR(50) NOT NULL DEFAULT '' AFTER vehicle_number;
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL AFTER license_number;
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS document_path VARCHAR(255) DEFAULT NULL AFTER profile_pic;
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER document_path;
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS online_status TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS upi_id VARCHAR(100) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS fare_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_type ENUM('bike','mini','sedan') NOT NULL,
    base_fare DECIMAL(10,2) NOT NULL DEFAULT 50.00,
    per_km_rate DECIMAL(10,2) NOT NULL DEFAULT 10.00,
    minimum_fare DECIMAL(10,2) NOT NULL DEFAULT 70.00,
    night_surcharge_percent DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vehicle (vehicle_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL UNIQUE,
    user_to_driver TINYINT(1) DEFAULT NULL,
    driver_to_user TINYINT(1) DEFAULT NULL,
    user_comment VARCHAR(255) DEFAULT NULL,
    driver_comment VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS driver_rejections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL,
    driver_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rejection (ride_id, driver_id)
) ENGINE=InnoDB;

ALTER TABLE rides ADD COLUMN IF NOT EXISTS vehicle_type ENUM('bike','mini','sedan') NOT NULL DEFAULT 'mini' AFTER distance;
ALTER TABLE rides ADD COLUMN IF NOT EXISTS cancellation_reason VARCHAR(255) DEFAULT NULL AFTER status;
ALTER TABLE rides ADD COLUMN IF NOT EXISTS started_at TIMESTAMP NULL DEFAULT NULL AFTER cancellation_reason;
ALTER TABLE rides ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL DEFAULT NULL AFTER started_at;

-- Payment columns needed for driver earnings, passenger payment, and admin revenue
ALTER TABLE rides ADD COLUMN IF NOT EXISTS payment_method ENUM('cash','upi','online') DEFAULT NULL;
ALTER TABLE rides ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','confirmed') NOT NULL DEFAULT 'pending';
ALTER TABLE rides MODIFY COLUMN status ENUM('pending','accepted','ongoing','payment_pending','completed','cancelled') NOT NULL DEFAULT 'pending';
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS upi_id VARCHAR(100) DEFAULT NULL;

INSERT IGNORE INTO fare_settings (vehicle_type, base_fare, per_km_rate, minimum_fare, night_surcharge_percent) VALUES
('bike',  30.00, 7.00,  50.00, 20.00),
('mini',  50.00, 10.00, 70.00, 20.00),
('sedan', 80.00, 14.00, 100.00, 20.00);

INSERT IGNORE INTO admin (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

SELECT 'Database fixed successfully!' AS status;
