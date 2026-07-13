<?php

namespace App\Presentation\Controllers;

use App\Application\Services\BillingService;

class BillingController
{
    private BillingService $service;

    public function __construct()
    {
        $this->service = new BillingService();
    }

    public function plansList(): void { $this->json($this->service->getPlans()); }
    public function plansActive(): void { $this->json($this->service->getActivePlans()); }
    public function planCreate(): void { $data = json_decode(file_get_contents('php://input'), true) ?? []; $this->json($this->service->createPlan($data)); }
    public function planUpdate(int $id): void { $data = json_decode(file_get_contents('php://input'), true) ?? []; $this->json($this->service->updatePlan($id, $data)); }
    public function planDelete(int $id): void { $this->json($this->service->deletePlan($id)); }

    public function subscriptions(): void { $this->json($this->service->getSubscriptions()); }
    public function subscription(int $id): void { $this->json($this->service->getSubscription($id)); }
    public function licenseSubscription(int $licenseId): void { $this->json($this->service->getLicenseSubscription($licenseId)); }
    public function createSubscription(): void { $data = json_decode(file_get_contents('php://input'), true) ?? []; $licenseId = (int) ($data['license_id'] ?? 0); $planId = (int) ($data['plan_id'] ?? 0); $this->json($this->service->createSubscription($licenseId, $planId)); }
    public function activateSubscription(): void { $data = json_decode(file_get_contents('php://input'), true) ?? []; $id = (int) ($data['id'] ?? 0); $this->json($this->service->activateSubscription($id)); }

    public function invoices(): void { $this->json($this->service->getInvoices()); }
    public function invoice(int $id): void { $this->json($this->service->getInvoice($id)); }
    public function licenseInvoices(int $licenseId): void { $this->json($this->service->getLicenseInvoices($licenseId)); }

    public function payments(): void { $this->json($this->service->getPayments()); }
    public function processPayment(): void { $data = json_decode(file_get_contents('php://input'), true) ?? []; $invoiceId = (int) ($data['invoice_id'] ?? 0); $provider = $data['provider'] ?? 'manual'; $ref = $data['reference'] ?? bin2hex(random_bytes(8)); $method = $data['payment_method'] ?? 'manual'; $this->json($this->service->processPayment($invoiceId, $provider, $ref, $method)); }

    public function stats(): void { $this->json($this->service->getBillingStats()); }
    public function checkExpired(): void { $this->json($this->service->checkExpiredSubscriptions()); }
    public function expiringSoon(int $days): void { $this->json($this->service->getExpiringSoon($days)); }
    public function manualExtend(): void { $data = json_decode(file_get_contents('php://input'), true) ?? []; $licenseId = (int) ($data['license_id'] ?? 0); $days = (int) ($data['days'] ?? 0); $this->json($this->service->manualExtend($licenseId, $days)); }

    private function json(array $data): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        echo json_encode($data);
    }
}
