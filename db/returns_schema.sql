-- Returns / dispatch tracking schema
-- Add this to your database (phpMyAdmin -> SQL tab)

USE poultry_ecommerce;

-- Persist each confirmed dispatched/delivered product per order
CREATE TABLE IF NOT EXISTS order_dispatch_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id VARCHAR(80) NOT NULL,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  delivery_address TEXT NOT NULL,
  status ENUM('DISPATCHED','DELIVERED','RETURN_REQUESTED','RETURNED') NOT NULL DEFAULT 'DISPATCHED',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_order_product (order_id, product_id),
  CONSTRAINT fk_od_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_od_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

