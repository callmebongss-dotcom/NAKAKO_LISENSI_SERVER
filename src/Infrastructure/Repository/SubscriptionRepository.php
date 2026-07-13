<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\Subscription;
use App\Infrastructure\Database\Database;
use PDO;

class SubscriptionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('
            SELECT s.*, l.business_name, l.owner_name, sp.name as plan_name, sp.price as plan_price
            FROM subscriptions s
            LEFT JOIN licenses l ON s.license_id = l.id
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            ORDER BY s.created_at DESC
        ');
        return array_map(fn($r) => new Subscription($r), $stmt->fetchAll());
    }

    public function findById(int $id): ?Subscription
    {
        $stmt = $this->db->prepare('
            SELECT s.*, l.business_name, l.owner_name, sp.name as plan_name, sp.price as plan_price
            FROM subscriptions s
            LEFT JOIN licenses l ON s.license_id = l.id
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new Subscription($row) : null;
    }

    public function findByLicenseId(int $licenseId): ?Subscription
    {
        $stmt = $this->db->prepare('
            SELECT s.*, l.business_name, l.owner_name, sp.name as plan_name, sp.price as plan_price
            FROM subscriptions s
            LEFT JOIN licenses l ON s.license_id = l.id
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.license_id = ?
            ORDER BY s.created_at DESC LIMIT 1
        ');
        $stmt->execute([$licenseId]);
        $row = $stmt->fetch();
        return $row ? new Subscription($row) : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO subscriptions (license_id, plan_id, status, start_date, end_date, grace_end_date, auto_renew, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([$data['license_id'], $data['plan_id'], $data['status'] ?? 'PENDING', $data['start_date'] ?? null, $data['end_date'] ?? null, $data['grace_end_date'] ?? null, $data['auto_renew'] ?? 0]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        foreach (['plan_id', 'status', 'start_date', 'end_date', 'grace_end_date', 'auto_renew'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $fields[] = "updated_at = NOW()";
        $values[] = $id;
        $stmt = $this->db->prepare('UPDATE subscriptions SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    public function findExpiringSoon(int $days): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, l.business_name, l.owner_name, sp.name as plan_name, sp.price as plan_price
            FROM subscriptions s
            LEFT JOIN licenses l ON s.license_id = l.id
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.status = 'ACTIVE' AND s.end_date IS NOT NULL
            AND julianday(s.end_date) - julianday('now') BETWEEN 0 AND ?
            ORDER BY s.end_date ASC
        ");
        $stmt->execute([$days]);
        return array_map(fn($r) => new Subscription($r), $stmt->fetchAll());
    }

    public function findExpired(): array
    {
        $stmt = $this->db->query("
            SELECT s.*, l.business_name, l.owner_name, sp.name as plan_name, sp.price as plan_price
            FROM subscriptions s
            LEFT JOIN licenses l ON s.license_id = l.id
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.status = 'ACTIVE' AND s.end_date IS NOT NULL
            AND s.end_date <= NOW()
            ORDER BY s.end_date DESC
        ");
        return array_map(fn($r) => new Subscription($r), $stmt->fetchAll());
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as c FROM subscriptions WHERE status = ?');
        $stmt->execute([$status]);
        return (int) $stmt->fetch()['c'];
    }

    public function getTotalActive(): int
    {
        return $this->countByStatus('ACTIVE');
    }

    public function getTotalExpired(): int
    {
        return $this->countByStatus('EXPIRED');
    }

    public function getMonthlyRevenue(): float
    {
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(amount), 0) as total FROM payment_transactions
            WHERE status = 'PAID' AND DATE_FORMAT(paid_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
        ");
        return (float) $stmt->fetch()['total'];
    }

    public function getYearlyRevenue(): float
    {
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(amount), 0) as total FROM payment_transactions
            WHERE status = 'PAID' AND YEAR(paid_at) = YEAR(NOW())
        ");
        return (float) $stmt->fetch()['total'];
    }

    public function getTodayRevenue(): float
    {
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(amount), 0) as total FROM payment_transactions
            WHERE status = 'PAID' AND DATE(paid_at) = CURDATE()
        ");
        return (float) $stmt->fetch()['total'];
    }

    public function getMRR(): float
    {
        // MRR = sum of active subscription plan prices (annual / 12)
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(CASE WHEN sp.duration_days >= 365 THEN sp.price / 12 ELSE sp.price END), 0) as mrr
            FROM subscriptions s
            JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.status = 'ACTIVE'
        ");
        return (float) $stmt->fetch()['mrr'];
    }
}
