<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\LicensePackage;
use App\Infrastructure\Database\Database;
use PDO;

class LicensePackageRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM license_packages ORDER BY id ASC');
        return array_map(fn($row) => new LicensePackage($row), $stmt->fetchAll());
    }

    public function findById(int $id): ?LicensePackage
    {
        $stmt = $this->db->prepare('SELECT * FROM license_packages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new LicensePackage($row) : null;
    }

    public function findActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM license_packages WHERE status = 'ACTIVE' ORDER BY id ASC");
        return array_map(fn($row) => new LicensePackage($row), $stmt->fetchAll());
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO license_packages (name, description, price, duration_days, max_devices, features, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $data['duration_days'] ?? 30,
            $data['max_devices'] ?? 1,
            $data['features'] ?? null,
            $data['status'] ?? 'ACTIVE',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $allowed = ['name', 'description', 'price', 'duration_days', 'max_devices', 'features', 'status'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $fields[] = "updated_at = NOW()";
        $values[] = $id;
        $sql = 'UPDATE license_packages SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM license_packages WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
