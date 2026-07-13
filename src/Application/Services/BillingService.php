<?php

namespace App\Application\Services;

use App\Domain\Entities\Subscription;
use App\Infrastructure\Repository\LicenseRepository;
use App\Infrastructure\Repository\SubscriptionPlanRepository;
use App\Infrastructure\Repository\SubscriptionRepository;
use App\Infrastructure\Repository\InvoiceRepository;
use App\Infrastructure\Repository\PaymentTransactionRepository;
use App\Infrastructure\Repository\AuditLogRepository;

class BillingService
{
    private LicenseRepository $licenseRepo;
    private SubscriptionPlanRepository $planRepo;
    private SubscriptionRepository $subscriptionRepo;
    private InvoiceRepository $invoiceRepo;
    private PaymentTransactionRepository $paymentRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->licenseRepo = new LicenseRepository();
        $this->planRepo = new SubscriptionPlanRepository();
        $this->subscriptionRepo = new SubscriptionRepository();
        $this->invoiceRepo = new InvoiceRepository();
        $this->paymentRepo = new PaymentTransactionRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    // ==================== SUBSCRIPTION PLANS ====================

    public function getPlans(): array
    {
        return ['success' => true, 'data' => array_map(fn($p) => $p->toArray(), $this->planRepo->findAll())];
    }

    public function getActivePlans(): array
    {
        return ['success' => true, 'data' => array_map(fn($p) => $p->toArray(), $this->planRepo->findActive())];
    }

    public function createPlan(array $data): array
    {
        if (empty($data['name'])) {
            return ['success' => false, 'message' => 'Nama plan wajib diisi'];
        }
        $id = $this->planRepo->create($data);
        $plan = $this->planRepo->findById($id);
        return ['success' => true, 'message' => 'Plan berhasil dibuat', 'data' => $plan->toArray()];
    }

    public function updatePlan(int $id, array $data): array
    {
        $plan = $this->planRepo->findById($id);
        if ($plan === null) return ['success' => false, 'message' => 'Plan tidak ditemukan'];
        $this->planRepo->update($id, $data);
        $updated = $this->planRepo->findById($id);
        return ['success' => true, 'message' => 'Plan berhasil diperbarui', 'data' => $updated->toArray()];
    }

    public function deletePlan(int $id): array
    {
        $plan = $this->planRepo->findById($id);
        if ($plan === null) return ['success' => false, 'message' => 'Plan tidak ditemukan'];
        $this->planRepo->delete($id);
        return ['success' => true, 'message' => 'Plan berhasil dihapus'];
    }

    // ==================== SUBSCRIPTIONS ====================

    public function getSubscriptions(): array
    {
        return ['success' => true, 'data' => array_map(fn($s) => $s->toArray(), $this->subscriptionRepo->findAll())];
    }

    public function getSubscription(int $id): array
    {
        $sub = $this->subscriptionRepo->findById($id);
        if ($sub === null) return ['success' => false, 'message' => 'Subscription tidak ditemukan'];
        return ['success' => true, 'data' => $sub->toArray()];
    }

    public function getLicenseSubscription(int $licenseId): array
    {
        $sub = $this->subscriptionRepo->findByLicenseId($licenseId);
        if ($sub === null) return ['success' => false, 'message' => 'Belum ada subscription'];
        return ['success' => true, 'data' => $sub->toArray()];
    }

    public function createSubscription(int $licenseId, int $planId): array
    {
        $license = $this->licenseRepo->findById($licenseId);
        if ($license === null) return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];

        $plan = $this->planRepo->findById($planId);
        if ($plan === null) return ['success' => false, 'message' => 'Plan tidak ditemukan'];

        $existing = $this->subscriptionRepo->findByLicenseId($licenseId);
        if ($existing !== null && $existing->status === Subscription::STATUS_ACTIVE) {
            return ['success' => false, 'message' => 'Lisensi sudah memiliki subscription aktif'];
        }

        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime("+{$plan->durationDays} days"));
        $graceEnd = date('Y-m-d H:i:s', strtotime("+{$plan->durationDays} days +7 days"));

        $subId = $this->subscriptionRepo->create([
            'license_id' => $licenseId,
            'plan_id' => $planId,
            'status' => $plan->price > 0 ? 'PENDING' : 'ACTIVE',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'grace_end_date' => $graceEnd,
            'auto_renew' => 0,
        ]);

        $invoice = $this->generateInvoice($licenseId, $planId, $subId);

        if ($plan->price <= 0) {
            // Free plan - auto activate
            $this->activateSubscription($subId);
        }

        $sub = $this->subscriptionRepo->findById($subId);
        return [
            'success' => true,
            'message' => 'Subscription berhasil dibuat',
            'data' => $sub->toArray(),
            'invoice' => $invoice['data'] ?? null,
        ];
    }

    public function activateSubscription(int $subId): array
    {
        $sub = $this->subscriptionRepo->findById($subId);
        if ($sub === null) return ['success' => false, 'message' => 'Subscription tidak ditemukan'];

        $this->subscriptionRepo->update($subId, ['status' => 'ACTIVE', 'start_date' => date('Y-m-d H:i:s')]);

        // Auto-activate license
        $license = $this->licenseRepo->findById($sub->licenseId);
        if ($license && $license->licenseStatus !== 'ACTIVE') {
            if ($license->licenseKey) {
                $this->licenseRepo->approve($license->id, $license->licenseKey, 'SYSTEM');
            } else {
                $key = $this->generateLicenseKey();
                $this->licenseRepo->approve($license->id, $key, 'SYSTEM');
            }
        }
        // Set plan on license
        $this->licenseRepo->setPlan($sub->licenseId, $sub->planId);

        $this->auditRepo->create([
            'action' => 'SUBSCRIPTION_ACTIVATED',
            'admin_name' => 'SYSTEM',
            'license_id' => $sub->licenseId,
            'details' => "Subscription #$subId activated",
        ]);

        $updated = $this->subscriptionRepo->findById($subId);
        return ['success' => true, 'message' => 'Subscription diaktifkan', 'data' => $updated->toArray()];
    }

    // ==================== INVOICES ====================

    public function getInvoices(): array
    {
        return ['success' => true, 'data' => array_map(fn($i) => $i->toArray(), $this->invoiceRepo->findAll())];
    }

    public function getInvoice(int $id): array
    {
        $inv = $this->invoiceRepo->findById($id);
        if ($inv === null) return ['success' => false, 'message' => 'Invoice tidak ditemukan'];
        return ['success' => true, 'data' => $inv->toArray()];
    }

    public function getLicenseInvoices(int $licenseId): array
    {
        return ['success' => true, 'data' => array_map(fn($i) => $i->toArray(), $this->invoiceRepo->findByLicenseId($licenseId))];
    }

    private function generateInvoice(int $licenseId, int $planId, ?int $subscriptionId = null): array
    {
        $plan = $this->planRepo->findById($planId);
        if ($plan === null) return ['success' => false, 'message' => 'Plan tidak ditemukan'];

        $number = $this->invoiceRepo->getNextNumber();
        $amount = $plan->price;
        $tax = $amount * 0.11; // 11% PPN
        $total = $amount + $tax;

        $id = $this->invoiceRepo->create([
            'invoice_number' => $number,
            'subscription_id' => $subscriptionId,
            'license_id' => $licenseId,
            'plan_id' => $planId,
            'amount' => $amount,
            'tax' => $tax,
            'total' => $total,
            'status' => 'UNPAID',
            'due_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
        ]);

        $invoice = $this->invoiceRepo->findById($id);
        return ['success' => true, 'message' => 'Invoice dibuat', 'data' => $invoice->toArray()];
    }

    // ==================== PAYMENTS ====================

    public function getPayments(): array
    {
        return ['success' => true, 'data' => array_map(fn($p) => $p->toArray(), $this->paymentRepo->findAll())];
    }

    public function processPayment(int $invoiceId, string $provider, string $providerReference, string $paymentMethod): array
    {
        $invoice = $this->invoiceRepo->findById($invoiceId);
        if ($invoice === null) return ['success' => false, 'message' => 'Invoice tidak ditemukan'];
        if ($invoice->status === 'PAID') return ['success' => false, 'message' => 'Invoice sudah dibayar'];

        $license = $this->licenseRepo->findById($invoice->licenseId);
        if ($license === null) return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];

        $sub = $this->subscriptionRepo->findByLicenseId($invoice->licenseId);

        $txId = $this->paymentRepo->create([
            'invoice_id' => $invoiceId,
            'subscription_id' => $sub ? $sub->id : null,
            'license_id' => $invoice->licenseId,
            'amount' => $invoice->total,
            'provider' => $provider,
            'provider_reference' => $providerReference,
            'status' => 'PAID',
            'payment_method' => $paymentMethod,
            'paid_at' => date('Y-m-d H:i:s'),
        ]);

        $this->invoiceRepo->markPaid($invoiceId);

        if ($sub) {
            $this->activateSubscription($sub->id);
        }

        $plan = $this->planRepo->findById($invoice->planId);
        if ($plan && $plan->durationDays > 0) {
            $currentEnd = $license->licenseExpired ? strtotime($license->licenseExpired) : time();
            if ($currentEnd < time()) $currentEnd = time();
            $newEnd = date('Y-m-d H:i:s', strtotime("+{$plan->durationDays} days", $currentEnd));
            try {
                $this->licenseRepo->extendExpiry($license->id, $newEnd);
            } catch (\Exception $e) {
                // Table column may not exist yet for existing licenses
            }
        }

        $this->auditRepo->create([
            'action' => 'PAYMENT_RECEIVED',
            'admin_name' => 'SYSTEM',
            'license_id' => $invoice->licenseId,
            'details' => "Pembayaran {$invoice->invoiceNumber} via $provider: Rp" . number_format($invoice->total, 0, ',', '.'),
        ]);

        $tx = $this->paymentRepo->findById($txId);
        return ['success' => true, 'message' => 'Pembayaran berhasil diproses', 'data' => $tx->toArray()];
    }

    // ==================== STATISTICS ====================

    public function getBillingStats(): array
    {
        return [
            'success' => true,
            'data' => [
                'total_subscriptions' => $this->subscriptionRepo->getTotalActive(),
                'total_expired' => $this->subscriptionRepo->getTotalExpired(),
                'mrr' => $this->subscriptionRepo->getMRR(),
                'arr' => $this->subscriptionRepo->getMRR() * 12,
                'revenue_today' => $this->subscriptionRepo->getTodayRevenue(),
                'revenue_month' => $this->subscriptionRepo->getMonthlyRevenue(),
                'revenue_year' => $this->subscriptionRepo->getYearlyRevenue(),
                'total_unpaid' => $this->invoiceRepo->countByStatus('UNPAID'),
                'total_paid' => $this->invoiceRepo->countByStatus('PAID'),
            ],
        ];
    }

    // ==================== AUTO OPERATIONS ====================

    public function checkExpiredSubscriptions(): array
    {
        $expired = $this->subscriptionRepo->findExpired();
        $count = 0;
        foreach ($expired as $sub) {
            $this->subscriptionRepo->update($sub->id, ['status' => 'EXPIRED']);
            $this->licenseRepo->block($sub->licenseId, 'Subscription expired');
            $this->auditRepo->create([
                'action' => 'SUBSCRIPTION_EXPIRED',
                'admin_name' => 'SYSTEM',
                'license_id' => $sub->licenseId,
                'details' => "Subscription #{$sub->id} expired",
            ]);
            $count++;
        }
        return ['success' => true, 'message' => "$count subscription(s) expired", 'count' => $count];
    }

    public function getExpiringSoon(int $days = 30): array
    {
        $list = $this->subscriptionRepo->findExpiringSoon($days);
        return ['success' => true, 'data' => array_map(fn($s) => $s->toArray(), $list)];
    }

    // ==================== MANUAL EXTEND ====================

    public function manualExtend(int $licenseId, int $days): array
    {
        $license = $this->licenseRepo->findById($licenseId);
        if ($license === null) return ['success' => false, 'message' => 'Lisensi tidak ditemukan'];

        $currentEnd = $license->licenseExpired ? strtotime($license->licenseExpired) : time();
        if ($currentEnd < time()) $currentEnd = time();
        $newEnd = date('Y-m-d H:i:s', strtotime("+$days days", $currentEnd));

        $this->licenseRepo->extendExpiry($license->id, $newEnd);

        $sub = $this->subscriptionRepo->findByLicenseId($licenseId);
        if ($sub) {
            $subEnd = $sub->endDate ? strtotime($sub->endDate) : time();
            if ($subEnd < time()) $subEnd = time();
            $newSubEnd = date('Y-m-d H:i:s', strtotime("+$days days", $subEnd));
            $this->subscriptionRepo->update($sub->id, ['end_date' => $newSubEnd, 'status' => 'ACTIVE']);
        }

        $this->auditRepo->create([
            'action' => 'EXTEND_LICENSE',
            'admin_name' => 'SYSTEM',
            'license_id' => $licenseId,
            'details' => "Lisensi diperpanjang $days hari",
        ]);

        return ['success' => true, 'message' => "Lisensi diperpanjang $days hari"];
    }

    private function generateLicenseKey(): string
    {
        $segments = ['NKG', date('Y')];
        for ($i = 0; $i < 3; $i++) {
            $segments[] = strtoupper(bin2hex(random_bytes(2)));
        }
        return implode('-', $segments);
    }
}
