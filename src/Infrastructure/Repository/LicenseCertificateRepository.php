<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\LicenseCertificate;
use App\Infrastructure\Database\Database;
use PDO;

class LicenseCertificateRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM license_certificates ORDER BY created_at DESC');
        return array_map(fn($row) => new LicenseCertificate($row), $stmt->fetchAll());
    }

    public function findById(int $id): ?LicenseCertificate
    {
        $stmt = $this->db->prepare('SELECT * FROM license_certificates WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new LicenseCertificate($row) : null;
    }

    public function findByCertificateNumber(string $number): ?LicenseCertificate
    {
        $stmt = $this->db->prepare('SELECT * FROM license_certificates WHERE certificate_number = ?');
        $stmt->execute([$number]);
        $row = $stmt->fetch();
        return $row ? new LicenseCertificate($row) : null;
    }

    public function findByLicenseId(int $licenseId): ?LicenseCertificate
    {
        $stmt = $this->db->prepare('SELECT * FROM license_certificates WHERE license_id = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$licenseId]);
        $row = $stmt->fetch();
        return $row ? new LicenseCertificate($row) : null;
    }

    public function findAllByLicenseId(int $licenseId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM license_certificates WHERE license_id = ? ORDER BY created_at DESC');
        $stmt->execute([$licenseId]);
        return array_map(fn($row) => new LicenseCertificate($row), $stmt->fetchAll());
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO license_certificates (certificate_number, license_id, license_key, product_key, business_name, owner_name, phone_number, device_id, license_type, activation_date, signature_hash, qr_data, generated_at, generated_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())
        ');
        $stmt->execute([
            $data['certificate_number'],
            $data['license_id'],
            $data['license_key'] ?? null,
            $data['product_key'] ?? null,
            $data['business_name'] ?? null,
            $data['owner_name'] ?? null,
            $data['phone_number'] ?? null,
            $data['device_id'] ?? null,
            $data['license_type'] ?? null,
            $data['activation_date'] ?? null,
            $data['signature_hash'] ?? null,
            $data['qr_data'] ?? null,
            $data['generated_by'] ?? 'SYSTEM',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getTotalCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as count FROM license_certificates');
        return (int) $stmt->fetch()['count'];
    }
}
