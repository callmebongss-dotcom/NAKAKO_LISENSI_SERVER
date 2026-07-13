<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\LicenseCommand;
use App\Infrastructure\Database\Database;
use PDO;

class LicenseCommandRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO license_commands (license_id, command, payload, status, created_by, created_at)
            VALUES (?, ?, ?, \'PENDING\', ?, NOW())
        ');
        $stmt->execute([
            $data['license_id'],
            $data['command'],
            $data['payload'] ?? null,
            $data['created_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findPendingByLicenseId(int $licenseId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM license_commands
            WHERE license_id = ? AND status = \'PENDING\'
            ORDER BY created_at ASC
        ');
        $stmt->execute([$licenseId]);
        return array_map(fn($row) => new LicenseCommand($row), $stmt->fetchAll());
    }

    public function markExecuted(int $id, string $result): bool
    {
        $stmt = $this->db->prepare('
            UPDATE license_commands SET status = \'EXECUTED\', executed_at = NOW(), result = ?
            WHERE id = ?
        ');
        return $stmt->execute([$result, $id]);
    }

    public function markFailed(int $id, string $result): bool
    {
        $stmt = $this->db->prepare('
            UPDATE license_commands SET status = \'FAILED\', executed_at = NOW(), result = ?
            WHERE id = ?
        ');
        return $stmt->execute([$result, $id]);
    }

    public function findByLicenseId(int $licenseId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM license_commands WHERE license_id = ? ORDER BY created_at DESC
        ');
        $stmt->execute([$licenseId]);
        return array_map(fn($row) => new LicenseCommand($row), $stmt->fetchAll());
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT lc.*, l.business_name, l.owner_name
            FROM license_commands lc
            LEFT JOIN licenses l ON lc.license_id = l.id
            ORDER BY lc.created_at DESC
            LIMIT 100
        ');
        return $stmt->fetchAll();
    }
}
