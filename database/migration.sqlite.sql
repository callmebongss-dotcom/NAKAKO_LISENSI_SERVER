CREATE TABLE IF NOT EXISTS licenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_key TEXT UNIQUE,
    device_id TEXT,
    business_name TEXT NOT NULL,
    owner_name TEXT NOT NULL,
    phone_number TEXT NOT NULL,
    email TEXT,
    city TEXT NOT NULL,
    license_status TEXT NOT NULL DEFAULT 'PENDING',
    license_type TEXT NOT NULL DEFAULT 'TRIAL',
    device_fingerprint TEXT DEFAULT NULL,
    device_name TEXT DEFAULT NULL,
    platform TEXT DEFAULT NULL,
    app_version TEXT DEFAULT NULL,
    last_online DATETIME DEFAULT NULL,
    clone_detected INTEGER DEFAULT 0,
    plan_id INTEGER DEFAULT NULL,
    license_expired DATETIME DEFAULT NULL,
    offline_expired DATETIME DEFAULT NULL,
    last_sync DATETIME DEFAULT NULL,
    purchase_price REAL DEFAULT 0,
    purchase_date DATETIME DEFAULT NULL,
    activation_date DATETIME DEFAULT NULL,
    blocked_date DATETIME DEFAULT NULL,
    blocked_reason TEXT DEFAULT NULL,
    transfer_count INTEGER DEFAULT 0,
    max_transfer INTEGER DEFAULT 3,
    product_key TEXT DEFAULT NULL,
    product_key_id INTEGER DEFAULT NULL,
    major_version TEXT DEFAULT '1',
    minor_version TEXT DEFAULT '0',
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_by TEXT,
    remarks TEXT
);

CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    name TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS device_transfer_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_id INTEGER NOT NULL,
    old_fingerprint TEXT DEFAULT NULL,
    new_fingerprint TEXT DEFAULT NULL,
    old_device_id TEXT DEFAULT NULL,
    new_device_id TEXT DEFAULT NULL,
    admin_name TEXT NOT NULL,
    reason TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id)
);

CREATE TABLE IF NOT EXISTS license_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plan_name TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    max_tv INTEGER DEFAULT 0,
    offline_days INTEGER DEFAULT 30,
    license_duration_days INTEGER DEFAULT 365,
    allow_transfer INTEGER DEFAULT 0,
    max_transfer INTEGER DEFAULT 0,
    allow_remote_disable INTEGER DEFAULT 0,
    allow_remote_update INTEGER DEFAULT 0,
    priority_support INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT OR IGNORE INTO admins (username, password, name) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin NAKAKO');

INSERT OR IGNORE INTO license_plans (id, plan_name, description, max_tv, offline_days, license_duration_days, allow_transfer, max_transfer, allow_remote_disable, allow_remote_update, priority_support, is_active) VALUES
(1, 'TRIAL', 'Masa percobaan terbatas', 2, 3, 7, 0, 0, 1, 1, 0, 1),
(2, 'BASIC', 'Paket dasar untuk rental kecil', 4, 7, 30, 1, 1, 0, 0, 0, 1),
(3, 'STANDARD', 'Paket standar rental menengah', 8, 14, 365, 1, 2, 0, 0, 1, 1),
(4, 'PRO', 'Paket professional fitur lengkap', 16, 30, 365, 1, 3, 0, 0, 1, 1),
(5, 'LIFETIME', 'Akses seumur hidup', 999, 60, 99999, 1, 999, 0, 0, 1, 1);

CREATE TABLE IF NOT EXISTS license_commands (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_id INTEGER NOT NULL,
    command TEXT NOT NULL,
    payload TEXT DEFAULT NULL,
    status TEXT NOT NULL DEFAULT 'PENDING',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    executed_at DATETIME DEFAULT NULL,
    result TEXT DEFAULT NULL,
    created_by TEXT NOT NULL,
    FOREIGN KEY (license_id) REFERENCES licenses(id)
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action TEXT NOT NULL,
    admin_name TEXT NOT NULL,
    license_id INTEGER DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address TEXT DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscription_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price REAL NOT NULL DEFAULT 0,
    duration_days INTEGER NOT NULL DEFAULT 30,
    offline_days INTEGER NOT NULL DEFAULT 7,
    max_tv INTEGER DEFAULT 2,
    max_user INTEGER DEFAULT 1,
    feature_flags TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status TEXT NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'PENDING',
    start_date DATETIME DEFAULT NULL,
    end_date DATETIME DEFAULT NULL,
    grace_end_date DATETIME DEFAULT NULL,
    auto_renew INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id),
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number TEXT NOT NULL UNIQUE,
    subscription_id INTEGER DEFAULT NULL,
    license_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    tax REAL DEFAULT 0,
    total REAL NOT NULL,
    status TEXT NOT NULL DEFAULT 'UNPAID',
    due_date DATETIME DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id),
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

CREATE TABLE IF NOT EXISTS payment_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL,
    subscription_id INTEGER DEFAULT NULL,
    license_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    provider TEXT DEFAULT NULL,
    provider_reference TEXT DEFAULT NULL,
    status TEXT NOT NULL DEFAULT 'PENDING',
    payment_method TEXT DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    raw_response TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);

INSERT OR IGNORE INTO subscription_plans (id, name, price, duration_days, offline_days, max_tv, max_user, feature_flags, description, status) VALUES
(1, 'TRIAL', 0, 7, 3, 2, 1, '{"remote_disable":true,"remote_update":true}', 'Masa percobaan terbatas', 'ACTIVE'),
(2, 'BASIC', 499000, 365, 7, 4, 1, '{"remote_disable":false,"remote_update":false}', 'Paket dasar untuk rental kecil', 'ACTIVE'),
(3, 'PRO', 999000, 365, 30, 16, 2, '{"remote_disable":true,"remote_update":true}', 'Paket professional fitur lengkap', 'ACTIVE'),
(4, 'ENTERPRISE', 0, 99999, 60, 999, 999, '{"remote_disable":true,"remote_update":true,"priority_support":true}', 'Custom enterprise solution', 'ACTIVE');

CREATE TABLE IF NOT EXISTS history_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    admin_name TEXT DEFAULT NULL,
    ip_address TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id)
);

CREATE TABLE IF NOT EXISTS license_certificates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    certificate_number TEXT NOT NULL UNIQUE,
    license_id INTEGER NOT NULL,
    license_key TEXT DEFAULT NULL,
    product_key TEXT DEFAULT NULL,
    business_name TEXT DEFAULT NULL,
    owner_name TEXT DEFAULT NULL,
    phone_number TEXT DEFAULT NULL,
    device_id TEXT DEFAULT NULL,
    license_type TEXT DEFAULT NULL,
    activation_date DATETIME DEFAULT NULL,
    signature_hash TEXT DEFAULT NULL,
    qr_data TEXT DEFAULT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    generated_by TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id)
);

CREATE TABLE IF NOT EXISTS app_updates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    version TEXT NOT NULL,
    release_notes TEXT DEFAULT NULL,
    file_url TEXT NOT NULL,
    file_size INTEGER DEFAULT 0,
    platform TEXT NOT NULL DEFAULT 'all',
    is_forced INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS license_packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    price REAL NOT NULL DEFAULT 0,
    duration_days INTEGER NOT NULL DEFAULT 30,
    max_devices INTEGER NOT NULL DEFAULT 1,
    features TEXT DEFAULT '{}',
    status TEXT NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT OR IGNORE INTO license_packages (id, name, description, price, duration_days, max_devices, features, status) VALUES
(1, 'TRIAL', 'Coba gratis selama 3 hari, maksimal 1 perangkat', 0, 3, 1, '{"remote_disable":true,"remote_update":true,"priority_support":false}', 'ACTIVE'),
(2, 'STANDARD', 'Paket standar untuk rental kecil, cocok untuk 1-2 perangkat', 200000, 30, 2, '{"remote_disable":true,"remote_update":true,"priority_support":false}', 'ACTIVE'),
(3, 'PREMIUM', 'Paket lengkap untuk rental menengah, support prioritas', 350000, 90, 4, '{"remote_disable":true,"remote_update":true,"priority_support":true}', 'ACTIVE'),
(4, 'ENTERPRISE', 'Paket unlimited, maksimal perangkat tidak terbatas', 500000, 365, 999, '{"remote_disable":true,"remote_update":true,"priority_support":true}', 'ACTIVE');

CREATE TABLE IF NOT EXISTS product_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_key TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'UNUSED',
    license_type TEXT DEFAULT 'LIFETIME',
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    activated_at DATETIME DEFAULT NULL,
    license_id INTEGER DEFAULT NULL,
    generated_by TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id)
);
