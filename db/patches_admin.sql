CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(190),
  rol ENUM('superadmin','ventas','administracion','compras','caja') NOT NULL DEFAULT 'ventas',
  activo TINYINT(1) DEFAULT 1,
  last_login_at DATETIME,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proveedores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  razon_social VARCHAR(200) NOT NULL,
  cuit VARCHAR(20),
  contacto VARCHAR(120),
  telefono VARCHAR(40),
  email VARCHAR(190),
  direccion VARCHAR(190),
  activo TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departamentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255),
  activo TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producto_admin (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idprodu INT UNSIGNED NOT NULL UNIQUE,
  id_proveedor INT UNSIGNED,
  id_departamento INT UNSIGNED,
  costo_cents INT DEFAULT 0,
  observaciones TEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (idprodu) REFERENCES producto(idprodu) ON DELETE CASCADE,
  FOREIGN KEY (id_proveedor) REFERENCES proveedores(id) ON DELETE SET NULL,
  FOREIGN KEY (id_departamento) REFERENCES departamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
