<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\ProductKey;
use App\Infrastructure\Database\Database;
use PDO;

class ProductKeyRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM product_keys ORDER BY created_at DESC');
        return array_map(fn($row) => new ProductKey($row), $stmt->fetchAll());
    }

    public function findById(int $id): ?ProductKey
    {
        $stmt = $this->db->prepare('SELECT * FROM product_keys WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new ProductKey($row) : null;
    }

    public function findByProductKey(string $productKey): ?ProductKey
    {
        $stmt = $this->db->prepare('SELECT * FROM product_keys WHERE product_key = ?');
        $stmt->execute([$productKey]);
        $row = $stmt->fetch();
        return $row ? new ProductKey($row) : null;
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_keys WHERE status = ? ORDER BY created_at DESC');
        $stmt->execute([strtoupper($status)]);
        return array_map(fn($row) => new ProductKey($row), $stmt->fetchAll());
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM product_keys WHERE status = ?');
        $stmt->execute([strtoupper($status)]);
        return (int) $stmt->fetch()['count'];
    }

    public function getTotalCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as count FROM product_keys');
        return (int) $stmt->fetch()['count'];
    }

    public function search(string $q): array
    {
        $like = '%' . $q . '%';
        $stmt = $this->db->prepare("
            SELECT * FROM product_keys
            WHERE product_key LIKE ? OR generated_by LIKE ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$like, $like]);
        return array_map(fn($row) => new ProductKey($row), $stmt->fetchAll());
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO product_keys (product_key, status, license_type, generated_by, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            $data['product_key'],
            $data['status'] ?? 'UNUSED',
            $data['license_type'] ?? 'LIFETIME',
            $data['generated_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function markAsUsed(int $id, int $licenseId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE product_keys SET status = \'USED\', license_id = ?, activated_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$licenseId, $id]);
    }

    public function block(int $id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE product_keys SET status = \'BLOCKED\' WHERE id = ?
        ');
        return $stmt->execute([$id]);
    }
}
