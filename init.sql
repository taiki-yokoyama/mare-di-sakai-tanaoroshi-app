SET NAMES utf8mb4;
SET time_zone = '+09:00';

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'staff',
    password_salt VARCHAR(64) NOT NULL,
    password_hash VARCHAR(128) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS remember_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_remember_tokens_hash (token_hash),
    KEY idx_remember_tokens_user_id (user_id),
    KEY idx_remember_tokens_expires_at (expires_at),
    CONSTRAINT fk_remember_tokens_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sku VARCHAR(64) NOT NULL,
    name VARCHAR(190) NOT NULL,
    barcode VARCHAR(128) NULL,
    unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
    current_stock_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_items_sku (sku),
    UNIQUE KEY uq_items_barcode (barcode),
    KEY idx_items_name (name),
    KEY idx_items_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(190) NOT NULL,
    location_name VARCHAR(190) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    memo TEXT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inventory_sessions_status (status),
    KEY idx_inventory_sessions_created_by (created_by_user_id),
    CONSTRAINT fk_inventory_sessions_user
        FOREIGN KEY (created_by_user_id) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    expected_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_stock_snapshots_session_item (session_id, item_id),
    KEY idx_stock_snapshots_session_id (session_id),
    KEY idx_stock_snapshots_item_id (item_id),
    CONSTRAINT fk_stock_snapshots_session
        FOREIGN KEY (session_id) REFERENCES inventory_sessions (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_stock_snapshots_item
        FOREIGN KEY (item_id) REFERENCES items (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_counts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    counted_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
    counted_by_user_id BIGINT UNSIGNED NOT NULL,
    counted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_inventory_counts_session_item (session_id, item_id),
    KEY idx_inventory_counts_session_id (session_id),
    KEY idx_inventory_counts_item_id (item_id),
    KEY idx_inventory_counts_counted_by (counted_by_user_id),
    CONSTRAINT fk_inventory_counts_session
        FOREIGN KEY (session_id) REFERENCES inventory_sessions (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_inventory_counts_item
        FOREIGN KEY (item_id) REFERENCES items (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_inventory_counts_user
        FOREIGN KEY (counted_by_user_id) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS adjustments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    expected_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
    counted_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
    difference_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_adjustments_session_item (session_id, item_id),
    KEY idx_adjustments_session_id (session_id),
    KEY idx_adjustments_item_id (item_id),
    CONSTRAINT fk_adjustments_session
        FOREIGN KEY (session_id) REFERENCES inventory_sessions (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_adjustments_item
        FOREIGN KEY (item_id) REFERENCES items (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    action VARCHAR(100) NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    subject_type VARCHAR(100) NOT NULL,
    subject_id BIGINT UNSIGNED NULL,
    payload_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_logs_actor_user_id (actor_user_id),
    KEY idx_audit_logs_subject_type (subject_type),
    KEY idx_audit_logs_subject_id (subject_id),
    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (actor_user_id) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, role, password_salt, password_hash, created_at, updated_at)
VALUES (
    'Admin',
    'admin@example.com',
    'admin',
    '9a3d1f6c8b4e2a0d7c1f3b5a6e8d0f11',
    '4d12c2c8a5a23254dc95cdf179e6e7c24d0143fd888156562470ee822034f260',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    role = VALUES(role),
    password_salt = VALUES(password_salt),
    password_hash = VALUES(password_hash),
    updated_at = VALUES(updated_at);

INSERT INTO items (sku, name, barcode, unit, current_stock_qty, is_active, created_at, updated_at)
VALUES
    ('SKU-001', 'Sample Apple', '490000000001', 'pcs', 12, 1, NOW(), NOW()),
    ('SKU-002', 'Sample Banana', '490000000002', 'pcs', 20, 1, NOW(), NOW()),
    ('SKU-003', 'Sample Water', '490000000003', 'bottle', 8, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    barcode = VALUES(barcode),
    unit = VALUES(unit),
    current_stock_qty = VALUES(current_stock_qty),
    is_active = VALUES(is_active),
    updated_at = VALUES(updated_at);
