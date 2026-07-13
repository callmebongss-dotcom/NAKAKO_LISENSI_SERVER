<?php

namespace App\Infrastructure\Repository;

use App\Infrastructure\Database\Database;
use PDO;

class AuditLogRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO audit_logs (action, admin_name, license_id, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            $data['action'],
            $data['admin_name'],
            $data['license_id'] ?? null,
            $data['details'] ?? null,
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findAll(int $limit = 100): array
    {
        $stmt = $this->db->query("
            SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT $limit
        ");
        return $stmt->fetchAll();
    }

    public function findByLicenseId(int $licenseId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM audit_logs WHERE license_id = ? ORDER BY created_at DESC
        ');
        $stmt->execute([$licenseId]);
        return $stmt->fetchAll();
    }
}
