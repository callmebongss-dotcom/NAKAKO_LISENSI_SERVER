<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\License;
use App\Infrastructure\Database\Database;
use PDO;

class LicenseRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM licenses ORDER BY created_at DESC');
        return array_map(fn($row) => new License($row), $stmt->fetchAll());
    }

    public function findById(int $id): ?License
    {
        $stmt = $this->db->prepare('SELECT * FROM licenses WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new License($row) : null;
    }

    public function findByDeviceId(string $deviceId): ?License
    {
        $stmt = $this->db->prepare('SELECT * FROM licenses WHERE device_id = ?');
        $stmt->execute([$deviceId]);
        $row = $stmt->fetch();
        return $row ? new License($row) : null;
    }

    public function findByFingerprint(string $fingerprint): ?License
    {
        $stmt = $this->db->prepare('SELECT * FROM licenses WHERE device_fingerprint = ?');
        $stmt->execute([$fingerprint]);
        $row = $stmt->fetch();
        return $row ? new License($row) : null;
    }

    public function findByLicenseKey(string $licenseKey): ?License
    {
        $stmt = $this->db->prepare('SELECT * FROM licenses WHERE license_key = ?');
        $stmt->execute([$licenseKey]);
        $row = $stmt->fetch();
        return $row ? new License($row) : null;
    }

    public function findByProductKey(string $productKey): ?License
    {
        $stmt = $this->db->prepare('SELECT * FROM licenses WHERE product_key = ?');
        $stmt->execute([$productKey]);
        $row = $stmt->fetch();
        return $row ? new License($row) : null;
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->db->prepare('SELECT * FROM licenses WHERE license_status = ? ORDER BY created_at DESC');
        $stmt->execute([strtoupper($status)]);
        return array_map(fn($row) => new License($row), $stmt->fetchAll());
    }

    public function search(string $q): array
    {
        $like = '%' . $q . '%';
        $stmt = $this->db->prepare("
            SELECT * FROM licenses
            WHERE business_name LIKE ? OR phone_number LIKE ? OR device_id LIKE ? OR license_key LIKE ? OR city LIKE ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$like, $like, $like, $like, $like]);
        return array_map(fn($row) => new License($row), $stmt->fetchAll());
    }

    public function searchByLicenseType(string $type): array
    {
        $stmt = $this->db->prepare('SELECT * FROM licenses WHERE license_type = ? ORDER BY created_at DESC');
        $stmt->execute([strtoupper($type)]);
        return array_map(fn($row) => new License($row), $stmt->fetchAll());
    }

    public function create(array $data): int
    {
        $planId = $data['plan_id'] ?? null;
        if ($planId === null) {
            $planRepo = new \App\Infrastructure\Repository\LicensePlanRepository();
            $plan = $planRepo->getDefaultPlan();
            $planId = $plan->id;
        }
        $stmt = $this->db->prepare('
            INSERT INTO licenses (device_id, business_name, owner_name, phone_number, email, city, license_status, license_type, device_fingerprint, device_name, platform, app_version, plan_id, product_key, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([
            $data['device_id'] ?? null,
            $data['business_name'],
            $data['owner_name'],
            $data['phone_number'],
            $data['email'] ?? null,
            $data['city'],
            'PENDING',
            'TRIAL',
            $data['device_fingerprint'] ?? null,
            $data['device_name'] ?? null,
            $data['platform'] ?? null,
            $data['app_version'] ?? null,
            $planId,
            $data['product_key'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function createActiveByDevice(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO licenses (device_id, license_key, business_name, owner_name, phone_number, city, license_status, license_type, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, \'ACTIVE\', \'FULL\', NOW(), NOW())
        ');
        $stmt->execute([
            $data['device_id'],
            $data['license_key'],
            $data['business_name'] ?? 'Android User',
            $data['owner_name'] ?? 'User',
            $data['phone_number'] ?? '-',
            $data['city'] ?? '-',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateLicenseData(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;
        $stmt = $this->db->prepare('UPDATE licenses SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    public function approve(int $id, string $licenseKey, string $approvedBy): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET license_status = \'ACTIVE\', license_key = ?, approved_at = NOW(), updated_at = NOW(), approved_by = ?
            WHERE id = ?
        ');
        return $stmt->execute([$licenseKey, $approvedBy, $id]);
    }

    public function approveAsLifetime(int $id, string $licenseKey, string $approvedBy, float $purchasePrice): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET license_status = \'ACTIVE\', license_type = \'LIFETIME\', license_key = ?, purchase_price = ?, purchase_date = NOW(), activation_date = NOW(), approved_at = NOW(), updated_at = NOW(), approved_by = ?
            WHERE id = ?
        ');
        return $stmt->execute([$licenseKey, $purchasePrice, $approvedBy, $id]);
    }

    public function approveAsTrial(int $id, string $licenseKey, string $approvedBy, int $trialDays): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET license_status = \'ACTIVE\', license_type = \'TRIAL\', license_key = ?, activation_date = NOW(), approved_at = NOW(), updated_at = NOW(), approved_by = ?, license_expired = DATE_ADD(NOW(), INTERVAL ? DAY)
            WHERE id = ?
        ');
        return $stmt->execute([$licenseKey, $approvedBy, '+' . $trialDays, $id]);
    }

    public function reject(int $id, string $remarks, string $approvedBy): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET license_status = \'REJECTED\', updated_at = NOW(), remarks = ?, approved_by = ?
            WHERE id = ?
        ');
        return $stmt->execute([$remarks, $approvedBy, $id]);
    }

    public function block(int $id, string $reason): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET license_status = \'BLOCKED\', blocked_date = NOW(), blocked_reason = ?, updated_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$reason, $id]);
    }

    public function unblock(int $id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET license_status = \'ACTIVE\', blocked_date = NULL, blocked_reason = NULL, updated_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$id]);
    }

    public function setTrialExpired(int $id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET license_status = \'TRIAL_EXPIRED\', updated_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$id]);
    }

    public function suspend(int $id, string $remarks): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET license_status = \'SUSPENDED\', updated_at = NOW(), remarks = ?
            WHERE id = ?
        ');
        return $stmt->execute([$remarks, $id]);
    }

    public function unsuspend(int $id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET license_status = \'ACTIVE\', updated_at = NOW(), remarks = NULL
            WHERE id = ?
        ');
        return $stmt->execute([$id]);
    }

    public function updateFingerprint(int $id, string $fingerprint, string $deviceName, string $platform, string $appVersion): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET device_fingerprint = ?, device_name = ?, platform = ?, app_version = ?, last_online = NOW(), last_sync = NOW(), updated_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$fingerprint, $deviceName, $platform, $appVersion, $id]);
    }

    public function updateDevice(int $id, string $deviceId, string $fingerprint, string $deviceName, string $platform, string $appVersion): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET device_id = ?, device_fingerprint = ?, device_name = ?, platform = ?, app_version = ?, last_online = NOW(), last_sync = NOW(), updated_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$deviceId, $fingerprint, $deviceName, $platform, $appVersion, $id]);
    }

    public function setPlan(int $id, int $planId): bool
    {
        $stmt = $this->db->prepare('UPDATE licenses SET plan_id = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$planId, $id]);
    }

    public function clearFingerprint(int $id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET device_fingerprint = NULL, device_name = NULL, platform = NULL, app_version = NULL, last_online = NULL, clone_detected = 0, license_status = \'ACTIVE\', remarks = \'Menunggu aktivasi perangkat baru\', updated_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$id]);
    }

    public function resetDevice(int $id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET device_fingerprint = NULL, device_name = NULL, platform = NULL, app_version = NULL, device_id = NULL, last_online = NULL, clone_detected = 0, remarks = \'Device reset oleh admin\', updated_at = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$id]);
    }

    public function markCloneDetected(int $id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE licenses SET clone_detected = 1, license_status = \'BLOCKED\', updated_at = NOW(), remarks = \'CLONE DETECTED: Device ID digunakan oleh fingerprint berbeda\'
            WHERE id = ?
        ');
        return $stmt->execute([$id]);
    }

    public function updateLastOnline(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE licenses SET last_online = NOW(), updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM licenses WHERE license_status = ?');
        $stmt->execute([strtoupper($status)]);
        return (int) $stmt->fetch()['count'];
    }

    public function getTotalCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as count FROM licenses');
        return (int) $stmt->fetch()['count'];
    }

    public function extendExpiry(int $id, string $newExpiry): bool
    {
        $stmt = $this->db->prepare('UPDATE licenses SET license_expired = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$newExpiry, $id]);
    }

    public function addTransferHistory(int $licenseId, ?string $oldFp, ?string $newFp, ?string $oldDeviceId, ?string $newDeviceId, string $adminName, ?string $reason): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO device_transfer_history (license_id, old_fingerprint, new_fingerprint, old_device_id, new_device_id, admin_name, reason, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$licenseId, $oldFp, $newFp, $oldDeviceId, $newDeviceId, $adminName, $reason]);
    }

    public function getTransferHistory(int $licenseId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM device_transfer_history WHERE license_id = ? ORDER BY created_at DESC');
        $stmt->execute([$licenseId]);
        return $stmt->fetchAll();
    }

    public function incrementTransferCount(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE licenses SET transfer_count = transfer_count + 1, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function updatePurchasePrice(int $id, float $price): bool
    {
        $stmt = $this->db->prepare('UPDATE licenses SET purchase_price = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$price, $id]);
    }

    public function updateMajorVersion(int $id, string $version): bool
    {
        $stmt = $this->db->prepare('UPDATE licenses SET major_version = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$version, $id]);
    }

    public function updateNotesOnly(int $id, ?string $notes): bool
    {
        $stmt = $this->db->prepare('UPDATE licenses SET notes = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$notes, $id]);
    }

    public function updateMaxTransfer(int $id, int $max): bool
    {
        $stmt = $this->db->prepare('UPDATE licenses SET max_transfer = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$max, $id]);
    }

    public function updateProductKey(int $id, string $productKey): bool
    {
        $stmt = $this->db->prepare('UPDATE licenses SET product_key = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$productKey, $id]);
    }

    public function updateProductKeyId(int $id, int $productKeyId): bool
    {
        $stmt = $this->db->prepare('UPDATE licenses SET product_key_id = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$productKeyId, $id]);
    }
}
