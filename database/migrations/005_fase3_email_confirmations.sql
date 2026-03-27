CREATE TABLE IF NOT EXISTS email_verifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  email VARCHAR(254) NOT NULL,
  token CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_email_verifications_token (token),
  KEY idx_email_verifications_user (user_id),
  CONSTRAINT fk_email_verifications_user
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE usuarios
  ADD COLUMN email_verificado_em DATETIME NULL DEFAULT NULL AFTER email;

UPDATE usuarios
SET email_verificado_em = COALESCE(email_verificado_em, NOW())
WHERE email_verificado_em IS NULL;
