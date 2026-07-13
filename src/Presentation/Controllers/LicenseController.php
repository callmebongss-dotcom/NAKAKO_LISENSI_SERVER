<?php

namespace App\Presentation\Controllers;

use App\Application\Services\LicenseService;

class LicenseController
{
    private LicenseService $service;

    public function __construct()
    {
        $this->service = new LicenseService();
    }

    public function request(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->requestActivation($data);
        $this->jsonResponse($result);
    }

    public function activateByKey(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->activateByKey($data);
        $this->jsonResponse($result);
    }

    public function createByDevice(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->createByDevice($data);
        $this->jsonResponse($result);
    }

    public function activateByLicenseKey(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->activateByLicenseKey($data);
        $this->jsonResponse($result);
    }

    public function status(string $deviceId): void
    {
        $result = $this->service->checkStatus($deviceId);
        $this->jsonResponse($result);
    }

    public function approve(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['id'] ?? 0);
        $approvedBy = $data['approved_by'] ?? 'Admin';
        $result = $this->service->approve($id, $approvedBy);
        $this->jsonResponse($result);
    }

    public function approveWithType(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['id'] ?? 0);
        $type = $data['license_type'] ?? 'TRIAL';
        $purchasePrice = (float) ($data['purchase_price'] ?? 0);
        $trialDays = (int) ($data['trial_days'] ?? 7);
        $approvedBy = $data['approved_by'] ?? 'Admin';
        $result = $this->service->approveWithType($id, $type, $approvedBy, $purchasePrice, $trialDays);
        $this->jsonResponse($result);
    }

    public function unblockLicense(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->unblockLicense($id, $adminName);
        $this->jsonResponse($result);
    }

    public function resetDevice(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->resetDeviceLicense($id, $adminName);
        $this->jsonResponse($result);
    }

    public function editPrice(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $price = (float) ($data['price'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->editPrice($id, $price, $adminName);
        $this->jsonResponse($result);
    }

    public function editNotes(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $notes = $data['notes'] ?? null;
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->editNotes($id, $notes, $adminName);
        $this->jsonResponse($result);
    }

    public function updateMajorVersion(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $version = $data['major_version'] ?? '1';
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->updateMajorVersion($id, $version, $adminName);
        $this->jsonResponse($result);
    }

    public function updateMaxTransfer(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $max = (int) ($data['max_transfer'] ?? 3);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->updateMaxTransfer($id, $max, $adminName);
        $this->jsonResponse($result);
    }

    public function getHistory(int $licenseId): void
    {
        $result = $this->service->getLicenseHistory($licenseId);
        $this->jsonResponse($result);
    }

    public function searchLicenses(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $q = $data['q'] ?? '';
        $result = $this->service->searchLicenses($q);
        $this->jsonResponse($result);
    }

    public function filterByType(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $type = $data['license_type'] ?? '';
        $result = $this->service->filterByType($type);
        $this->jsonResponse($result);
    }

    public function reject(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['id'] ?? 0);
        $remarks = $data['remarks'] ?? '';
        $approvedBy = $data['approved_by'] ?? 'Admin';
        $result = $this->service->reject($id, $remarks, $approvedBy);
        $this->jsonResponse($result);
    }

    public function list(): void
    {
        $result = $this->service->getAllLicenses();
        $this->jsonResponse($result);
    }

    public function stats(): void
    {
        $result = $this->service->getDashboardStats();
        $this->jsonResponse($result);
    }

    public function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $token = base64_encode(json_encode([
                'username' => $username,
                'exp' => time() + 86400,
            ]));
            $this->jsonResponse(['success' => true, 'token' => $token]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Username atau password salah']);
        }
    }

    public function validateDevice(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->validateDevice($data);
        $this->jsonResponse($result);
    }

    public function transfer(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->transferLicense($data);
        $this->jsonResponse($result);
    }

    public function transferHistory(int $id): void
    {
        $result = $this->service->getTransferHistory($id);
        $this->jsonResponse($result);
    }

    public function renewToken(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->renewToken($data);
        $this->jsonResponse($result);
    }

    public function policy(string $deviceId): void
    {
        $result = $this->service->getLicensePolicy($deviceId);
        $this->jsonResponse($result);
    }

    public function plansList(): void
    {
        $result = $this->service->getPlans();
        $this->jsonResponse($result);
    }

    public function planCreate(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->createPlan($data);
        $this->jsonResponse($result);
    }

    public function planUpdate(int $id): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->updatePlan($id, $data);
        $this->jsonResponse($result);
    }

    public function planDelete(int $id): void
    {
        $result = $this->service->deletePlan($id);
        $this->jsonResponse($result);
    }

    public function setLicensePlan(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $licenseId = (int) ($data['license_id'] ?? 0);
        $planId = (int) ($data['plan_id'] ?? 0);
        $result = $this->service->setLicensePlan($licenseId, $planId);
        $this->jsonResponse($result);
    }

    // ==================== LICENSE CONTROL (TAHAP 5) ====================

    public function heartbeat(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->heartbeat($data);
        $this->jsonResponse($result);
    }

    public function getPendingCommands(string $deviceId): void
    {
        $result = $this->service->getPendingCommands($deviceId);
        $this->jsonResponse($result);
    }

    public function reportCommandResult(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->reportCommandResult($data);
        $this->jsonResponse($result);
    }

    public function block(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $reason = $data['reason'] ?? null;
        $result = $this->service->blockLicense($id, $adminName, $reason);
        $this->jsonResponse($result);
    }

    public function suspend(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $reason = $data['reason'] ?? null;
        $result = $this->service->suspendLicense($id, $adminName, $reason);
        $this->jsonResponse($result);
    }

    public function unsuspend(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->unsuspendLicense($id, $adminName);
        $this->jsonResponse($result);
    }

    public function activate(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->activateLicense($id, $adminName);
        $this->jsonResponse($result);
    }

    public function extend(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['license_id'] ?? 0);
        $days = (int) ($data['days'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->extendLicense($id, $days, $adminName);
        $this->jsonResponse($result);
    }

    public function changePlan(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $licenseId = (int) ($data['license_id'] ?? 0);
        $planId = (int) ($data['plan_id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->changePlan($licenseId, $planId, $adminName);
        $this->jsonResponse($result);
    }

    public function sendMessage(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $licenseId = (int) ($data['license_id'] ?? 0);
        $message = $data['message'] ?? '';
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->sendMessage($licenseId, $message, $adminName);
        $this->jsonResponse($result);
    }

    public function forceSync(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $licenseId = (int) ($data['license_id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->forceSync($licenseId, $adminName);
        $this->jsonResponse($result);
    }

    public function restartApp(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $licenseId = (int) ($data['license_id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->restartApp($licenseId, $adminName);
        $this->jsonResponse($result);
    }

    public function logoutLicense(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $licenseId = (int) ($data['license_id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        $result = $this->service->logoutLicense($licenseId, $adminName);
        $this->jsonResponse($result);
    }

    public function commandHistory(int $licenseId): void
    {
        $result = $this->service->getCommandHistory($licenseId);
        $this->jsonResponse($result);
    }

    public function allCommands(): void
    {
        $result = $this->service->getAllCommands();
        $this->jsonResponse($result);
    }

    public function auditLogs(): void
    {
        $result = $this->service->getAuditLogs();
        $this->jsonResponse($result);
    }

    public function connected(): void
    {
        $result = $this->service->getConnectedLicenses();
        $this->jsonResponse($result);
    }

    // ==================== LICENSE CERTIFICATE (TAHAP 6.2) ====================

    public function generateCertificate(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $licenseId = (int) ($data['license_id'] ?? 0);
        $generatedBy = $data['generated_by'] ?? 'Admin';
        if ($licenseId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'license_id wajib diisi']);
            return;
        }
        $result = $this->service->generateCertificate($licenseId, $generatedBy);
        $this->jsonResponse($result);
    }

    public function downloadCertificate(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $certId = (int) ($data['cert_id'] ?? 0);
        if ($certId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'cert_id wajib diisi']);
            return;
        }
        $result = $this->service->downloadCertificate($certId);
        $this->jsonResponse($result);
    }

    public function verifyCertificate(string $certificateNumber): void
    {
        $result = $this->service->verifyCertificate($certificateNumber);
        $this->jsonResponse($result);
    }

    public function getCertificateByLicense(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $licenseId = (int) ($data['license_id'] ?? 0);
        if ($licenseId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'license_id wajib diisi']);
            return;
        }
        $result = $this->service->getCertificateByLicense($licenseId);
        $this->jsonResponse($result);
    }

    public function certificateList(): void
    {
        $result = $this->service->getAllCertificates();
        $this->jsonResponse($result);
    }

    // ==================== PRODUCT KEY (TAHAP 6.1) ====================

    public function generateProductKey(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->generateProductKey($data);
        $this->jsonResponse($result);
    }

    public function generateMultipleProductKeys(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->service->generateMultipleProductKeys($data);
        $this->jsonResponse($result);
    }

    public function validateProductKey(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $productKey = $data['product_key'] ?? '';
        if (empty($productKey)) {
            $this->jsonResponse(['success' => false, 'message' => 'product_key wajib diisi']);
            return;
        }
        $result = $this->service->validateProductKey($productKey);
        $this->jsonResponse($result);
    }

    public function useProductKey(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $productKey = $data['product_key'] ?? '';
        $licenseId = (int) ($data['license_id'] ?? 0);
        if (empty($productKey) || $licenseId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'product_key dan license_id wajib diisi']);
            return;
        }
        $result = $this->service->useProductKey($productKey, $licenseId);
        $this->jsonResponse($result);
    }

    public function productKeyList(): void
    {
        $result = $this->service->getAllProductKeys();
        $this->jsonResponse($result);
    }

    public function productKeyStats(): void
    {
        $result = $this->service->getProductKeyStats();
        $this->jsonResponse($result);
    }

    public function searchProductKeys(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $q = $data['q'] ?? '';
        $result = $this->service->searchProductKeys($q);
        $this->jsonResponse($result);
    }

    public function blockProductKey(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($data['id'] ?? 0);
        $adminName = $data['admin_name'] ?? 'Admin';
        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'id wajib diisi']);
            return;
        }
        $result = $this->service->blockProductKey($id, $adminName);
        $this->jsonResponse($result);
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        echo json_encode($data);
    }
}
