-- ============================================================
-- Migration v5: WebAuthn / Passkey credentials table
-- ============================================================

USE poultry_ecommerce;

CREATE TABLE IF NOT EXISTS webauthn_credentials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  credential_id VARBINARY(255) NOT NULL,
  public_key VARBINARY(255),
  counter BIGINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_webauthn_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_credential_id (credential_id)
) ENGINE=InnoDB;
