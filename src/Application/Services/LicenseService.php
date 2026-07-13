<?php

namespace App\Application\Services;

use App\Domain\Entities\License;
use App\Domain\Entities\LicenseCommand;
use App\Infrastructure\Repository\LicenseRepository;

class LicenseService
{
    private LicenseRepository $repository;
    private \App\Infrastructure\Repository\LicensePlanRepository $planRepository;
    private \App\Infrastructure\Repository\LicenseCommandRepository $commandRepository;
    private \App\Infrastructure\Repository\AuditLogRepository $auditRepository;

    public function __construct()
    {
        $this->repository = new LicenseRepository();
        $this->planRepository = new \App\Infrastructure\Repository\LicensePlanRepository();
        $this->commandRepository = new \App\Infrastructure\Repository\LicenseCommandRepository();
        $this->auditRepository = new \App\Infrastructure\Repository\AuditLogRepository();
    }

    private function logAudit(string $action, string $adminName, ?int $licenseId = null, ?string $details = null): void
    {
        $this->auditRepository->create([
            'action' => $action,
            'admin_name' => $adminName,
            'license_id' => $licenseId,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    private function sendCommand(int $licenseId, string $command, ?string $payload, string $createdBy): array
    {
        $id = $this->commandRepository->create([
            'license_id' => $licenseId,
            'command' => $command,
            'payload' => $payload,
            'created_by' => $createdBy,
        ]);
        return ['success' => true, 'message' => 'Perintah dikirim', 'command_id' => $id];
    }

    private function getPlanForLicense(License $license): \App\Domain\Entities\LicensePlan
    {
        if ($license->planId !== null) {
            $plan = $this->planRepository->findById($license->planId);
            if ($plan !== null) return $plan;
        }
        return $this->planRepository->getDefaultPlan();
    }

    private function addPlanToResponse(array $response, License $license): array
    {
        $plan = $this->getPlanForLicense($license);
        $response['plan'] = $plan->toArray();
        return $response;
    }

    public function requestActivation(array $data): array
    {
        $errors = [];
        if (empty($data['business_name'])) $errors[] = 'business_name wajib diisi';
        if (empty($data['owner_name'])) $errors[] = 'owner_name wajib diisi';
        if (empty($data['phone_number'])) $errors[] = 'phone_number wajib diisi';
        if (empty($data['city'])) $errors[] = 'city wajib diisi';
        if (empty($data['device_id'])) $errors[] = 'device_id wajib diisi';
        if (empty($data['product_key'])) $errors[] = 'product_key wajib diisi';

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        // Validate product key
        $pkRepo = new \App\Infrastructure\Repository\ProductKeyRepository();
        $pk = $pkRepo->findByProductKey($data['product_key']);
        if ($pk === null) {
            return ['success' => false, 'message' => 'Product Key tidak valid'];
        }
        if ($pk->status !== 'UNUSED') {
            return ['success' => false, 'message' => 'Product Key sudah tidak tersedia'];
        }

        $existing = $this->repository->findByDeviceId($data['device_id']);
        if ($existing !== null) {
            return [
                'success' => false,
                'message' => 'Perangkat sudah terdaftar',
                'status' => $existing->licenseStatus,
            ];
        }

        $id = $this->repository->create($data);
        $license = $this->repository->findById($id);

        // Mark product key as used
        $pkRepo->markAsUsed($pk->id, $id);
        $this->repository->updateProductKey($id, $data['product_key']);
        $this->repository->updateProductKeyId($id, $pk->id);

        $license = $this->repository->findById($id);

        return [
            'success' => true,
            'message' => 'Permintaan aktivasi berhasil dikirim',
            'data' => $license ? $license->toArray() : null,
        ];
    }

    public function checkStatus(string $deviceId): array
    {
        $license = $this->repository->findByDeviceId($deviceId);
        if ($license === null) {
            return [
                'success' => false,
                'message' => 'Lisensi tidak ditemukan',
                'status' => 'UNREGISTERED',
            ];
        }

        return [
            'success' => true,
            'message' => 'Status lisensi ditemukan',
            'data' => $license->toArray(),
        ];
    }

    public function createByDevice(array $data): array
    {
        if (empty($data['device_id'])) {
            return ['success' => false, 'message' => 'device_id wajib diisi'];
        }

        $licenseKey = $this->generateLicenseKey();
        while ($this->repository->findByLicenseKey($licenseKey) !== null) {
            $licenseKey = $this->generateLicenseKey();
        }

        $id = $this->repository->createActiveByDevice([
            'device_id' => $data['device_id'],
            'license_key' => $licenseKey,
            'business_name' => $data['business_name'] ?? 'Android User',
            'owner_name' => $data['owner_name'] ?? 'User',
            'phone_number' => $data['phone_number'] ?? '-',
            'city' => $data['city'] ?? '-',
        ]);

        $license = $this->repository->findById($id);

        return [
            'success' => true,
            'message' => 'Lisensi berhasil dibuat',
            'data' => [
                'id' => $id,
                'license_key' => $licenseKey,
                'device_id' => $data['device_id'],
            ],
        ];
    }

    public function activateByLicenseKey(array $data): array
    {
        if (empty($data['license_key'])) {
            return ['success' => false, 'message' => 'license_key wajib diisi'];
        }
        if (empty($data['device_id'])) {
            return ['success' => false, 'message' => 'device_id wajib diisi'];
        }

        $license = $this->repository->findByLicenseKey($data['license_key']);
        if ($license === null) {
            return ['success' => false, 'message' => 'License Key tidak ditemukan'];
        }

        if ($license->licenseStatus !== 'ACTIVE') {
            return ['success' => false, 'message' => 'Lisensi belum aktif', 'status' => $license->licenseStatus];
        }

        if ($license->deviceId !== $data['device_id']) {
            return ['success' => false, 'message' => 'Device ID tidak cocok dengan License Key ini'];
        }

        $this->repository->updateLicenseData($license->id, [
            'business_name' => $data['business_name'] ?? $license->businessName,
            'owner_name' => $data['owner_name'] ?? $license->ownerName,
            'phone_number' => $data['phone_number'] ?? $license->phoneNumber,
            'email' => $data['email'] ?? $license->email,
            'city' => $data['city'] ?? $license->city,
            'device_fingerprint' => $data['device_fingerprint'] ?? $license->deviceFingerprint,
            'device_name' => $data['device_name'] ?? $license->deviceName,
            'platform' => $data['platform'] ?? $license->platform,
            'app_version' => $data['app_version'] ?? $license->appVersion,
            'last_online' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $updated = $this->repository->findById($license->id);

        $offlineToken = $this->generateOfflineToken($updated);

        $result = [
            'success' => true,
            'message' => 'Lisensi berhasil diaktifkan',
            'data' => $updated->toArray(),
            'offline_token' => $offlineToken,
        ];
        return $this->addPlanToResponse($result, $updated);
    }

    public function approve(int $id, string $approvedBy): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        if ($license->licenseStatus !== 'PENDING') {
            return ['success' => false, 'message' => 'Status lisensi bukan PENDING'];
        }

        $licenseKey = $this->generateLicenseKey();
        while ($this->repository->findByLicenseKey($licenseKey) !== null) {
            $licenseKey = $this->generateLicenseKey();
        }

        $this->repository->approve($id, $licenseKey, $approvedBy);
        $updated = $this->repository->findById($id);

        return [
            'success' => true,
            'message' => 'Lisensi berhasil diaktifkan',
            'data' => $updated->toArray(),
        ];
    }

    public function reject(int $id, string $remarks, string $approvedBy): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $this->repository->reject($id, $remarks, $approvedBy);
        $updated = $this->repository->findById($id);

        return [
            'success' => true,
            'message' => 'Lisensi ditolak',
            'data' => $updated->toArray(),
        ];
    }

    public function getAllLicenses(): array
    {
        $licenses = $this->repository->findAll();
        return [
            'success' => true,
            'data' => array_map(fn($l) => $l->toArray(), $licenses),
        ];
    }

    public function getDashboardStats(): array
    {
        return [
            'success' => true,
            'data' => [
                'total' => $this->repository->getTotalCount(),
                'pending' => $this->repository->countByStatus('PENDING'),
                'active' => $this->repository->countByStatus('ACTIVE'),
                'rejected' => $this->repository->countByStatus('REJECTED'),
                'blocked' => $this->repository->countByStatus('BLOCKED'),
                'suspended' => $this->repository->countByStatus('SUSPENDED'),
                'trial_expired' => $this->repository->countByStatus('TRIAL_EXPIRED'),
            ],
        ];
    }

    public function validateDevice(array $data): array
    {
        if (empty($data['device_id'])) {
            return ['success' => false, 'message' => 'device_id wajib diisi'];
        }

        $deviceId = $data['device_id'];
        $fingerprint = $data['device_fingerprint'] ?? '';
        $deviceName = $data['device_name'] ?? '';
        $platform = $data['platform'] ?? '';
        $appVersion = $data['app_version'] ?? '';

        $license = $this->repository->findByDeviceId($deviceId);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan', 'status' => 'UNREGISTERED'];
        }

        if ($license->licenseStatus !== 'ACTIVE' && $license->licenseStatus !== 'SUSPENDED') {
            return ['success' => false, 'message' => 'Lisensi tidak aktif', 'status' => $license->licenseStatus];
        }

        // Update last online
        $this->repository->updateLastOnline($license->id);

        // If no fingerprint stored yet (fresh approval), store it
        if (empty($license->deviceFingerprint)) {
            $this->repository->updateFingerprint($license->id, $fingerprint, $deviceName, $platform, $appVersion);
            $updated = $this->repository->findById($license->id);
            $offlineToken = $this->generateOfflineToken($updated);
            $result = [
                'success' => true,
                'message' => 'Perangkat terverifikasi',
                'status' => 'ACTIVE',
                'data' => $updated->toArray(),
                'offline_token' => $offlineToken,
            ];
            return $this->addPlanToResponse($result, $updated);
        }

        // Compare fingerprint
        if ($license->deviceFingerprint !== $fingerprint) {
            // Clone detection: same device_id but different fingerprint
            $this->repository->markCloneDetected($license->id);
            $this->repository->addTransferHistory(
                $license->id,
                $license->deviceFingerprint,
                $fingerprint,
                $license->deviceId,
                $deviceId,
                'SYSTEM',
                'CLONE DETECTED: Device ID sama, fingerprint berbeda'
            );
            return [
                'success' => false,
                'message' => 'Lisensi digunakan pada perangkat lain',
                'status' => 'INVALID_DEVICE',
                'code' => 'INVALID_DEVICE',
            ];
        }

        // Fingerprint matches
        $this->repository->updateFingerprint($license->id, $fingerprint, $deviceName, $platform, $appVersion);
        $updated = $this->repository->findById($license->id);
        $offlineToken = $this->generateOfflineToken($updated);
        $result = [
            'success' => true,
            'message' => 'Perangkat terverifikasi',
            'status' => 'ACTIVE',
            'data' => $updated->toArray(),
            'offline_token' => $offlineToken,
        ];
        return $this->addPlanToResponse($result, $updated);
    }

    public function renewToken(array $data): array
    {
        $fingerprint = $data['device_fingerprint'] ?? '';
        $deviceId = $data['device_id'] ?? '';

        $license = $this->repository->findByDeviceId($deviceId);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        if ($license->licenseStatus !== 'ACTIVE' && $license->licenseStatus !== 'SUSPENDED') {
            return ['success' => false, 'message' => 'Lisensi tidak aktif'];
        }

        if (!empty($license->deviceFingerprint) && $license->deviceFingerprint !== $fingerprint) {
            return ['success' => false, 'message' => 'Fingerprint tidak cocok'];
        }

        $this->repository->updateLastOnline($license->id);
        $updated = $this->repository->findById($license->id);
        $offlineToken = $this->generateOfflineToken($updated);

        return $this->addPlanToResponse([
            'success' => true,
            'message' => 'Token offline diperbarui',
            'offline_token' => $offlineToken,
            'data' => $updated->toArray(),
        ], $updated);
    }

    private function generateOfflineToken(License $license): string
    {
        $plan = $this->getPlanForLicense($license);
        $offlineDays = $plan->offlineDays > 0 ? $plan->offlineDays : 30;
        $issuedAt = time();
        $expiresAt = $issuedAt + ($offlineDays * 24 * 60 * 60);

        $payload = [
            'license_key' => $license->licenseKey,
            'device_fingerprint' => $license->deviceFingerprint,
            'issued_at' => gmdate('Y-m-d\TH:i:s\Z', $issuedAt),
            'expires_at' => gmdate('Y-m-d\TH:i:s\Z', $expiresAt),
            'version' => '1',
            'plan' => $plan->planName,
            'offline_days' => $offlineDays,
        ];

        $payloadJson = json_encode($payload);
        $payloadB64 = base64_encode($payloadJson);

        $signature = hash_hmac('sha256', $payloadB64, JWT_SECRET, true);
        $signatureB64 = base64_encode($signature);

        return $payloadB64 . '.' . $signatureB64;
    }

    public function transferLicense(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $reason = $data['reason'] ?? 'Transfer lisensi ke perangkat baru';

        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        // Check transfer limit
        if ($license->maxTransfer > 0 && $license->transferCount >= $license->maxTransfer) {
            return ['success' => false, 'message' => 'Batas transfer telah tercapai (' . $license->maxTransfer . 'x). Hubungi admin untuk menambah batas transfer.'];
        }

        $oldFp = $license->deviceFingerprint;
        $oldDeviceId = $license->deviceId;

        $this->repository->addTransferHistory($id, $oldFp, null, $oldDeviceId, null, $adminName, $reason);
        $this->repository->clearFingerprint($id);
        $this->repository->incrementTransferCount($id);
        $this->logHistory($id, 'TRANSFER', "Device ditransfer dari " . ($oldDeviceId ?? '-') . " ke perangkat baru oleh {$adminName}", $oldDeviceId, null, $adminName);

        $updated = $this->repository->findById($id);
        return [
            'success' => true,
            'message' => 'Lisensi berhasil ditransfer. Customer dapat melakukan aktivasi ulang.',
            'data' => $updated->toArray(),
        ];
    }

    public function getTransferHistory(int $id): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $history = $this->repository->getTransferHistory($id);
        return [
            'success' => true,
            'data' => $history,
        ];
    }

    public function getLicensePolicy(string $deviceId): array
    {
        $license = $this->repository->findByDeviceId($deviceId);
        if ($license === null) {
            // Return default plan for unregistered devices
            $plan = $this->planRepository->getDefaultPlan();
            return [
                'success' => true,
                'message' => 'Policy default',
                'plan' => $plan->toArray(),
                'license_status' => 'UNREGISTERED',
            ];
        }
        $plan = $this->getPlanForLicense($license);
        return [
            'success' => true,
            'message' => 'License policy ditemukan',
            'plan' => $plan->toArray(),
            'license_status' => $license->licenseStatus,
            'data' => $license->toArray(),
        ];
    }

    public function getPlans(): array
    {
        $plans = $this->planRepository->findAll();
        return [
            'success' => true,
            'data' => array_map(fn($p) => $p->toArray(), $plans),
        ];
    }

    public function createPlan(array $data): array
    {
        $errors = [];
        if (empty($data['plan_name'])) $errors[] = 'plan_name wajib diisi';
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }
        $id = $this->planRepository->create($data);
        $plan = $this->planRepository->findById($id);
        return [
            'success' => true,
            'message' => 'Plan berhasil dibuat',
            'data' => $plan->toArray(),
        ];
    }

    public function updatePlan(int $id, array $data): array
    {
        $plan = $this->planRepository->findById($id);
        if ($plan === null) {
            return ['success' => false, 'message' => 'Plan tidak ditemukan'];
        }
        $this->planRepository->update($id, $data);
        $updated = $this->planRepository->findById($id);
        return [
            'success' => true,
            'message' => 'Plan berhasil diperbarui',
            'data' => $updated->toArray(),
        ];
    }

    public function deletePlan(int $id): array
    {
        $plan = $this->planRepository->findById($id);
        if ($plan === null) {
            return ['success' => false, 'message' => 'Plan tidak ditemukan'];
        }
        $this->planRepository->delete($id);
        return ['success' => true, 'message' => 'Plan berhasil dihapus'];
    }

    public function setLicensePlan(int $licenseId, int $planId): array
    {
        $license = $this->repository->findById($licenseId);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }
        $plan = $this->planRepository->findById($planId);
        if ($plan === null) {
            return ['success' => false, 'message' => 'Plan tidak ditemukan'];
        }
        $this->repository->setPlan($licenseId, $planId);
        return ['success' => true, 'message' => 'Plan lisensi berhasil diubah'];
    }

    // ==================== LICENSE CONTROL (TAHAP 5) ====================

    public function heartbeat(array $data): array
    {
        if (empty($data['device_id'])) {
            return ['success' => false, 'message' => 'device_id wajib diisi'];
        }

        $license = $this->repository->findByDeviceId($data['device_id']);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $this->repository->updateLastOnline($license->id);

        if (!empty($data['device_fingerprint']) && !empty($license->deviceFingerprint) && $license->deviceFingerprint !== $data['device_fingerprint']) {
            return ['success' => false, 'message' => 'Fingerprint mismatch'];
        }

        // Check for pending commands
        $pending = $this->commandRepository->findPendingByLicenseId($license->id);

        return [
            'success' => true,
            'message' => 'Heartbeat diterima',
            'commands' => array_map(fn($c) => $c->toArray(), $pending),
            'server_time' => gmdate('Y-m-d\TH:i:s\Z'),
        ];
    }

    public function getPendingCommands(string $deviceId): array
    {
        $license = $this->repository->findByDeviceId($deviceId);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan', 'commands' => []];
        }

        $pending = $this->commandRepository->findPendingByLicenseId($license->id);
        return [
            'success' => true,
            'commands' => array_map(fn($c) => $c->toArray(), $pending),
        ];
    }

    public function reportCommandResult(array $data): array
    {
        $commandId = (int) ($data['command_id'] ?? 0);
        $status = $data['status'] ?? 'FAILED';
        $result = $data['result'] ?? '';

        if ($status === 'SUCCESS') {
            $this->commandRepository->markExecuted($commandId, $result);
        } else {
            $this->commandRepository->markFailed($commandId, $result);
        }

        return ['success' => true, 'message' => 'Hasil perintah diterima'];
    }

    public function blockLicense(int $id, string $adminName, ?string $reason = null): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $this->repository->block($id, $reason ?? 'Diblokir oleh Admin');
        $this->logAudit('BLOCK', $adminName, $id, 'Lisensi diblokir' . ($reason ? ': ' . $reason : ''));

        $cmdResult = $this->sendCommand($id, LicenseCommand::COMMAND_BLOCK, $reason, $adminName);

        return [
            'success' => true,
            'message' => 'Lisensi berhasil diblokir',
            'command_id' => $cmdResult['command_id'] ?? null,
        ];
    }

    public function suspendLicense(int $id, string $adminName, ?string $reason = null): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $this->repository->suspend($id, $reason ?? 'Ditangguhkan oleh Admin');
        $this->logAudit('SUSPEND', $adminName, $id, 'Lisensi ditangguhkan' . ($reason ? ': ' . $reason : ''));

        $cmdResult = $this->sendCommand($id, LicenseCommand::COMMAND_SUSPEND, $reason, $adminName);

        return [
            'success' => true,
            'message' => 'Lisensi berhasil ditangguhkan',
            'command_id' => $cmdResult['command_id'] ?? null,
        ];
    }

    public function unsuspendLicense(int $id, string $adminName): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $this->repository->unsuspend($id);
        $this->logAudit('UNSUSPEND', $adminName, $id, 'Lisensi diaktifkan kembali');

        $cmdResult = $this->sendCommand($id, LicenseCommand::COMMAND_UNSUSPEND, null, $adminName);

        return [
            'success' => true,
            'message' => 'Lisensi berhasil diaktifkan kembali',
            'command_id' => $cmdResult['command_id'] ?? null,
        ];
    }

    public function activateLicense(int $id, string $adminName): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $this->repository->unsuspend($id);
        $this->repository->approve($id, $license->licenseKey ?? $this->generateLicenseKey(), $adminName);
        $this->logAudit('ACTIVATE', $adminName, $id, 'Lisensi diaktifkan oleh Admin');

        $cmdResult = $this->sendCommand($id, LicenseCommand::COMMAND_ACTIVATE, null, $adminName);

        return [
            'success' => true,
            'message' => 'Lisensi berhasil diaktifkan',
            'command_id' => $cmdResult['command_id'] ?? null,
        ];
    }

    public function extendLicense(int $id, int $days, string $adminName): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $payload = json_encode(['days' => $days]);
        $this->logAudit('EXTEND', $adminName, $id, "Lisensi diperpanjang $days hari");

        $cmdResult = $this->sendCommand($id, LicenseCommand::COMMAND_EXTEND_LICENSE, $payload, $adminName);

        return [
            'success' => true,
            'message' => "Perintah perpanjangan $days hari telah dikirim",
            'command_id' => $cmdResult['command_id'] ?? null,
        ];
    }

    public function changePlan(int $licenseId, int $planId, string $adminName): array
    {
        $license = $this->repository->findById($licenseId);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $plan = $this->planRepository->findById($planId);
        if ($plan === null) {
            return ['success' => false, 'message' => 'Plan tidak ditemukan'];
        }

        $this->repository->setPlan($licenseId, $planId);
        $this->logAudit('CHANGE_PLAN', $adminName, $licenseId, "Plan diubah ke {$plan->planName}");

        $payload = json_encode(['plan_id' => $planId, 'plan_name' => $plan->planName]);
        $cmdResult = $this->sendCommand($licenseId, LicenseCommand::COMMAND_CHANGE_PLAN, $payload, $adminName);

        return [
            'success' => true,
            'message' => "Plan berhasil diubah ke {$plan->planName}",
            'command_id' => $cmdResult['command_id'] ?? null,
        ];
    }

    public function sendMessage(int $licenseId, string $message, string $adminName): array
    {
        $license = $this->repository->findById($licenseId);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $payload = json_encode(['message' => $message]);
        $this->logAudit('SHOW_MESSAGE', $adminName, $licenseId, "Pesan: $message");

        $cmdResult = $this->sendCommand($licenseId, LicenseCommand::COMMAND_SHOW_MESSAGE, $payload, $adminName);

        return [
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'command_id' => $cmdResult['command_id'] ?? null,
        ];
    }

    public function forceSync(int $licenseId, string $adminName): array
    {
        $license = $this->repository->findById($licenseId);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $this->logAudit('FORCE_SYNC', $adminName, $licenseId, 'Sinkronisasi paksa');
        $cmdResult = $this->sendCommand($licenseId, LicenseCommand::COMMAND_FORCE_SYNC, null, $adminName);

        return [
            'success' => true,
            'message' => 'Perintah sinkronisasi telah dikirim',
            'command_id' => $cmdResult['command_id'] ?? null,
        ];
    }

    public function restartApp(int $licenseId, string $adminName): array
    {
        $license = $this->repository->findById($licenseId);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $this->logAudit('RESTART_APP', $adminName, $licenseId, 'Restart aplikasi');
        $cmdResult = $this->sendCommand($licenseId, LicenseCommand::COMMAND_RESTART_APP, null, $adminName);

        return [
            'success' => true,
            'message' => 'Perintah restart telah dikirim',
            'command_id' => $cmdResult['command_id'] ?? null,
        ];
    }

    public function logoutLicense(int $licenseId, string $adminName): array
    {
        $license = $this->repository->findById($licenseId);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $this->logAudit('LOGOUT_LICENSE', $adminName, $licenseId, 'Logout lisensi');
        $cmdResult = $this->sendCommand($licenseId, LicenseCommand::COMMAND_LOGOUT_LICENSE, null, $adminName);

        return [
            'success' => true,
            'message' => 'Perintah logout telah dikirim',
            'command_id' => $cmdResult['command_id'] ?? null,
        ];
    }

    public function getCommandHistory(int $licenseId): array
    {
        $commands = $this->commandRepository->findByLicenseId($licenseId);
        return [
            'success' => true,
            'data' => array_map(fn($c) => $c->toArray(), $commands),
        ];
    }

    public function getAllCommands(): array
    {
        $commands = $this->commandRepository->findAll();
        return [
            'success' => true,
            'data' => $commands,
        ];
    }

    public function getAuditLogs(): array
    {
        $logs = $this->auditRepository->findAll();
        return [
            'success' => true,
            'data' => $logs,
        ];
    }

    public function getConnectedLicenses(): array
    {
        $licenses = $this->repository->findAll();
        $connected = array_filter($licenses, fn($l) =>
            $l->lastOnline !== null &&
            strtotime($l->lastOnline) > time() - 300 // within 5 minutes
        );
        return [
            'success' => true,
            'data' => array_map(fn($l) => $l->toArray(), array_values($connected)),
            'total_online' => count($connected),
            'total_offline' => count($licenses) - count($connected),
        ];
    }

    private function generateLicenseKey(): string
    {
        $segments = [];
        $segments[] = 'NKG';
        $segments[] = date('Y');
        for ($i = 0; $i < 3; $i++) {
            $segments[] = strtoupper(bin2hex(random_bytes(2)));
        }
        return implode('-', $segments);
    }

    // ==================== LIFETIME LICENSE MANAGEMENT (TAHAP 6) ====================

    public function approveWithType(int $id, string $type, string $approvedBy, float $purchasePrice = 0, int $trialDays = 7): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }
        if ($license->licenseStatus !== 'PENDING') {
            return ['success' => false, 'message' => 'Status lisensi bukan PENDING'];
        }

        $licenseKey = $this->generateLicenseKey();
        while ($this->repository->findByLicenseKey($licenseKey) !== null) {
            $licenseKey = $this->generateLicenseKey();
        }

        if (strtoupper($type) === 'LIFETIME') {
            $this->repository->approveAsLifetime($id, $licenseKey, $approvedBy, $purchasePrice);
        } else {
            $this->repository->approveAsTrial($id, $licenseKey, $approvedBy, $trialDays);
        }

        $this->logHistory($id, 'APPROVE', "Lisensi disetujui sebagai $type" . ($type === 'LIFETIME' ? " dengan harga Rp" . number_format($purchasePrice, 0, ',', '.') : " trial $trialDays hari"), null, $type, $approvedBy);

        $updated = $this->repository->findById($id);
        return [
            'success' => true,
            'message' => 'Lisensi berhasil diaktifkan sebagai ' . $type,
            'data' => $updated->toArray(),
        ];
    }

    public function unblockLicense(int $id, string $adminName): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }
        if ($license->licenseStatus !== 'BLOCKED') {
            return ['success' => false, 'message' => 'Lisensi tidak dalam status BLOCKED'];
        }

        $this->repository->unblock($id);
        $this->logHistory($id, 'UNBLOCK', 'Lisensi diaktifkan kembali setelah diblokir', 'BLOCKED', 'ACTIVE', $adminName);

        return [
            'success' => true,
            'message' => 'Lisensi berhasil diaktifkan kembali',
            'data' => $this->repository->findById($id)->toArray(),
        ];
    }

    public function resetDeviceLicense(int $id, string $adminName): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $oldDevice = $license->deviceId;
        $this->repository->resetDevice($id);
        $this->logHistory($id, 'RESET_DEVICE', "Device direset. Device sebelumnya: " . ($oldDevice ?? '-'), null, null, $adminName);

        return [
            'success' => true,
            'message' => 'Device berhasil direset. Customer dapat aktivasi ulang.',
            'data' => $this->repository->findById($id)->toArray(),
        ];
    }

    public function editPrice(int $id, float $price, string $adminName): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $oldPrice = $license->purchasePrice;
        $this->repository->updatePurchasePrice($id, $price);
        $this->logHistory($id, 'EDIT_PRICE', "Harga diubah dari Rp" . number_format($oldPrice, 0, ',', '.') . " menjadi Rp" . number_format($price, 0, ',', '.'), (string)$oldPrice, (string)$price, $adminName);

        return [
            'success' => true,
            'message' => 'Harga berhasil diperbarui',
            'data' => $this->repository->findById($id)->toArray(),
        ];
    }

    public function editNotes(int $id, ?string $notes, string $adminName): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $oldNotes = $license->notes;
        $this->repository->updateNotesOnly($id, $notes);
        $this->logHistory($id, 'EDIT_NOTES', 'Catatan diperbarui', $oldNotes, $notes, $adminName);

        return [
            'success' => true,
            'message' => 'Catatan berhasil diperbarui',
            'data' => $this->repository->findById($id)->toArray(),
        ];
    }

    public function updateMajorVersion(int $id, string $version, string $adminName): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $oldVersion = $license->majorVersion;
        $this->repository->updateMajorVersion($id, $version);
        $this->logHistory($id, 'UPGRADE_VERSION', "Major version ditingkatkan dari v{$oldVersion} ke v{$version}", $oldVersion, $version, $adminName);

        return [
            'success' => true,
            'message' => "Major version berhasil diubah ke v{$version}",
            'data' => $this->repository->findById($id)->toArray(),
        ];
    }

    public function updateMaxTransfer(int $id, int $max, string $adminName): array
    {
        $license = $this->repository->findById($id);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        $oldMax = $license->maxTransfer;
        $this->repository->updateMaxTransfer($id, $max);
        $this->logHistory($id, 'EDIT_MAX_TRANSFER', "Batas transfer diubah dari {$oldMax} menjadi {$max}", (string)$oldMax, (string)$max, $adminName);

        return [
            'success' => true,
            'message' => 'Batas transfer berhasil diperbarui',
            'data' => $this->repository->findById($id)->toArray(),
        ];
    }

    public function getLicenseHistory(int $licenseId): array
    {
        $historyRepo = new \App\Infrastructure\Repository\HistoryLogRepository();
        $history = $historyRepo->findByLicenseId($licenseId);
        return [
            'success' => true,
            'data' => array_map(fn($h) => $h->toArray(), $history),
        ];
    }

    public function searchLicenses(string $q): array
    {
        if (empty($q)) {
            return $this->getAllLicenses();
        }
        $licenses = $this->repository->search($q);
        return [
            'success' => true,
            'data' => array_map(fn($l) => $l->toArray(), $licenses),
        ];
    }

    public function filterByType(string $type): array
    {
        if (empty($type) || $type === 'ALL') {
            return $this->getAllLicenses();
        }

        // Handle status filters
        $statusMap = [
            'BLOCKED' => 'BLOCKED',
            'TRIAL_EXPIRED' => 'TRIAL_EXPIRED',
            'ACTIVE' => 'ACTIVE',
            'PENDING' => 'PENDING',
        ];

        if (isset($statusMap[strtoupper($type)])) {
            $licenses = $this->repository->findByStatus(strtoupper($type));
        } else {
            $licenses = $this->repository->searchByLicenseType(strtoupper($type));
        }

        return [
            'success' => true,
            'data' => array_map(fn($l) => $l->toArray(), $licenses),
        ];
    }

    public function getExpiredTrials(): array
    {
        $licenses = $this->repository->findAll();
        $expired = array_filter($licenses, function ($l) {
            if ($l->licenseType !== 'TRIAL' || $l->licenseStatus !== 'ACTIVE') return false;
            if ($l->licenseExpired === null) return false;
            return strtotime($l->licenseExpired) < time();
        });

        foreach ($expired as $l) {
            $this->repository->setTrialExpired($l->id);
            $this->logHistory($l->id, 'TRIAL_EXPIRED', 'Masa trial telah berakhir', 'ACTIVE', 'TRIAL_EXPIRED', 'SYSTEM');
        }

        return [
            'success' => true,
            'message' => count($expired) . ' lisensi trial kedaluwarsa',
            'count' => count($expired),
        ];
    }

    // ==================== LICENSE CERTIFICATE SYSTEM (TAHAP 6.2) ====================

    public function generateCertificate(int $licenseId, string $generatedBy = 'SYSTEM'): array
    {
        $license = $this->repository->findById($licenseId);
        if ($license === null) {
            return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];
        }

        if ($license->licenseStatus !== 'ACTIVE' && $license->licenseStatus !== 'PENDING') {
            return ['success' => false, 'message' => 'Lisensi belum aktif, tidak bisa generate sertifikat'];
        }

        $certRepo = new \App\Infrastructure\Repository\LicenseCertificateRepository();

        $generator = new \App\Application\Services\CertificateGeneratorService();
        $certNumber = $generator->generateCertificateNumber();

        // Prevent duplicate certificate numbers
        $attempts = 0;
        while ($certRepo->findByCertificateNumber($certNumber) !== null && $attempts < 100) {
            $certNumber = $generator->generateCertificateNumber();
            $attempts++;
        }
        if ($attempts >= 100) {
            return ['success' => false, 'message' => 'Gagal generate nomor sertifikat'];
        }

        $activationDate = $license->activationDate ?? $license->approvedAt ?? $license->createdAt;

        $sigData = [
            'certificate_number' => $certNumber,
            'license_key' => $license->licenseKey,
            'product_key' => $license->productKey ?? $this->getProductKeyForLicense($license->id),
            'business_name' => $license->businessName,
            'owner_name' => $license->ownerName,
            'license_type' => $license->licenseType,
            'activation_date' => $activationDate,
        ];

        $signatureHash = $generator->generateSignature($sigData);

        $qrUrl = $this->getServerUrl() . '/verify.html?cert=' . urlencode($certNumber);

        $certId = $certRepo->create([
            'certificate_number' => $certNumber,
            'license_id' => $license->id,
            'license_key' => $license->licenseKey,
            'product_key' => $license->productKey ?? $this->getProductKeyForLicense($license->id),
            'business_name' => $license->businessName,
            'owner_name' => $license->ownerName,
            'phone_number' => $license->phoneNumber,
            'device_id' => $license->deviceId,
            'license_type' => $license->licenseType,
            'activation_date' => $activationDate,
            'signature_hash' => $signatureHash,
            'qr_data' => $qrUrl,
            'generated_by' => $generatedBy,
        ]);

        $cert = $certRepo->findById($certId);

        $this->logAudit('GENERATE_CERTIFICATE', $generatedBy, $license->id, "Sertifikat $certNumber digenerate untuk {$license->businessName}");

        return [
            'success' => true,
            'message' => 'Sertifikat berhasil digenerate',
            'data' => $cert->toArray(),
        ];
    }

    public function downloadCertificate(int $certId): array
    {
        $certRepo = new \App\Infrastructure\Repository\LicenseCertificateRepository();
        $cert = $certRepo->findById($certId);
        if ($cert === null) {
            return ['success' => false, 'message' => 'Sertifikat tidak ditemukan'];
        }

        $generator = new \App\Application\Services\CertificateGeneratorService();

        $pdfData = $generator->generatePdf($cert->toArray());

        return [
            'success' => true,
            'pdf' => base64_encode($pdfData),
            'filename' => 'sertifikat_' . $cert->certificateNumber . '.pdf',
            'certificate_number' => $cert->certificateNumber,
        ];
    }

    public function verifyCertificate(string $certificateNumber): array
    {
        $certRepo = new \App\Infrastructure\Repository\LicenseCertificateRepository();
        $cert = $certRepo->findByCertificateNumber($certificateNumber);
        if ($cert === null) {
            return ['success' => false, 'message' => 'Sertifikat tidak ditemukan', 'valid' => false];
        }

        $generator = new \App\Application\Services\CertificateGeneratorService();

        $expectedSig = $generator->generateSignature($cert->toArray());
        $isValid = hash_equals($expectedSig, $cert->signatureHash ?? '');

        return [
            'success' => true,
            'valid' => $isValid,
            'message' => $isValid ? 'Sertifikat valid' : 'Tanda tangan digital tidak valid',
            'data' => $cert->toArray(),
        ];
    }

    public function getCertificateByLicense(int $licenseId): array
    {
        $certRepo = new \App\Infrastructure\Repository\LicenseCertificateRepository();
        $cert = $certRepo->findByLicenseId($licenseId);
        if ($cert === null) {
            return ['success' => false, 'message' => 'Belum ada sertifikat untuk lisensi ini'];
        }
        return [
            'success' => true,
            'data' => $cert->toArray(),
        ];
    }

    public function getAllCertificates(): array
    {
        $certRepo = new \App\Infrastructure\Repository\LicenseCertificateRepository();
        $certs = $certRepo->findAll();
        return [
            'success' => true,
            'data' => array_map(fn($c) => $c->toArray(), $certs),
        ];
    }

    private function getProductKeyForLicense(int $licenseId): ?string
    {
        $license = $this->repository->findById($licenseId);
        if ($license === null) return null;
        return $license->productKey;
    }

    private function getServerUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        return $protocol . '://' . $host;
    }

    private function logHistory(int $licenseId, string $action, ?string $description = null, ?string $oldValue = null, ?string $newValue = null, ?string $adminName = null): void
    {
        $log = new \App\Domain\Entities\HistoryLog([
            'license_id' => $licenseId,
            'action' => $action,
            'description' => $description,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'admin_name' => $adminName,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        $repo = new \App\Infrastructure\Repository\HistoryLogRepository();
        $repo->create($log);
    }

    // ==================== PRODUCT KEY SYSTEM (TAHAP 6.1) ====================

    private function generateProductKeyString(string $licenseType = 'LIFETIME'): string
    {
        $prefix = $licenseType === 'TRIAL' ? 'TRIAL' : 'PRO';
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $segments = [];
        for ($i = 0; $i < 4; $i++) {
            $seg = '';
            for ($j = 0; $j < 4; $j++) {
                $seg .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $segments[] = $seg;
        }
        return 'NKG-' . $prefix . '-' . implode('-', $segments);
    }

    public function generateProductKey(array $data): array
    {
        $licenseType = $data['license_type'] ?? 'LIFETIME';
        $generatedBy = $data['generated_by'] ?? 'Admin';

        $key = $this->generateProductKeyString($licenseType);
        $attempts = 0;
        $repo = new \App\Infrastructure\Repository\ProductKeyRepository();
        while ($repo->findByProductKey($key) !== null && $attempts < 100) {
            $key = $this->generateProductKeyString($licenseType);
            $attempts++;
        }

        if ($attempts >= 100) {
            return ['success' => false, 'message' => 'Gagal generate key unik'];
        }

        $id = $repo->create([
            'product_key' => $key,
            'status' => 'UNUSED',
            'license_type' => $licenseType,
            'generated_by' => $generatedBy,
        ]);

        $pk = $repo->findById($id);
        $this->logAudit('GENERATE_PRODUCT_KEY', $generatedBy, null, "Product Key generated: $key");

        return [
            'success' => true,
            'message' => 'Product Key berhasil digenerate',
            'data' => $pk->toArray(),
        ];
    }

    public function generateMultipleProductKeys(array $data): array
    {
        $count = min((int)($data['count'] ?? 10), 1000);
        $licenseType = $data['license_type'] ?? 'LIFETIME';
        $generatedBy = $data['generated_by'] ?? 'Admin';

        $repo = new \App\Infrastructure\Repository\ProductKeyRepository();
        $keys = [];

        for ($i = 0; $i < $count; $i++) {
            $key = $this->generateProductKeyString($licenseType);
            $attempts = 0;
            while ($repo->findByProductKey($key) !== null && $attempts < 100) {
                $key = $this->generateProductKeyString($licenseType);
                $attempts++;
            }
            if ($attempts >= 100) continue;

            $repo->create([
                'product_key' => $key,
                'status' => 'UNUSED',
                'license_type' => $licenseType,
                'generated_by' => $generatedBy,
            ]);
            $keys[] = $key;
        }

        $this->logAudit('GENERATE_MULTIPLE_PRODUCT_KEYS', $generatedBy, null, count($keys) . " Product Keys generated");

        return [
            'success' => true,
            'message' => count($keys) . ' Product Key berhasil digenerate',
            'count' => count($keys),
        ];
    }

    public function validateProductKey(string $productKey): array
    {
        $repo = new \App\Infrastructure\Repository\ProductKeyRepository();
        $pk = $repo->findByProductKey($productKey);

        if ($pk === null) {
            return ['success' => false, 'message' => 'Product Key tidak valid', 'valid' => false];
        }

        if ($pk->status === 'USED') {
            return ['success' => false, 'message' => 'Product Key sudah digunakan', 'valid' => false];
        }

        if ($pk->status === 'BLOCKED') {
            return ['success' => false, 'message' => 'Product Key diblokir', 'valid' => false];
        }

        return [
            'success' => true,
            'message' => 'Product Key valid',
            'valid' => true,
            'data' => $pk->toArray(),
        ];
    }

    public function useProductKey(string $productKey, int $licenseId): array
    {
        $repo = new \App\Infrastructure\Repository\ProductKeyRepository();
        $pk = $repo->findByProductKey($productKey);

        if ($pk === null) {
            return ['success' => false, 'message' => 'Product Key tidak valid'];
        }

        if ($pk->status !== 'UNUSED') {
            return ['success' => false, 'message' => 'Product Key sudah tidak tersedia'];
        }

        $repo->markAsUsed($pk->id, $licenseId);
        $this->repository->updateProductKey($licenseId, $productKey);
        $this->logAudit('USE_PRODUCT_KEY', 'SYSTEM', $licenseId, "Product Key $productKey digunakan untuk lisensi #$licenseId");

        return ['success' => true, 'message' => 'Product Key berhasil digunakan'];
    }

    public function getAllProductKeys(): array
    {
        $repo = new \App\Infrastructure\Repository\ProductKeyRepository();
        $keys = $repo->findAll();
        return [
            'success' => true,
            'data' => array_map(fn($k) => $k->toArray(), $keys),
        ];
    }

    public function getProductKeyStats(): array
    {
        $repo = new \App\Infrastructure\Repository\ProductKeyRepository();
        return [
            'success' => true,
            'data' => [
                'total' => $repo->getTotalCount(),
                'unused' => $repo->countByStatus('UNUSED'),
                'used' => $repo->countByStatus('USED'),
                'blocked' => $repo->countByStatus('BLOCKED'),
            ],
        ];
    }

    public function searchProductKeys(string $q): array
    {
        if (empty($q)) {
            return $this->getAllProductKeys();
        }
        $repo = new \App\Infrastructure\Repository\ProductKeyRepository();
        $keys = $repo->search($q);
        return [
            'success' => true,
            'data' => array_map(fn($k) => $k->toArray(), $keys),
        ];
    }

    public function blockProductKey(int $id, string $adminName): array
    {
        $repo = new \App\Infrastructure\Repository\ProductKeyRepository();
        $pk = $repo->findById($id);
        if ($pk === null) {
            return ['success' => false, 'message' => 'Product Key tidak ditemukan'];
        }
        if ($pk->status === 'USED') {
            return ['success' => false, 'message' => 'Tidak bisa blokir Product Key yang sudah digunakan'];
        }

        $repo->block($id);
        $this->logAudit('BLOCK_PRODUCT_KEY', $adminName, null, "Product Key {$pk->productKey} diblokir");

        return ['success' => true, 'message' => 'Product Key berhasil diblokir'];
    }
}
