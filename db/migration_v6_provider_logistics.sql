-- ============================================================
-- Migration v6: Provider & Logistics workflow tables
-- ============================================================

USE poultry_ecommerce;

-- 1) Extend order_dispatch_items with driver/provider tracking
--    and add ASSIGNED status for provider-assigned orders.
ALTER TABLE order_dispatch_items
  MODIFY status ENUM('DISPATCHED','ASSIGNED','IN_TRANSIT','DELIVERED','RETURN_REQUESTED','RETURNED')
    NOT NULL DEFAULT 'DISPATCHED',
  ADD COLUMN IF NOT EXISTS assigned_driver_id INT NULL AFTER user_id,
  ADD COLUMN IF NOT EXISTS assigned_driver_name VARCHAR(120) NULL AFTER assigned_driver_id,
  ADD COLUMN IF NOT EXISTS provider_id INT NULL AFTER assigned_driver_name;

-- Add foreign keys in separate statements for better compatibility
ALTER TABLE order_dispatch_items
  ADD CONSTRAINT fk_od_driver FOREIGN KEY (assigned_driver_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE order_dispatch_items
  ADD CONSTRAINT fk_od_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE SET NULL;

-- 2) Delivery OTP table for logistics handover verification
CREATE TABLE IF NOT EXISTS delivery_otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id VARCHAR(80) NOT NULL,
  driver_id INT NOT NULL,
  customer_id INT NOT NULL,
  otp_code VARCHAR(6) NOT NULL,
  otp_hash VARCHAR(255) NOT NULL,
  status ENUM('PENDING','VERIFIED','EXPIRED','FAILED') NOT NULL DEFAULT 'PENDING',
  expires_at DATETIME NOT NULL,
  verified_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_otp_order (order_id),
  INDEX idx_otp_driver (driver_id),
  INDEX idx_otp_customer (customer_id),
  INDEX idx_otp_status (status),
  CONSTRAINT fk_otp_driver FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_otp_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
