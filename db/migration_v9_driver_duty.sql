-- ============================================================
-- Migration v9: Driver Duty System
-- ============================================================

USE poultry_ecommerce;

CREATE TABLE IF NOT EXISTS driver_duty (
  id INT AUTO_INCREMENT PRIMARY KEY,
  driver_id INT NOT NULL UNIQUE,
  status ENUM('ON_DUTY','OFF_DUTY') NOT NULL DEFAULT 'OFF_DUTY',
  clock_in_at DATETIME NULL,
  clock_out_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_duty_driver FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
