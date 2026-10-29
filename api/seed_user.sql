-- Demo user seeding helper.
-- Create a password hash using PHP and insert it.
-- Example (run once in a temporary PHP file):
-- <?php echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT); ?>
-- Then paste the resulting hash into the INSERT below.

-- Replace:
--   1) YOUR_PHONE (10 digits)
--   2) PASSWORD_HASH (from password_hash())

USE poultry_ecommerce;

INSERT INTO users (phone, password_hash)
VALUES ('1234567890', 'BUNNY')
ON DUPLICATE KEY UPDATE
  password_hash = VALUES(password_hash);

