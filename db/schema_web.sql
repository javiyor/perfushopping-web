-- Web-only tables for Perfushopping (do not modify existing ERP tables)

CREATE TABLE IF NOT EXISTS web_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  phone_key VARCHAR(24) DEFAULT NULL,
  address VARCHAR(190) DEFAULT NULL,
  addr_key VARCHAR(220) DEFAULT NULL,
  city VARCHAR(120) DEFAULT NULL,
  city_key VARCHAR(140) DEFAULT NULL,
  postal_code VARCHAR(20) DEFAULT NULL,
  province_codprov INT UNSIGNED DEFAULT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  wholesale_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
  customer_category VARCHAR(40) NOT NULL DEFAULT 'none',
  cliente_id INT UNSIGNED DEFAULT NULL,
  affiliate_referrer_user_id INT UNSIGNED DEFAULT NULL,
  affiliate_assigned_at DATETIME DEFAULT NULL,
  email_verified_at DATETIME DEFAULT NULL,
  disabled_at DATETIME DEFAULT NULL,
  force_password_change TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  last_login_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_web_users_email (email),
  KEY idx_web_users_aff_ref (affiliate_referrer_user_id),
  CONSTRAINT fk_web_users_aff_ref FOREIGN KEY (affiliate_referrer_user_id) REFERENCES web_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Optional: bootstrap an affiliate row for every user (app can also create on-demand).

CREATE TABLE IF NOT EXISTS web_user_tokens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  token_type ENUM('activate','reset') NOT NULL,
  created_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_user_tokens_user (user_id),
  UNIQUE KEY uq_user_tokens_hash (token_hash),
  CONSTRAINT fk_user_tokens_user FOREIGN KEY (user_id) REFERENCES web_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wholesale_requests (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  razon_social VARCHAR(150) NOT NULL,
  cuit VARCHAR(20) NOT NULL,
  address VARCHAR(190) NOT NULL,
  city VARCHAR(120) NOT NULL,
  postal_code VARCHAR(20) NOT NULL,
  province_codprov INT UNSIGNED NOT NULL,
  customer_category VARCHAR(40) NOT NULL DEFAULT 'none',
  notes VARCHAR(500) DEFAULT NULL,
  submitted_at DATETIME NOT NULL,
  reviewed_by INT UNSIGNED DEFAULT NULL,
  reviewed_at DATETIME DEFAULT NULL,
  decision ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  decision_notes VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_wh_req_user (user_id),
  KEY idx_wh_req_decision (decision),
  CONSTRAINT fk_wh_req_user FOREIGN KEY (user_id) REFERENCES web_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_code CHAR(16) NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  customer_type ENUM('retail','wholesale') NOT NULL,
  status ENUM('draft','pending_payment','paid','cancelled','pending_transfer','transfer_reported') NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  ship_name VARCHAR(120) NOT NULL,
  ship_address VARCHAR(190) NOT NULL,
  ship_city VARCHAR(120) NOT NULL,
  ship_postal_code VARCHAR(20) NOT NULL,
  ship_province_codprov INT UNSIGNED NOT NULL,
  ship_cod_lugar INT UNSIGNED DEFAULT NULL,
  ship_province_name VARCHAR(60) NOT NULL,
  shipping_method ENUM('local_delivery','correo_argentino') NOT NULL,
  shipping_detail VARCHAR(120) DEFAULT NULL,
  shipping_cost_cents INT NOT NULL DEFAULT 0,
  subtotal_net_cents INT NOT NULL,
  discount_percent DECIMAL(7,2) NOT NULL DEFAULT 0.00,
  discount_cents INT NOT NULL DEFAULT 0,
  iva_cents INT NOT NULL,
  total_cents INT NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'ARS',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_code (order_code),
  KEY idx_orders_user (user_id),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES web_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  idprodu INT UNSIGNED NOT NULL,
  idcodgusto INT UNSIGNED NOT NULL,
  product_name VARCHAR(220) NOT NULL,
  variant_name VARCHAR(60) NOT NULL,
  qty INT NOT NULL,
  unit_net_cents INT NOT NULL,
  iva_rate DECIMAL(7,2) NOT NULL,
  line_net_cents INT NOT NULL,
  line_iva_cents INT NOT NULL,
  line_total_cents INT NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mp_payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  preference_id VARCHAR(120) DEFAULT NULL,
  payment_id BIGINT UNSIGNED DEFAULT NULL,
  status VARCHAR(40) DEFAULT NULL,
  status_detail VARCHAR(120) DEFAULT NULL,
  raw_json MEDIUMTEXT DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mp_payment_id (payment_id),
  KEY idx_mp_order (order_id),
  CONSTRAINT fk_mp_order FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mp_webhook_events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_key VARCHAR(190) NOT NULL,
  topic VARCHAR(60) DEFAULT NULL,
  payload MEDIUMTEXT NOT NULL,
  received_at DATETIME NOT NULL,
  processed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mp_event_key (event_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS local_delivery_localities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  locality_key VARCHAR(120) NOT NULL,
  display_name VARCHAR(120) NOT NULL,
  province_codprov INT UNSIGNED NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ld_locality_key (locality_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS local_delivery_prices (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  locality_key VARCHAR(120) NOT NULL,
  price_cents INT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ld_price_key (locality_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo tecnica (lunes): registro de profesionales y clientes
CREATE TABLE IF NOT EXISTS demo_tech_registrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED DEFAULT NULL,
  kind ENUM('pro','client') NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  city VARCHAR(120) DEFAULT NULL,
  province VARCHAR(80) DEFAULT NULL,
  salon_name VARCHAR(160) DEFAULT NULL,
  salon_address VARCHAR(190) DEFAULT NULL,
  monday_date DATE NOT NULL,
  attendees INT UNSIGNED DEFAULT NULL,
  notes VARCHAR(600) DEFAULT NULL,
  status ENUM('new','contacted','confirmed','cancelled') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL,
  ip VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_demo_event (event_id),
  KEY idx_demo_kind_date (kind, monday_date),
  KEY idx_demo_status_date (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS demo_tech_events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  monday_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  venue_name VARCHAR(160) NOT NULL,
  venue_address VARCHAR(190) DEFAULT NULL,
  capacity INT UNSIGNED NOT NULL,
  notes VARCHAR(400) DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_demo_event_date_time (monday_date, start_time, end_time),
  KEY idx_demo_event_active_date (active, monday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS correo_agencies (
  agency_id VARCHAR(20) NOT NULL,
  agency_name VARCHAR(190) DEFAULT NULL,
  state_id VARCHAR(5) DEFAULT NULL,
  state_name VARCHAR(80) DEFAULT NULL,
  city_name VARCHAR(120) DEFAULT NULL,
  street_name VARCHAR(120) DEFAULT NULL,
  street_number VARCHAR(30) DEFAULT NULL,
  zip_code VARCHAR(20) DEFAULT NULL,
  phone VARCHAR(60) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  schedule VARCHAR(190) DEFAULT NULL,
  pickup_availability TINYINT(1) NOT NULL DEFAULT 0,
  package_reception TINYINT(1) NOT NULL DEFAULT 0,
  raw_json TEXT DEFAULT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (agency_id),
  KEY idx_correo_agency_state (state_id, city_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
