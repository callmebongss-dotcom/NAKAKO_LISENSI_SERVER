<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entities\HistoryLog;
use App\Infrastructure\Database\Database;
use PDO;

class HistoryLogRepository
{
    public function create(HistoryLog $log): HistoryLog
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO history_logs (license_id, action, description, old_value, new_value, admin_name, ip_address, created_at)
             VALUES (:license_id, :action, :description, :old_value, :new_value, :admin_name, :ip_address, NOW())"
        );
        $stmt->execute([
            'license_id' => $log->licenseId,
            'action' => $log->action,
            'description' => $log->description,
            'old_value' => $log->oldValue,
            'new_value' => $log->newValue,
            'admin_name' => $log->adminName,
            'ip_address' => $log->ipAddress,
        ]);
        $log->id = (int) $db->lastInsertId();
        return $log;
    }

    public function findByLicenseId(int $licenseId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM history_logs WHERE license_id = :lid ORDER BY created_at DESC");
        $stmt->execute(['lid' => $licenseId]);
        return array_map(fn($r) => new HistoryLog($r), $stmt->fetchAll());
    }

    public function findRecent(int $limit = 50): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM history_logs ORDER BY created_at DESC LIMIT :lim");
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn($r) => new HistoryLog($r), $stmt->fetchAll());
    }
}
