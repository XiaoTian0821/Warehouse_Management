CREATE DATABASE IF NOT EXISTS warehouse_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE warehouse_management;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    item_name VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    unit VARCHAR(20) NOT NULL DEFAULT 'pcs',
    reorder_level INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_item_code (item_code),
    INDEX idx_item_name (item_name),
    INDEX idx_category (category),
    INDEX idx_location (location),
    INDEX idx_quantity (quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    movement_type ENUM('IN', 'OUT') NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    movement_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reference_note VARCHAR(255) NULL DEFAULT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movements_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_movements_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_movements_item (item_id),
    INDEX idx_movements_type (movement_type),
    INDEX idx_movements_date (movement_date),
    INDEX idx_movements_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (full_name, username, password_hash, role, is_active)
VALUES (
    'Administrator',
    'admin',
    '$2a$12$tVILpY3Om8vFZS5tIts4dewixFnXn1vnjTNIL3wuR0YcjKl2Mpzdu',
    'admin',
    1
);

INSERT INTO users (full_name, username, password_hash, role, is_active)
VALUES (
    'Demo User',
    'demo',
    '$2a$12$UzVeGIeax/X8LHGI.B/LCuMYZSGCfOw95p0Rw.8y7wWyo2/WmEMt6',
    'user',
    1
);

INSERT INTO items (item_code, item_name, category, location, quantity, unit, reorder_level) VALUES
('ITM-001', 'Wireless Mouse', 'Electronics', 'Shelf A-1', 45, 'pcs', 10),
('ITM-002', 'USB-C Cable', 'Electronics', 'Shelf A-2', 8, 'pcs', 20),
('ITM-003', 'Notebook A5', 'Stationery', 'Shelf B-1', 150, 'pcs', 30),
('ITM-004', 'Ballpoint Pen', 'Stationery', 'Shelf B-2', 5, 'pcs', 50),
('ITM-005', 'Desk Lamp', 'Furniture', 'Shelf C-1', 12, 'pcs', 5);
