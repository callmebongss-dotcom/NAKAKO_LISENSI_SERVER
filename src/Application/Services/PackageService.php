<?php

namespace App\Application\Services;

use App\Infrastructure\Repository\LicensePackageRepository;

class PackageService
{
    private LicensePackageRepository $repo;

    public function __construct()
    {
        $this->repo = new LicensePackageRepository();
    }

    public function getAll(): array
    {
        try {
            $packages = $this->repo->findAll();
            return [
                'success' => true,
                'data' => array_map(fn($p) => $p->toArray(), $packages),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getById(int $id): array
    {
        try {
            $p = $this->repo->findById($id);
            if (!$p) {
                return ['success' => false, 'message' => 'Paket tidak ditemukan'];
            }
            return ['success' => true, 'data' => $p->toArray()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function create(array $data): array
    {
        try {
            if (empty($data['name'])) {
                return ['success' => false, 'message' => 'Nama paket wajib diisi'];
            }
            $id = $this->repo->create($data);
            return ['success' => true, 'message' => 'Paket berhasil dibuat', 'id' => $id];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function update(int $id, array $data): array
    {
        try {
            $existing = $this->repo->findById($id);
            if (!$existing) {
                return ['success' => false, 'message' => 'Paket tidak ditemukan'];
            }
            $this->repo->update($id, $data);
            return ['success' => true, 'message' => 'Paket berhasil diperbarui'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function delete(int $id): array
    {
        try {
            $existing = $this->repo->findById($id);
            if (!$existing) {
                return ['success' => false, 'message' => 'Paket tidak ditemukan'];
            }
            $this->repo->delete($id);
            return ['success' => true, 'message' => 'Paket berhasil dihapus'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
