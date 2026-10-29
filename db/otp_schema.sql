-- OTP table for Textbee OTP login
-- Run in phpMyAdmin (SQL tab)

USE poultry_ecommerce;

CREATE TABLE IF NOT EXISTS otp_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone VARCHAR(10) NOT NULL,
  otp_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempt_count INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_otp_phone_created (phone, created_at)
) ENGINE=InnoDB;

