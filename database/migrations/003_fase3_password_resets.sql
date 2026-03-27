CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  email VARCHAR(254) NOT NULL,
  token CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_password_resets_token (token),
  KEY idx_password_resets_user (user_id),
  KEY idx_password_resets_email (email),
  CONSTRAINT fk_password_resets_user
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
