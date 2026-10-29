-- ============================================================
-- Migration v3: Fix OTP login
-- Removes the FK constraint that blocks OTP requests for
-- phones not yet registered in the users table.
-- Run this in phpMyAdmin (SQL tab) after migration_v2.
-- ============================================================

USE poultry_ecommerce;

-- Drop the FK that prevents OTP for unregistered phones.
-- OTP login should work for ANY phone; the user is auto-created
-- on successful verification (see auth/verify_otp.php).
ALTER TABLE otp_requests DROP FOREIGN KEY fk_otp_user_phone;
