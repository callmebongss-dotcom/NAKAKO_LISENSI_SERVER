<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\PaymentTransaction;
use App\Infrastructure\Database\Database;
use PDO;

class PaymentTransactionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('
            SELECT pt.*, i.invoice_number, l.business_name, l.owner_name
            FROM payment_transactions pt
            LEFT JOIN invoices i ON pt.invoice_id = i.id
            LEFT JOIN licenses l ON pt.license_id = l.id
            ORDER BY pt.created_at DESC
        ');
        return array_map(fn($r) => new PaymentTransaction($r), $stmt->fetchAll());
    }

    public function findById(int $id): ?PaymentTransaction
    {
        $stmt = $this->db->prepare('SELECT * FROM payment_transactions WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new PaymentTransaction($row) : null;
    }

    public function findByInvoiceId(int $invoiceId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM payment_transactions WHERE invoice_id = ? ORDER BY created_at DESC');
        $stmt->execute([$invoiceId]);
        return array_map(fn($r) => new PaymentTransaction($r), $stmt->fetchAll());
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO payment_transactions (invoice_id, subscription_id, license_id, amount, provider, provider_reference, status, payment_method, paid_at, raw_response, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$data['invoice_id'], $data['subscription_id'] ?? null, $data['license_id'], $data['amount'], $data['provider'] ?? null, $data['provider_reference'] ?? null, $data['status'] ?? 'PENDING', $data['payment_method'] ?? null, $data['paid_at'] ?? null, $data['raw_response'] ?? null]);
        return (int) $this->db->lastInsertId();
    }

    public function markPaid(int $id, string $providerReference, ?string $paidAt = null): bool
    {
        $paidAt = $paidAt ?? date('Y-m-d H:i:s');
        $stmt = $this->db->prepare('UPDATE payment_transactions SET status = \'PAID\', provider_reference = ?, paid_at = ? WHERE id = ?');
        return $stmt->execute([$providerReference, $paidAt, $id]);
    }

    public function markFailed(int $id, string $reason): bool
    {
        $stmt = $this->db->prepare('UPDATE payment_transactions SET status = \'FAILED\', raw_response = ? WHERE id = ?');
        return $stmt->execute([$reason, $id]);
    }
}
