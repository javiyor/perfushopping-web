-- Affiliate program patches (run once on existing installations)

-- Extend web_users with affiliate + profile fields
ALTER TABLE web_users
  ADD COLUMN phone_key VARCHAR(24) DEFAULT NULL,
  ADD COLUMN address VARCHAR(190) DEFAULT NULL,
  ADD COLUMN addr_key VARCHAR(220) DEFAULT NULL,
  ADD COLUMN city VARCHAR(120) DEFAULT NULL,
  ADD COLUMN city_key VARCHAR(140) DEFAULT NULL,
  ADD COLUMN postal_code VARCHAR(20) DEFAULT NULL,
  ADD COLUMN province_codprov INT UNSIGNED DEFAULT NULL,
  ADD COLUMN affiliate_referrer_user_id INT UNSIGNED DEFAULT NULL,
  ADD COLUMN affiliate_assigned_at DATETIME DEFAULT NULL,
  ADD KEY idx_web_users_aff_ref (affiliate_referrer_user_id);

ALTER TABLE web_users
  ADD CONSTRAINT fk_web_users_aff_ref
  FOREIGN KEY (affiliate_referrer_user_id) REFERENCES web_users(id);

-- Create tables
CREATE TABLE IF NOT EXISTS affiliates (
  user_id INT UNSIGNED NOT NULL,
  ref_code VARCHAR(24) NOT NULL,
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_affiliates_ref_code (ref_code),
  CONSTRAINT fk_affiliates_user FOREIGN KEY (user_id) REFERENCES web_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_ledger (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  affiliate_user_id INT UNSIGNED NOT NULL,
  type ENUM('commission_earn','commission_revoke','commission_blocked','spend_on_order','withdraw_request','withdraw_paid','adjustment') NOT NULL,
  amount_cents INT NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'ARS',
  order_id INT UNSIGNED DEFAULT NULL,
  status ENUM('pending','available') NOT NULL DEFAULT 'available',
  available_at DATETIME DEFAULT NULL,
  note VARCHAR(500) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_aff_ledger_commission_once (affiliate_user_id, order_id, type),
  KEY idx_aff_ledger_user_created (affiliate_user_id, created_at),
  KEY idx_aff_ledger_status_avail (status, available_at),
  CONSTRAINT fk_aff_ledger_user FOREIGN KEY (affiliate_user_id) REFERENCES web_users(id),
  CONSTRAINT fk_aff_ledger_order FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_withdrawals (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  affiliate_user_id INT UNSIGNED NOT NULL,
  credit_amount_cents INT NOT NULL,
  payout_amount_cents INT NOT NULL,
  destination VARCHAR(220) NOT NULL,
  status ENUM('requested','approved','paid','rejected') NOT NULL DEFAULT 'requested',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_aff_withdraw_user (affiliate_user_id, created_at),
  CONSTRAINT fk_aff_withdraw_user FOREIGN KEY (affiliate_user_id) REFERENCES web_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS terms_acceptances (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  terms_key VARCHAR(40) NOT NULL,
  accepted_at DATETIME NOT NULL,
  ip VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_terms_user_key (user_id, terms_key),
  KEY idx_terms_key_date (terms_key, accepted_at),
  CONSTRAINT fk_terms_user FOREIGN KEY (user_id) REFERENCES web_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
