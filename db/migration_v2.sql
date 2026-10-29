-- ============================================================
-- Migration v2: Add price, size, role columns
-- Run this in phpMyAdmin (SQL tab) after the existing schema
-- ============================================================

USE poultry_ecommerce;

-- Add price and size columns to products
ALTER TABLE products
  ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER description,
  ADD COLUMN size VARCHAR(50) NOT NULL DEFAULT '' AFTER price;

-- Add role column to users (admin = shopkeeper, customer = buyer)
ALTER TABLE users
  ADD COLUMN role ENUM('admin','customer') NOT NULL DEFAULT 'customer' AFTER password_hash;

-- ============================================================
-- Seed prices (INR ₹) and sizes for existing products
-- ============================================================

-- Vaccines (Liquid vials for injection OR Spore/Freeze-Dried for oral/water, ₹180-₹2500)
-- Liquid vials are measured in ml; Spore/Freeze-Dried are measured in doses per vial
UPDATE products SET price = 450.00, size = '5ml vial (liquid)' WHERE sku = 'VCX-NEW-001';
UPDATE products SET price = 180.00, size = '2ml vial (liquid)' WHERE sku = 'VCX-GUMB-001';
UPDATE products SET price = 350.00, size = '1000 dose vial (spore)' WHERE sku = 'VCX-IBD-002';
UPDATE products SET price = 280.00, size = '1000 dose vial (spore)' WHERE sku = 'VCX-ND-003';
UPDATE products SET price = 500.00, size = '1000 dose vial (spore)' WHERE sku = 'VCX-REV-004';
UPDATE products SET price = 600.00, size = '5ml vial (liquid)' WHERE sku = 'VCX-H5-005';

-- Layer Medicines (Liquids: 500ml, Powders: 500g, ₹400-₹1800)
UPDATE products SET price = 850.00, size = '500ml' WHERE sku = 'MED-OXY-001';
UPDATE products SET price = 750.00, size = '500ml' WHERE sku = 'MED-LEV-002';
UPDATE products SET price = 600.00, size = '500g' WHERE sku = 'MED-AMPROLIUM-001';
UPDATE products SET price = 1200.00, size = '500g' WHERE sku = 'MED-FENBENDAZOLE-001';
UPDATE products SET price = 1800.00, size = '500ml' WHERE sku = 'MED-HYGROMYCINB-001';
UPDATE products SET price = 950.00, size = '500g' WHERE sku = 'MED-BMD-001';
UPDATE products SET price = 1100.00, size = '500g' WHERE sku = 'MED-CTC-001';

-- Feed Medicines (Powders: 250g-1000g, ₹200-₹600)
UPDATE products SET price = 350.00, size = '500g' WHERE sku = 'FMD-CHL-001';
UPDATE products SET price = 450.00, size = '500g' WHERE sku = 'FMD-TAX-001';

-- Feed Supplements (Powders: 500g or 1000g, ₹250-₹500)
UPDATE products SET price = 400.00, size = '500g' WHERE sku = 'FEED-AD3E-001';
UPDATE products SET price = 350.00, size = '500g' WHERE sku = 'FEED-B-COMP-001';
UPDATE products SET price = 300.00, size = '500g' WHERE sku = 'FEED-C-SUP-001';
UPDATE products SET price = 250.00, size = '500g' WHERE sku = 'FEED-ELECTRO-001';
UPDATE products SET price = 280.00, size = '500g' WHERE sku = 'FEED-CALCIUM-001';
UPDATE products SET price = 350.00, size = '500ml' WHERE sku = 'FEED-LIVER-001';
UPDATE products SET price = 450.00, size = '500g' WHERE sku = 'FEED-PROBIOTIC-001';

-- Water Sanitation (Liquids: 500ml or 1000ml, Powders: 500g, ₹150-₹400)
UPDATE products SET price = 200.00, size = '500g' WHERE sku = 'WTR-CHL-TAB-001';
UPDATE products SET price = 150.00, size = '500g' WHERE sku = 'WTR-BLEACH-001';
UPDATE products SET price = 180.00, size = '500ml' WHERE sku = 'WTR-SOD-HYPO-001';
UPDATE products SET price = 250.00, size = '500ml' WHERE sku = 'WTR-H2O2-001';
UPDATE products SET price = 300.00, size = '500ml' WHERE sku = 'WTR-IOD-001';
UPDATE products SET price = 220.00, size = '500g' WHERE sku = 'WTR-PERMANG-001';
UPDATE products SET price = 350.00, size = '500ml' WHERE sku = 'WTR-CHLORINE-DIOX-001';
UPDATE products SET price = 400.00, size = '500ml' WHERE sku = 'WTR-QAC-001';
UPDATE products SET price = 280.00, size = '500ml' WHERE sku = 'WTR-GLUTAR-001';
UPDATE products SET price = 200.00, size = '500ml' WHERE sku = 'WTR-FORMALIN-001';

-- Set first user (id=1) as admin for testing
UPDATE users SET role = 'admin' WHERE id = 1;

