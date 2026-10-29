-- ============================================================
-- Migration v4: Multi-role RBAC (customer / provider / logistics)
-- Requires: schema.sql + migration_v2.sql + migration_v3.sql +
--           otp_schema.sql + returns_schema.sql (already applied)
-- Run this in phpMyAdmin (SQL tab) or via the CLI helper.
-- ============================================================

USE poultry_ecommerce;

-- 1) Extend users.role to support the three platform roles
--    ('admin' is kept for backward compatibility and is granted
--    access to every guarded route by auth/rbac.php).
ALTER TABLE users
  MODIFY role ENUM('admin','customer','provider','logistics')
    NOT NULL DEFAULT 'customer';

-- 2) Friendly display name used in navigation + delivery SMS
ALTER TABLE users
  ADD COLUMN full_name VARCHAR(120) NOT NULL DEFAULT '' AFTER phone;

-- 3) Passkey / biometric simulation flag (demo login tab)
ALTER TABLE users
  ADD COLUMN biometric_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER role;

-- 4) Logistics lifecycle status on dispatched order items.
--    DISPATCHED -> shown as "Pending" in the logistics dashboard,
--    IN_TRANSIT -> "In-Transit", DELIVERED -> "Delivered".
ALTER TABLE order_dispatch_items
  MODIFY status ENUM('DISPATCHED','IN_TRANSIT','DELIVERED','RETURN_REQUESTED','RETURNED')
    NOT NULL DEFAULT 'DISPATCHED';

-- 5) Treat the legacy admin account as a provider so it can manage inventory.
UPDATE users SET role = 'provider' WHERE role = 'admin';
