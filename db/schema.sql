-- Poultry e-commerce demo schema (simplified: single inventory, no local shop chain)
-- Run in phpMyAdmin (SQL tab)

CREATE DATABASE IF NOT EXISTS poultry_ecommerce
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE poultry_ecommerce;

-- Users (for login) - phone/ID is exactly 10 digits
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone VARCHAR(10) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- NOTE: For demo, insert a user manually from phpMyAdmin using password_hash.
-- Or create your own users table entries after deploying.

-- Products (vaccines, layer meds, feed meds)
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(60) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  category ENUM('vaccines','layer_meds','feed_meds','water') NOT NULL,
  description TEXT NULL,
  logo_path VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;



-- Single inventory (no per-shop localization / chain)
CREATE TABLE IF NOT EXISTS inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL UNIQUE,
  qty INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_inventory_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- IoT evidence (single stream per product; demo readings)
CREATE TABLE IF NOT EXISTS iot_readings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  reading_type ENUM('cold_room','humidity','shelf_temp') NOT NULL,
  value DECIMAL(10,2) NOT NULL,
  status ENUM('OK','WARNING','CRITICAL') NOT NULL DEFAULT 'OK',
  source ENUM('seed','sensor','simulated','alert') NOT NULL DEFAULT 'sensor',
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_iot_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- QR verification tokens (demo)
CREATE TABLE IF NOT EXISTS qr_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  token VARCHAR(120) NOT NULL UNIQUE,
  product_id INT NOT NULL,
  batch_no VARCHAR(80) NOT NULL,
  iot_status ENUM('OK','WARNING','CRITICAL') NOT NULL DEFAULT 'OK',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_qr_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Cart (per user, stores selected products)
CREATE TABLE IF NOT EXISTS carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_product (user_id, product_id),
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed 100 products (include vaccines; antibiotics; disinfectants; supplements; etc.)

INSERT INTO products (sku, name, category, description) VALUES
  ('VCX-NEW-001','Newcastle Vaccine','vaccines','Live vaccine for Newcastle disease'),
  ('VCX-GUMB-001','Gumboro Vaccine','vaccines','Infectious bursal disease vaccine'),
  ('VCX-IBD-002','IBD Vaccine (Intermediate)','vaccines','Demo IBD vaccine'),
  ('VCX-ND-003','ND Vaccine (LaSota)','vaccines','Demo Newcastle vaccine'),
  ('VCX-REV-004','Reovirus Vaccine','vaccines','Demo reovirus vaccine'),
  ('VCX-H5-005','Avian Influenza (H5) Vaccine','vaccines','Demo AI vaccine (traceability only)'),

  ('MED-OXY-001','Oxy-Tyl 200 (Oral)','layer_meds','Layer antibiotic (demo oral)'),
  ('MED-LEV-002','Levo-Tyl 10% (Oral)','layer_meds','Tylosin/levamisole style (demo)'),

  ('FMD-CHL-001','Chlorine Tabs','feed_meds','Disinfectant chlorine tablets (demo)'),
  ('FMD-TAX-001','Taximbinder','feed_meds','Mycotoxin binder (demo)'),

  ('MED-AMPROLIUM-001','Amprolium','layer_meds','Coccidiostat Amprolium (demo)'),
  ('MED-FENBENDAZOLE-001','Fenbendazole','layer_meds','Anthelmintic Fenbendazole (demo)'),
  ('MED-HYGROMYCINB-001','Hygromycin B','layer_meds','Antibiotic Hygromycin B (demo)'),
  ('MED-BMD-001','Bacitracin Methylene Disalicylate (BMD)','layer_meds','Bacitracin Methylene Disalicylate (BMD) (demo)'),
  ('MED-CTC-001','Chlortetracycline (CTC)','layer_meds','Chlortetracycline (CTC) (demo)'),

  ('FEED-AD3E-001','Vitamin AD3E','feed_meds','Vitamin AD3E (seeded)'),
  ('FEED-B-COMP-001','Vitamin B-Complex','feed_meds','Vitamin B-Complex (seeded)'),
  ('FEED-C-SUP-001','Vitamin C Supplement','feed_meds','Vitamin C Supplement (seeded)'),
  ('FEED-ELECTRO-001','Electrolyte Powder','feed_meds','Electrolyte Powder (seeded)'),
  ('FEED-CALCIUM-001','Calcium Supplement','feed_meds','Calcium Supplement (seeded)'),
  ('FEED-LIVER-001','Liver Tonic','feed_meds','Liver Tonic (seeded)'),
  ('FEED-PROBIOTIC-001','Probiotic Powder','feed_meds','Probiotic Powder (seeded)'),

  ('WTR-CHL-TAB-001','Chlorine Tablets','water','Water sanitation chlorine tablets (demo)'),

  ('WTR-BLEACH-001','Bleaching Powder','water','Water sanitation bleaching powder (demo)'),
  ('WTR-SOD-HYPO-001','Sodium Hypochlorite','water','Water sanitation sodium hypochlorite (demo)'),
  ('WTR-H2O2-001','Hydrogen Peroxide','water','Water sanitation hydrogen peroxide (demo)'),
  ('WTR-IOD-001','Iodine Solution','water','Water sanitation iodine solution (demo)'),
  ('WTR-PERMANG-001','Potassium Permanganate','water','Water sanitation potassium permanganate (demo)'),
  ('WTR-CHLORINE-DIOX-001','Chlorine Dioxide','water','Water sanitation chlorine dioxide (demo)'),
  ('WTR-QAC-001','Quaternary Ammonium Compounds (QAC)','water','Water sanitation QAC (demo)'),
  ('WTR-GLUTAR-001','Glutaraldehyde','water','Water sanitation glutaraldehyde (demo)'),
  ('WTR-FORMALIN-001','Formalin','water','Water sanitation formalin (demo)')
ON DUPLICATE KEY UPDATE
  sku = sku;

-- Seed inventory (single)
-- Map via subqueries by sku to avoid relying on autoincrement ids
INSERT INTO inventory (product_id, qty)
SELECT id, 120 FROM products WHERE sku = 'VCX-NEW-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 60 FROM products WHERE sku = 'VCX-GUMB-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 40 FROM products WHERE sku = 'VCX-IBD-002'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 90 FROM products WHERE sku = 'VCX-ND-003'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 55 FROM products WHERE sku = 'VCX-REV-004'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 25 FROM products WHERE sku = 'VCX-H5-005'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 200 FROM products WHERE sku = 'MED-OXY-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 140 FROM products WHERE sku = 'MED-LEV-002'
ON DUPLICATE KEY UPDATE qty = qty;

-- Layer meds inventory for the additional products
INSERT INTO inventory (product_id, qty)
SELECT id, 100 FROM products WHERE sku = 'MED-AMPROLIUM-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 100 FROM products WHERE sku = 'MED-FENBENDAZOLE-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 100 FROM products WHERE sku = 'MED-HYGROMYCINB-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 100 FROM products WHERE sku = 'MED-BMD-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 100 FROM products WHERE sku = 'MED-CTC-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 500 FROM products WHERE sku = 'FMD-CHL-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 320 FROM products WHERE sku = 'FMD-TAX-001'
ON DUPLICATE KEY UPDATE qty = qty;

-- Seed feed_meds supplements inventory (single)
INSERT INTO inventory (product_id, qty)
SELECT id, 500 FROM products WHERE sku = 'FEED-AD3E-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 500 FROM products WHERE sku = 'FEED-B-COMP-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 500 FROM products WHERE sku = 'FEED-C-SUP-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 500 FROM products WHERE sku = 'FEED-ELECTRO-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 500 FROM products WHERE sku = 'FEED-CALCIUM-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 500 FROM products WHERE sku = 'FEED-LIVER-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 500 FROM products WHERE sku = 'FEED-PROBIOTIC-001'
ON DUPLICATE KEY UPDATE qty = qty;


INSERT INTO inventory (product_id, qty)
SELECT id, 500 FROM products WHERE sku = 'WTR-CHL-TAB-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 400 FROM products WHERE sku = 'WTR-BLEACH-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 400 FROM products WHERE sku = 'WTR-SOD-HYPO-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 250 FROM products WHERE sku = 'WTR-H2O2-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 300 FROM products WHERE sku = 'WTR-IOD-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 250 FROM products WHERE sku = 'WTR-PERMANG-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 300 FROM products WHERE sku = 'WTR-CHLORINE-DIOX-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 450 FROM products WHERE sku = 'WTR-QAC-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 350 FROM products WHERE sku = 'WTR-GLUTAR-001'
ON DUPLICATE KEY UPDATE qty = qty;

INSERT INTO inventory (product_id, qty)
SELECT id, 300 FROM products WHERE sku = 'WTR-FORMALIN-001'
ON DUPLICATE KEY UPDATE qty = qty;


-- Seed IoT readings (2 readings per product).
-- The NOT EXISTS guard keeps the seed idempotent: running the migrations
-- again never duplicates the demo rows. Source = 'seed' marks them as
-- historical sample data (a real device upload is stored as 'sensor').
INSERT INTO iot_readings (product_id, reading_type, value, status, source)
SELECT p.id, 'cold_room', 4.2, 'OK', 'seed' FROM products p
 WHERE p.sku = 'VCX-NEW-001'
   AND NOT EXISTS (SELECT 1 FROM iot_readings r WHERE r.product_id = p.id AND r.reading_type = 'cold_room' AND r.source = 'seed');

INSERT INTO iot_readings (product_id, reading_type, value, status, source)
SELECT p.id, 'humidity', 58, 'OK', 'seed' FROM products p
 WHERE p.sku = 'VCX-NEW-001'
   AND NOT EXISTS (SELECT 1 FROM iot_readings r WHERE r.product_id = p.id AND r.reading_type = 'humidity' AND r.source = 'seed');

INSERT INTO iot_readings (product_id, reading_type, value, status, source)
SELECT p.id, 'cold_room', 9.1, 'WARNING', 'seed' FROM products p
 WHERE p.sku = 'VCX-GUMB-001'
   AND NOT EXISTS (SELECT 1 FROM iot_readings r WHERE r.product_id = p.id AND r.reading_type = 'cold_room' AND r.source = 'seed');

INSERT INTO iot_readings (product_id, reading_type, value, status, source)
SELECT p.id, 'humidity', 62, 'OK', 'seed' FROM products p
 WHERE p.sku = 'VCX-GUMB-001'
   AND NOT EXISTS (SELECT 1 FROM iot_readings r WHERE r.product_id = p.id AND r.reading_type = 'humidity' AND r.source = 'seed');

-- Add a few more generic readings for other products
INSERT INTO iot_readings (product_id, reading_type, value, status, source)
SELECT p.id, 'shelf_temp', 7.0, 'OK', 'seed' FROM products p
 WHERE p.sku IN ('VCX-IBD-002','VCX-ND-003','VCX-REV-004','VCX-H5-005')
   AND NOT EXISTS (SELECT 1 FROM iot_readings r WHERE r.product_id = p.id AND r.reading_type = 'shelf_temp' AND r.source = 'seed');

INSERT INTO iot_readings (product_id, reading_type, value, status, source)
SELECT p.id, 'cold_room', 3.6, 'OK', 'seed' FROM products p
 WHERE p.sku IN ('MED-OXY-001','MED-LEV-002')
   AND NOT EXISTS (SELECT 1 FROM iot_readings r WHERE r.product_id = p.id AND r.reading_type = 'cold_room' AND r.source = 'seed');

INSERT INTO iot_readings (product_id, reading_type, value, status, source)
SELECT p.id, 'humidity', 45, 'OK', 'seed' FROM products p
 WHERE p.sku IN ('FMD-CHL-001','FMD-TAX-001')
   AND NOT EXISTS (SELECT 1 FROM iot_readings r WHERE r.product_id = p.id AND r.reading_type = 'humidity' AND r.source = 'seed');

-- Seed IoT readings (demo) for water sanitation products
INSERT INTO iot_readings (product_id, reading_type, value, status, source)
SELECT p.id, 'cold_room', 5.0, 'OK', 'seed' FROM products p
 WHERE p.sku IN ('WTR-CHL-TAB-001','WTR-BLEACH-001','WTR-SOD-HYPO-001','WTR-H2O2-001')
   AND NOT EXISTS (SELECT 1 FROM iot_readings r WHERE r.product_id = p.id AND r.reading_type = 'cold_room' AND r.source = 'seed');

INSERT INTO iot_readings (product_id, reading_type, value, status, source)
SELECT p.id, 'humidity', 55, 'OK', 'seed' FROM products p
 WHERE p.sku IN ('WTR-IOD-001','WTR-PERMANG-001','WTR-CHLORINE-DIOX-001')
   AND NOT EXISTS (SELECT 1 FROM iot_readings r WHERE r.product_id = p.id AND r.reading_type = 'humidity' AND r.source = 'seed');

INSERT INTO iot_readings (product_id, reading_type, value, status, source)
SELECT p.id, 'shelf_temp', 6.5, 'WARNING', 'seed' FROM products p
 WHERE p.sku IN ('WTR-QAC-001','WTR-GLUTAR-001','WTR-FORMALIN-001')
   AND NOT EXISTS (SELECT 1 FROM iot_readings r WHERE r.product_id = p.id AND r.reading_type = 'shelf_temp' AND r.source = 'seed');

-- Seed QR tokens for products (token format still VCX-NEW-001|BATCH-xxxx)

INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
SELECT 'VCX-NEW-001|BATCH-7A92', id, 'BATCH-7A92', 'OK' FROM products WHERE sku = 'VCX-NEW-001'
ON DUPLICATE KEY UPDATE token = token;

INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
SELECT 'VCX-GUMB-001|BATCH-4P18', id, 'BATCH-4P18', 'WARNING' FROM products WHERE sku = 'VCX-GUMB-001'
ON DUPLICATE KEY UPDATE token = token;

INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
SELECT 'VCX-IBD-002|BATCH-IBD1', id, 'IBD1', 'OK' FROM products WHERE sku = 'VCX-IBD-002'
ON DUPLICATE KEY UPDATE token = token;

INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
SELECT 'VCX-ND-003|BATCH-ND1', id, 'ND1', 'OK' FROM products WHERE sku = 'VCX-ND-003'
ON DUPLICATE KEY UPDATE token = token;

INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
SELECT 'VCX-REV-004|BATCH-REV1', id, 'REV1', 'OK' FROM products WHERE sku = 'VCX-REV-004'
ON DUPLICATE KEY UPDATE token = token;

INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
SELECT 'VCX-H5-005|BATCH-H51', id, 'H51', 'WARNING' FROM products WHERE sku = 'VCX-H5-005'
ON DUPLICATE KEY UPDATE token = token;

INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
SELECT 'MED-OXY-001|BATCH-LMD-11C', id, 'LMD-11C', 'OK' FROM products WHERE sku = 'MED-OXY-001'
ON DUPLICATE KEY UPDATE token = token;

INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
SELECT 'MED-LEV-002|BATCH-LMD-12D', id, 'LMD-12D', 'OK' FROM products WHERE sku = 'MED-LEV-002'
ON DUPLICATE KEY UPDATE token = token;

INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
SELECT 'FMD-CHL-001|BATCH-FMD-2X1', id, 'FMD-2X1', 'OK' FROM products WHERE sku = 'FMD-CHL-001'
ON DUPLICATE KEY UPDATE token = token;

INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
SELECT 'FMD-TAX-001|BATCH-TXB-001', id, 'TXB-001', 'OK' FROM products WHERE sku = 'FMD-TAX-001'
ON DUPLICATE KEY UPDATE token = token;

