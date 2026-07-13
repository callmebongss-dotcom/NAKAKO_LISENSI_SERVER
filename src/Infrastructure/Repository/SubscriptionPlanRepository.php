<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\SubscriptionPlan;
use App\Infrastructure\Database\Database;
use PDO;

class SubscriptionPlanRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM subscription_plans ORDER BY price ASC');
        return array_map(fn($r) => new SubscriptionPlan($r), $stmt->fetchAll());
    }

    public function findActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM subscription_plans WHERE status = 'ACTIVE' ORDER BY price ASC");
        return array_map(fn($r) => new SubscriptionPlan($r), $stmt->fetchAll());
    }

    public function findById(int $id): ?SubscriptionPlan
    {
        $stmt = $this->db->prepare('SELECT * FROM subscription_plans WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new SubscriptionPlan($row) : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO subscription_plans (name, price, duration_days, offline_days, max_tv, max_user, feature_flags, description, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([$data['name'], $data['price'] ?? 0, $data['duration_days'] ?? 30, $data['offline_days'] ?? 7, $data['max_tv'] ?? 2, $data['max_user'] ?? 1, $data['feature_flags'] ?? null, $data['description'] ?? null, $data['status'] ?? 'ACTIVE']);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        foreach (['name', 'price', 'duration_days', 'offline_days', 'max_tv', 'max_user', 'feature_flags', 'description', 'status'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $fields[] = "updated_at = NOW()";
        $values[] = $id;
        $stmt = $this->db->prepare('UPDATE subscription_plans SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM subscription_plans WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
