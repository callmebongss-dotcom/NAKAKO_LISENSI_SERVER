<?php

namespace App\Infrastructure\Database;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $mysqlHost = getenv('MYSQL_HOST');
            if ($mysqlHost) {
                $port = getenv('MYSQL_PORT') ?: '3306';
                $dbName = getenv('MYSQL_DATABASE') ?: 'nakako_license';
                $user = getenv('MYSQL_USER') ?: 'root';
                $pass = getenv('MYSQL_PASSWORD') ?: '';
                $dsn = "mysql:host=$mysqlHost;port=$port;dbname=$dbName;charset=utf8mb4";
                try {
                    self::$instance = new PDO($dsn, $user, $pass);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
                    exit;
                }
            } else {
                // Fallback to SQLite for local development
                $dbPath = __DIR__ . '/../../../database/license.db';
                try {
                    self::$instance = new PDO("sqlite:$dbPath");
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->exec('PRAGMA journal_mode=WAL');
                    self::$instance->exec('PRAGMA foreign_keys=ON');
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database connection failed']);
                    exit;
                }
            }
        }
        return self::$instance;
    }

    public static function initialize(): void
    {
        $db = self::getConnection();
        $isMysql = getenv('MYSQL_HOST') ? true : false;

        if ($isMysql) {
            $sql = file_get_contents(__DIR__ . '/../../../database/migration.sql');
            $db->exec($sql);
        } else {
            // SQLite: use original migration
            $sql = file_get_contents(__DIR__ . '/../../../database/migration.sqlite.sql');
            $db->exec($sql);
        }

        // Safe migration: add columns if not exist
        try {
            if ($isMysql) {
                $columns = $db->query("SHOW COLUMNS FROM licenses")->fetchAll(PDO::FETCH_COLUMN, 0);
            } else {
                $columns = $db->query("PRAGMA table_info('licenses')")->fetchAll(PDO::FETCH_COLUMN, 1);
            }
        } catch (\Exception $e) {
            $columns = [];
        }
        $addCols = [
            'license_type' => $isMysql ? "ALTER TABLE licenses ADD COLUMN license_type VARCHAR(50) DEFAULT 'TRIAL'" : "ALTER TABLE licenses ADD COLUMN license_type TEXT DEFAULT 'TRIAL'",
            'purchase_price' => $isMysql ? 'ALTER TABLE licenses ADD COLUMN purchase_price DECIMAL(15,2) DEFAULT 0' : 'ALTER TABLE licenses ADD COLUMN purchase_price REAL DEFAULT 0',
            'purchase_date' => 'ALTER TABLE licenses ADD COLUMN purchase_date DATETIME DEFAULT NULL',
            'activation_date' => 'ALTER TABLE licenses ADD COLUMN activation_date DATETIME DEFAULT NULL',
            'blocked_date' => 'ALTER TABLE licenses ADD COLUMN blocked_date DATETIME DEFAULT NULL',
            'blocked_reason' => 'ALTER TABLE licenses ADD COLUMN blocked_reason TEXT DEFAULT NULL',
            'transfer_count' => $isMysql ? 'ALTER TABLE licenses ADD COLUMN transfer_count INT DEFAULT 0' : 'ALTER TABLE licenses ADD COLUMN transfer_count INTEGER DEFAULT 0',
            'max_transfer' => $isMysql ? 'ALTER TABLE licenses ADD COLUMN max_transfer INT DEFAULT 3' : 'ALTER TABLE licenses ADD COLUMN max_transfer INTEGER DEFAULT 3',
            'major_version' => $isMysql ? "ALTER TABLE licenses ADD COLUMN major_version VARCHAR(10) DEFAULT '1'" : "ALTER TABLE licenses ADD COLUMN major_version TEXT DEFAULT '1'",
            'minor_version' => $isMysql ? "ALTER TABLE licenses ADD COLUMN minor_version VARCHAR(10) DEFAULT '0'" : "ALTER TABLE licenses ADD COLUMN minor_version TEXT DEFAULT '0'",
            'notes' => 'ALTER TABLE licenses ADD COLUMN notes TEXT DEFAULT NULL',
        ];
        foreach ($addCols as $col => $stmt) {
            if (!in_array($col, $columns)) {
                try { $db->exec($stmt); } catch (\Exception $e) {}
            }
        }

        if (!in_array('product_key', $columns)) {
            try { $db->exec($isMysql ? "ALTER TABLE licenses ADD COLUMN product_key VARCHAR(255) DEFAULT NULL" : "ALTER TABLE licenses ADD COLUMN product_key TEXT DEFAULT NULL"); } catch (\Exception $e) {}
        }

        if (!in_array('product_key_id', $columns)) {
            try { $db->exec($isMysql ? "ALTER TABLE licenses ADD COLUMN product_key_id INT DEFAULT NULL" : "ALTER TABLE licenses ADD COLUMN product_key_id INTEGER DEFAULT NULL"); } catch (\Exception $e) {}
        }
    }
}
