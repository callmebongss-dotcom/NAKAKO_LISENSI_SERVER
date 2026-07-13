<?php

namespace App\Infrastructure\Repository;

use App\Infrastructure\Database\Database;
use App\Domain\Entities\AppUpdate;

class AppUpdateRepository
{
    public function getLatest(string $platform): ?AppUpdate
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT * FROM app_updates WHERE (platform = ? OR platform = \'all\') ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$platform]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return null;
        return new AppUpdate($row);
    }

    public function create(array $data): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO app_updates (version, release_notes, file_url, file_size, platform, is_forced) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['version'],
            $data['release_notes'] ?? null,
            $data['file_url'],
            $data['file_size'] ?? 0,
            $data['platform'] ?? 'all',
            $data['is_forced'] ?? 0,
        ]);
        return (int)$db->lastInsertId();
    }

    public function findAll(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT * FROM app_updates ORDER BY created_at DESC');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($r) => new AppUpdate($r), $rows);
    }

    public function delete(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM app_updates WHERE id = ?');
        $stmt->execute([$id]);
    }
}
