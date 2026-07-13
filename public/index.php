<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Infrastructure/Database/Database.php';
require_once __DIR__ . '/../src/Domain/Entities/License.php';
require_once __DIR__ . '/../src/Domain/Entities/LicensePlan.php';
require_once __DIR__ . '/../src/Domain/Entities/LicenseCommand.php';
require_once __DIR__ . '/../src/Domain/Entities/AuditLog.php';
require_once __DIR__ . '/../src/Domain/Entities/HistoryLog.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/HistoryLogRepository.php';
require_once __DIR__ . '/../src/Domain/Entities/ProductKey.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/ProductKeyRepository.php';
require_once __DIR__ . '/../src/Domain/Entities/SubscriptionPlan.php';
require_once __DIR__ . '/../src/Domain/Entities/Subscription.php';
require_once __DIR__ . '/../src/Domain/Entities/Invoice.php';
require_once __DIR__ . '/../src/Domain/Entities/PaymentTransaction.php';
require_once __DIR__ . '/../src/Domain/PaymentProvider.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/LicenseRepository.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/LicensePlanRepository.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/LicenseCommandRepository.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/AuditLogRepository.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/SubscriptionPlanRepository.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/SubscriptionRepository.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/InvoiceRepository.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/PaymentTransactionRepository.php';
require_once __DIR__ . '/../src/Application/Services/LicenseService.php';
require_once __DIR__ . '/../src/Application/Services/CertificateGeneratorService.php';
require_once __DIR__ . '/../src/Application/Services/BillingService.php';
require_once __DIR__ . '/../src/Domain/Entities/LicenseCertificate.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/LicenseCertificateRepository.php';
require_once __DIR__ . '/../src/Presentation/Controllers/LicenseController.php';
require_once __DIR__ . '/../src/Presentation/Controllers/BillingController.php';
require_once __DIR__ . '/../src/Domain/Entities/AppUpdate.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/AppUpdateRepository.php';
require_once __DIR__ . '/../src/Domain/Entities/LicensePackage.php';
require_once __DIR__ . '/../src/Infrastructure/Repository/LicensePackageRepository.php';
require_once __DIR__ . '/../src/Application/Services/PackageService.php';
require_once __DIR__ . '/../src/Presentation/Controllers/PackageController.php';
require_once __DIR__ . '/../src/Presentation/Controllers/UpdateController.php';

use App\Infrastructure\Database\Database;
use App\Presentation\Controllers\LicenseController;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/' || $uri === '') {
    echo json_encode(['status' => 'ok', 'app' => 'NAKAKO LICENSE SERVER']);
    exit;
}

if (str_starts_with($uri, '/api/') || $uri === '/api/admin') {
    Database::initialize();

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $uri = rtrim($uri, '/');
    $controller = new LicenseController();

    try {
        switch (true) {
            case $uri === '/api/license/request' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->request();
                break;

            case $uri === '/api/license/activate-by-key' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->activateByKey();
                break;

            case $uri === '/api/license/create-by-device' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->createByDevice();
                break;

            case $uri === '/api/license/activate-by-license-key' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->activateByLicenseKey();
                break;

            case preg_match('#^/api/license/status/([A-Za-z0-9\-]+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->status($m[1]);
                break;

            case $uri === '/api/license/approve' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->approve();
                break;

            case $uri === '/api/license/reject' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->reject();
                break;

            case $uri === '/api/license/list' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->list();
                break;

            case $uri === '/api/license/stats' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->stats();
                break;

            case $uri === '/api/auth/login' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->login();
                break;

            case $uri === '/api/license/validate' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->validateDevice();
                break;

            case $uri === '/api/license/transfer' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->transfer();
                break;

            case preg_match('#^/api/license/transfer-history/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->transferHistory((int)$m[1]);
                break;

            case $uri === '/api/license/renew-token' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->renewToken();
                break;

            case preg_match('#^/api/license/policy/([A-Za-z0-9\-]+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->policy($m[1]);
                break;

            case $uri === '/api/plans' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->plansList();
                break;

            case $uri === '/api/plans' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->planCreate();
                break;

            case preg_match('#^/api/plans/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'PUT':
                $controller->planUpdate((int)$m[1]);
                break;

            case preg_match('#^/api/plans/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'DELETE':
                $controller->planDelete((int)$m[1]);
                break;

            case $uri === '/api/license/set-plan' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->setLicensePlan();
                break;

            // ==================== TAHAP 5: LICENSE CONTROL ====================
            case $uri === '/api/license/heartbeat' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->heartbeat();
                break;

            case preg_match('#^/api/license/commands/([A-Za-z0-9\-]+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->getPendingCommands($m[1]);
                break;

            case $uri === '/api/license/command-result' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->reportCommandResult();
                break;

            case $uri === '/api/license/control/block' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->block();
                break;

            case $uri === '/api/license/control/suspend' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->suspend();
                break;

            case $uri === '/api/license/control/unsuspend' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->unsuspend();
                break;

            case $uri === '/api/license/control/activate' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->activate();
                break;

            case $uri === '/api/license/control/extend' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->extend();
                break;

            case $uri === '/api/license/control/change-plan' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->changePlan();
                break;

            case $uri === '/api/license/control/send-message' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->sendMessage();
                break;

            case $uri === '/api/license/control/force-sync' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->forceSync();
                break;

            case $uri === '/api/license/control/restart-app' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->restartApp();
                break;

            case $uri === '/api/license/control/logout' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->logoutLicense();
                break;

            case preg_match('#^/api/license/command-history/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->commandHistory((int)$m[1]);
                break;

            case $uri === '/api/license/commands-all' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->allCommands();
                break;

            case $uri === '/api/license/audit-logs' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->auditLogs();
                break;

            case $uri === '/api/license/connected' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->connected();
                break;

            // ==================== TAHAP 6: LIFETIME LICENSE MANAGEMENT ====================
            case $uri === '/api/license/approve-with-type' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->approveWithType();
                break;

            case $uri === '/api/license/control/unblock' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->unblockLicense();
                break;

            case $uri === '/api/license/control/reset-device' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->resetDevice();
                break;

            case $uri === '/api/license/control/edit-price' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->editPrice();
                break;

            case $uri === '/api/license/control/edit-notes' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->editNotes();
                break;

            case $uri === '/api/license/control/upgrade-version' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->updateMajorVersion();
                break;

            case $uri === '/api/license/control/update-max-transfer' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->updateMaxTransfer();
                break;

            case preg_match('#^/api/license/history/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->getHistory((int)$m[1]);
                break;

            case $uri === '/api/license/search' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->searchLicenses();
                break;

            case $uri === '/api/license/filter' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->filterByType();
                break;

            case $uri === '/api/license/check-expired-trials' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller = new \App\Presentation\Controllers\LicenseController();
                $service = new \App\Application\Services\LicenseService();
                $result = $service->getExpiredTrials();
                echo json_encode($result);
                break;

            // ==================== TAHAP 6: BILLING & SUBSCRIPTION ====================
            case $uri === '/api/billing/init' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->stats();
                break;

            // Subscription Plans
            case $uri === '/api/billing/plans' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->plansList();
                break;

            case $uri === '/api/billing/plans/active' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->plansActive();
                break;

            case $uri === '/api/billing/plans' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->planCreate();
                break;

            case preg_match('#^/api/billing/plans/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'PUT':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->planUpdate((int)$m[1]);
                break;

            case preg_match('#^/api/billing/plans/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'DELETE':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->planDelete((int)$m[1]);
                break;

            // Subscriptions
            case $uri === '/api/billing/subscriptions' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->subscriptions();
                break;

            case preg_match('#^/api/billing/subscriptions/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->subscription((int)$m[1]);
                break;

            case $uri === '/api/billing/subscriptions' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->createSubscription();
                break;

            case $uri === '/api/billing/subscriptions/activate' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->activateSubscription();
                break;

            case preg_match('#^/api/billing/subscriptions/license/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->licenseSubscription((int)$m[1]);
                break;

            // Invoices
            case $uri === '/api/billing/invoices' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->invoices();
                break;

            case preg_match('#^/api/billing/invoices/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->invoice((int)$m[1]);
                break;

            case preg_match('#^/api/billing/invoices/license/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->licenseInvoices((int)$m[1]);
                break;

            // Payments
            case $uri === '/api/billing/payments' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->payments();
                break;

            case $uri === '/api/billing/payments/process' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->processPayment();
                break;

            // Stats & Auto
            case $uri === '/api/billing/stats' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->stats();
                break;

            case $uri === '/api/billing/check-expired' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->checkExpired();
                break;

            case preg_match('#^/api/billing/expiring/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->expiringSoon((int)$m[1]);
                break;

            case $uri === '/api/billing/extend' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $billing = new \App\Presentation\Controllers\BillingController();
                $billing->manualExtend();
                break;

            // ==================== TAHAP 6.1: PRODUCT KEY SYSTEM ====================
            case $uri === '/api/product-key/generate' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->generateProductKey();
                break;

            case $uri === '/api/product-key/generate-multiple' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->generateMultipleProductKeys();
                break;

            case $uri === '/api/product-key/validate' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->validateProductKey();
                break;

            case $uri === '/api/product-key/use' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->useProductKey();
                break;

            case $uri === '/api/product-key/list' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->productKeyList();
                break;

            case $uri === '/api/product-key/stats' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->productKeyStats();
                break;

            case $uri === '/api/product-key/search' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->searchProductKeys();
                break;

            case $uri === '/api/product-key/block' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->blockProductKey();
                break;

            // ==================== TAHAP 6.4: AUTO UPDATE SYSTEM ====================
            case $uri === '/api/update/check' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $updateCtrl = new \App\Presentation\Controllers\UpdateController();
                $updateCtrl->check();
                break;

            case $uri === '/api/update/list' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $updateCtrl = new \App\Presentation\Controllers\UpdateController();
                $updateCtrl->list();
                break;

            case $uri === '/api/update/create' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $updateCtrl = new \App\Presentation\Controllers\UpdateController();
                $updateCtrl->create();
                break;

            case preg_match('#^/api/update/delete/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'DELETE':
                $updateCtrl = new \App\Presentation\Controllers\UpdateController();
                $updateCtrl->delete((int)$m[1]);
                break;

            // ==================== TAHAP 6.2: LICENSE CERTIFICATE SYSTEM ====================
            case $uri === '/api/certificate/generate' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->generateCertificate();
                break;

            case $uri === '/api/certificate/download' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->downloadCertificate();
                break;

            case preg_match('#^/api/certificate/verify/([A-Za-z0-9\-]+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->verifyCertificate($m[1]);
                break;

            case $uri === '/api/certificate/by-license' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $controller->getCertificateByLicense();
                break;

            case $uri === '/api/certificate/list' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $controller->certificateList();
                break;

            // ==================== TASK 11.1: LICENSE PACKAGES ====================
            case $uri === '/api/packages' && $_SERVER['REQUEST_METHOD'] === 'GET':
                $pkgCtrl = new \App\Presentation\Controllers\PackageController();
                $pkgCtrl->list();
                break;

            case preg_match('#^/api/packages/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'GET':
                $pkgCtrl = new \App\Presentation\Controllers\PackageController();
                $pkgCtrl->get((int)$m[1]);
                break;

            case $uri === '/api/packages' && $_SERVER['REQUEST_METHOD'] === 'POST':
                $pkgCtrl = new \App\Presentation\Controllers\PackageController();
                $pkgCtrl->create();
                break;

            case preg_match('#^/api/packages/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'PUT':
                $pkgCtrl = new \App\Presentation\Controllers\PackageController();
                $pkgCtrl->update((int)$m[1]);
                break;

            case preg_match('#^/api/packages/(\d+)$#', $uri, $m) === 1 && $_SERVER['REQUEST_METHOD'] === 'DELETE':
                $pkgCtrl = new \App\Presentation\Controllers\PackageController();
                $pkgCtrl->delete((int)$m[1]);
                break;

            case $uri === '/api/admin':
                echo json_encode(['success' => true, 'message' => 'NAKAKO LICENSE SERVER API v1.0']);
                break;

            default:
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint tidak ditemukan']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$filePath = __DIR__ . $uri;

if (is_dir($filePath)) {
    $indexFile = rtrim($filePath, '/') . '/index.html';
    if (file_exists($indexFile)) {
        $filePath = $indexFile;
    } else {
        http_response_code(404);
        echo '404 Not Found';
        exit;
    }
}

if (file_exists($filePath) && is_file($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    header("Content-Type: $mime");
    readfile($filePath);
    return true;
}

http_response_code(404);
echo '404 Not Found';
return true;
