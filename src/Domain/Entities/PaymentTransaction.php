<?php

namespace App\Domain\Entities;

class PaymentTransaction
{
    public ?int $id;
    public int $invoiceId;
    public ?int $subscriptionId;
    public int $licenseId;
    public float $amount;
    public ?string $provider;
    public ?string $providerReference;
    public string $status;
    public ?string $paymentMethod;
    public ?string $paidAt;
    public ?string $rawResponse;
    public ?string $createdAt;

    const STATUS_PENDING = 'PENDING';
    const STATUS_WAITING = 'WAITING';
    const STATUS_PAID = 'PAID';
    const STATUS_FAILED = 'FAILED';
    const STATUS_REFUND = 'REFUND';

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->invoiceId = (int) ($data['invoice_id'] ?? 0);
        $this->subscriptionId = isset($data['subscription_id']) ? (int) $data['subscription_id'] : null;
        $this->licenseId = (int) ($data['license_id'] ?? 0);
        $this->amount = (float) ($data['amount'] ?? 0);
        $this->provider = $data['provider'] ?? null;
        $this->providerReference = $data['provider_reference'] ?? null;
        $this->status = $data['status'] ?? 'PENDING';
        $this->paymentMethod = $data['payment_method'] ?? null;
        $this->paidAt = $data['paid_at'] ?? null;
        $this->rawResponse = $data['raw_response'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoiceId,
            'subscription_id' => $this->subscriptionId,
            'license_id' => $this->licenseId,
            'amount' => $this->amount,
            'provider' => $this->provider,
            'provider_reference' => $this->providerReference,
            'status' => $this->status,
            'payment_method' => $this->paymentMethod,
            'paid_at' => $this->paidAt,
            'raw_response' => $this->rawResponse,
            'created_at' => $this->createdAt,
        ];
    }
}
