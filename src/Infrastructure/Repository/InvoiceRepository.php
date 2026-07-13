<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\Invoice;
use App\Infrastructure\Database\Database;
use PDO;

class InvoiceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('
            SELECT i.*, l.business_name, l.owner_name, sp.name as plan_name
            FROM invoices i
            LEFT JOIN licenses l ON i.license_id = l.id
            LEFT JOIN subscription_plans sp ON i.plan_id = sp.id
            ORDER BY i.created_at DESC
        ');
        return array_map(fn($r) => new Invoice($r), $stmt->fetchAll());
    }

    public function findById(int $id): ?Invoice
    {
        $stmt = $this->db->prepare('
            SELECT i.*, l.business_name, l.owner_name, sp.name as plan_name
            FROM invoices i
            LEFT JOIN licenses l ON i.license_id = l.id
            LEFT JOIN subscription_plans sp ON i.plan_id = sp.id
            WHERE i.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new Invoice($row) : null;
    }

    public function findByLicenseId(int $licenseId): array
    {
        $stmt = $this->db->prepare('
            SELECT i.*, l.business_name, l.owner_name, sp.name as plan_name
            FROM invoices i
            LEFT JOIN licenses l ON i.license_id = l.id
            LEFT JOIN subscription_plans sp ON i.plan_id = sp.id
            WHERE i.license_id = ?
            ORDER BY i.created_at DESC
        ');
        $stmt->execute([$licenseId]);
        return array_map(fn($r) => new Invoice($r), $stmt->fetchAll());
    }

    public function findByNumber(string $number): ?Invoice
    {
        $stmt = $this->db->prepare('
            SELECT i.*, l.business_name, l.owner_name, sp.name as plan_name
            FROM invoices i
            LEFT JOIN licenses l ON i.license_id = l.id
            LEFT JOIN subscription_plans sp ON i.plan_id = sp.id
            WHERE i.invoice_number = ?
        ');
        $stmt->execute([$number]);
        $row = $stmt->fetch();
        return $row ? new Invoice($row) : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO invoices (invoice_number, subscription_id, license_id, plan_id, amount, tax, total, status, due_date, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$data['invoice_number'], $data['subscription_id'] ?? null, $data['license_id'], $data['plan_id'], $data['amount'], $data['tax'] ?? 0, $data['total'], $data['status'] ?? 'UNPAID', $data['due_date'] ?? null, $data['notes'] ?? null]);
        return (int) $this->db->lastInsertId();
    }

    public function markPaid(int $id, ?string $paidAt = null): bool
    {
        $paidAt = $paidAt ?? date('Y-m-d H:i:s');
        $stmt = $this->db->prepare('UPDATE invoices SET status = \'PAID\', paid_at = ? WHERE id = ?');
        return $stmt->execute([$paidAt, $id]);
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as c FROM invoices WHERE status = ?");
        $stmt->execute([$status]);
        return (int) $stmt->fetch()['c'];
    }

    public function getNextNumber(): string
    {
        $stmt = $this->db->query("SELECT COUNT(*) as c FROM invoices WHERE YEAR(created_at) = YEAR(NOW())");
        $count = (int) $stmt->fetch()['c'];
        return 'INV-' . date('Y') . '-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }
}
