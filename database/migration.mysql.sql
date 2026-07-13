CREATE TABLE IF NOT EXISTS licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(255) UNIQUE,
    device_id VARCHAR(255),
    business_name VARCHAR(255) NOT NULL,
    owner_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    city VARCHAR(255) NOT NULL,
    license_status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    license_type VARCHAR(50) NOT NULL DEFAULT 'TRIAL',
    device_fingerprint TEXT,
    device_name TEXT,
    platform VARCHAR(50),
    app_version VARCHAR(50),
    last_online DATETIME,
    clone_detected INT DEFAULT 0,
    plan_id INT,
    license_expired DATETIME,
    offline_expired DATETIME,
    last_sync DATETIME,
    purchase_price DECIMAL(15,2) DEFAULT 0,
    purchase_date DATETIME,
    activation_date DATETIME,
    blocked_date DATETIME,
    blocked_reason TEXT,
    transfer_count INT DEFAULT 0,
    max_transfer INT DEFAULT 3,
    product_key VARCHAR(255) DEFAULT NULL,
    product_key_id INT,
    major_version VARCHAR(10) DEFAULT '1',
    minor_version VARCHAR(10) DEFAULT '0',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_by VARCHAR(255),
    remarks TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS device_transfer_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    old_fingerprint TEXT,
    new_fingerprint TEXT,
    old_device_id VARCHAR(255),
    new_device_id VARCHAR(255),
    admin_name VARCHAR(255) NOT NULL,
    reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS license_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(255) NOT NULL,
    description TEXT,
    max_tv INT DEFAULT 0,
    offline_days INT DEFAULT 30,
    license_duration_days INT DEFAULT 365,
    allow_transfer INT DEFAULT 0,
    max_transfer INT DEFAULT 0,
    allow_remote_disable INT DEFAULT 0,
    allow_remote_update INT DEFAULT 0,
    priority_support INT DEFAULT 0,
    is_active INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO admins (username, password, name) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin NAKAKO');

INSERT IGNORE INTO license_plans (id, plan_name, description, max_tv, offline_days, license_duration_days, allow_transfer, max_transfer, allow_remote_disable, allow_remote_update, priority_support, is_active) VALUES
(1, 'TRIAL', 'Masa percobaan terbatas', 2, 3, 7, 0, 0, 1, 1, 0, 1),
(2, 'BASIC', 'Paket dasar untuk rental kecil', 4, 7, 30, 1, 1, 0, 0, 0, 1),
(3, 'STANDARD', 'Paket standar rental menengah', 8, 14, 365, 1, 2, 0, 0, 1, 1),
(4, 'PRO', 'Paket professional fitur lengkap', 16, 30, 365, 1, 3, 0, 0, 1, 1),
(5, 'LIFETIME', 'Akses seumur hidup', 999, 60, 99999, 1, 999, 0, 0, 1, 1);

CREATE TABLE IF NOT EXISTS license_commands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    command VARCHAR(255) NOT NULL,
    payload TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    executed_at DATETIME,
    result TEXT,
    created_by VARCHAR(255) NOT NULL,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(255) NOT NULL,
    admin_name VARCHAR(255) NOT NULL,
    license_id INT,
    details TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(15,2) NOT NULL DEFAULT 0,
    duration_days INT NOT NULL DEFAULT 30,
    offline_days INT NOT NULL DEFAULT 7,
    max_tv INT DEFAULT 2,
    max_user INT DEFAULT 1,
    feature_flags TEXT,
    description TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    plan_id INT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    start_date DATETIME,
    end_date DATETIME,
    grace_end_date DATETIME,
    auto_renew INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(255) NOT NULL UNIQUE,
    subscription_id INT,
    license_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    tax DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'UNPAID',
    due_date DATETIME,
    paid_at DATETIME,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    subscription_id INT,
    license_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    provider VARCHAR(255),
    provider_reference VARCHAR(255),
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    payment_method VARCHAR(255),
    paid_at DATETIME,
    raw_response TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO subscription_plans (id, name, price, duration_days, offline_days, max_tv, max_user, feature_flags, description, status) VALUES
(1, 'TRIAL', 0, 7, 3, 2, 1, '{"remote_disable":true,"remote_update":true}', 'Masa percobaan terbatas', 'ACTIVE'),
(2, 'BASIC', 499000, 365, 7, 4, 1, '{"remote_disable":false,"remote_update":false}', 'Paket dasar untuk rental kecil', 'ACTIVE'),
(3, 'PRO', 999000, 365, 30, 16, 2, '{"remote_disable":true,"remote_update":true}', 'Paket professional fitur lengkap', 'ACTIVE'),
(4, 'ENTERPRISE', 0, 99999, 60, 999, 999, '{"remote_disable":true,"remote_update":true,"priority_support":true}', 'Custom enterprise solution', 'ACTIVE');

CREATE TABLE IF NOT EXISTS history_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    old_value TEXT,
    new_value TEXT,
    admin_name VARCHAR(255),
    ip_address VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS license_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_number VARCHAR(255) NOT NULL UNIQUE,
    license_id INT NOT NULL,
    license_key VARCHAR(255),
    product_key VARCHAR(255),
    business_name VARCHAR(255),
    owner_name VARCHAR(255),
    phone_number VARCHAR(255),
    device_id VARCHAR(255),
    license_type VARCHAR(50),
    activation_date DATETIME,
    signature_hash TEXT,
    qr_data TEXT,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    generated_by VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS app_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(50) NOT NULL,
    release_notes TEXT,
    file_url TEXT NOT NULL,
    file_size INT DEFAULT 0,
    platform VARCHAR(50) NOT NULL DEFAULT 'all',
    is_forced INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS license_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(15,2) NOT NULL DEFAULT 0,
    duration_days INT NOT NULL DEFAULT 30,
    max_devices INT NOT NULL DEFAULT 1,
    features TEXT DEFAULT '{}',
    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO license_packages (id, name, description, price, duration_days, max_devices, features, status) VALUES
(1, 'TRIAL', 'Coba gratis selama 3 hari, maksimal 1 perangkat', 0, 3, 1, '{"remote_disable":true,"remote_update":true,"priority_support":false}', 'ACTIVE'),
(2, 'STANDARD', 'Paket standar untuk rental kecil, cocok untuk 1-2 perangkat', 200000, 30, 2, '{"remote_disable":true,"remote_update":true,"priority_support":false}', 'ACTIVE'),
(3, 'PREMIUM', 'Paket lengkap untuk rental menengah, support prioritas', 350000, 90, 4, '{"remote_disable":true,"remote_update":true,"priority_support":true}', 'ACTIVE'),
(4, 'ENTERPRISE', 'Paket unlimited, maksimal perangkat tidak terbatas', 500000, 365, 999, '{"remote_disable":true,"remote_update":true,"priority_support":true}', 'ACTIVE');

CREATE TABLE IF NOT EXISTS product_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_key VARCHAR(255) NOT NULL UNIQUE,
    status VARCHAR(50) NOT NULL DEFAULT 'UNUSED',
    license_type VARCHAR(50) DEFAULT 'LIFETIME',
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    activated_at DATETIME,
    license_id INT,
    generated_by VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
