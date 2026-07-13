<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\LicensePlan;
use App\Infrastructure\Database\Database;
use PDO;

class LicensePlanRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM license_plans ORDER BY id ASC');
        return array_map(fn($row) => new LicensePlan($row), $stmt->fetchAll());
    }

    public function findById(int $id): ?LicensePlan
    {
        $stmt = $this->db->prepare('SELECT * FROM license_plans WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new LicensePlan($row) : null;
    }

    public function findActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM license_plans WHERE is_active = 1 ORDER BY id ASC');
        return array_map(fn($row) => new LicensePlan($row), $stmt->fetchAll());
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO license_plans (plan_name, description, max_tv, offline_days, license_duration_days, allow_transfer, max_transfer, allow_remote_disable, allow_remote_update, priority_support, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([
            $data['plan_name'],
            $data['description'] ?? null,
            $data['max_tv'] ?? 0,
            $data['offline_days'] ?? 30,
            $data['license_duration_days'] ?? 365,
            $data['allow_transfer'] ?? 0,
            $data['max_transfer'] ?? 0,
            $data['allow_remote_disable'] ?? 0,
            $data['allow_remote_update'] ?? 0,
            $data['priority_support'] ?? 0,
            $data['is_active'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $allowed = ['plan_name', 'description', 'max_tv', 'offline_days', 'license_duration_days', 'allow_transfer', 'max_transfer', 'allow_remote_disable', 'allow_remote_update', 'priority_support', 'is_active'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $fields[] = "updated_at = NOW()";
        $values[] = $id;
        $sql = 'UPDATE license_plans SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM license_plans WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getDefaultPlan(): LicensePlan
    {
        $plan = $this->findById(2); // BASIC
        if ($plan === null) {
            return new LicensePlan([
                'plan_name' => 'BASIC',
                'max_tv' => 4,
                'offline_days' => 7,
                'license_duration_days' => 30,
            ]);
        }
        return $plan;
    }
}
