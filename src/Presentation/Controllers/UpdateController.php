<?php

namespace App\Presentation\Controllers;

use App\Infrastructure\Repository\AppUpdateRepository;

class UpdateController
{
    private AppUpdateRepository $repo;

    public function __construct()
    {
        $this->repo = new AppUpdateRepository();
    }

    public function check(): void
    {
        $platform = $_GET['platform'] ?? 'all';
        $currentVersion = $_GET['current_version'] ?? '0.0.0';
        $update = $this->repo->getLatest($platform);

        if (!$update || version_compare($update->version, $currentVersion, '<=')) {
            $this->json(['success' => true, 'update_available' => false, 'latest_version' => $update?->version]);
            return;
        }

        $this->json([
            'success' => true,
            'update_available' => true,
            'version' => $update->version,
            'release_notes' => $update->releaseNotes,
            'file_url' => $update->fileUrl,
            'file_size' => $update->fileSize,
            'is_forced' => $update->isForced,
            'created_at' => $update->createdAt,
        ]);
    }

    public function list(): void
    {
        $updates = $this->repo->findAll();
        $data = array_map(fn($u) => $u->toArray(), $updates);
        $this->json(['success' => true, 'data' => $data]);
    }

    public function create(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = $this->repo->create($body);
        $this->json(['success' => true, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
        $this->json(['success' => true]);
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
