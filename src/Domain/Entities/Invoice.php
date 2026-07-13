<?php

namespace App\Domain\Entities;

class Invoice
{
    public ?int $id;
    public string $invoiceNumber;
    public ?int $subscriptionId;
    public int $licenseId;
    public int $planId;
    public float $amount;
    public float $tax;
    public float $total;
    public string $status;
    public ?string $dueDate;
    public ?string $paidAt;
    public ?string $notes;
    public ?string $createdAt;

    public ?string $businessName;
    public ?string $ownerName;
    public ?string $planName;

    const STATUS_UNPAID = 'UNPAID';
    const STATUS_PAID = 'PAID';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_REFUND = 'REFUND';

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->invoiceNumber = $data['invoice_number'] ?? '';
        $this->subscriptionId = isset($data['subscription_id']) ? (int) $data['subscription_id'] : null;
        $this->licenseId = (int) ($data['license_id'] ?? 0);
        $this->planId = (int) ($data['plan_id'] ?? 0);
        $this->amount = (float) ($data['amount'] ?? 0);
        $this->tax = (float) ($data['tax'] ?? 0);
        $this->total = (float) ($data['total'] ?? 0);
        $this->status = $data['status'] ?? 'UNPAID';
        $this->dueDate = $data['due_date'] ?? null;
        $this->paidAt = $data['paid_at'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->businessName = $data['business_name'] ?? null;
        $this->ownerName = $data['owner_name'] ?? null;
        $this->planName = $data['plan_name'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoiceNumber,
            'subscription_id' => $this->subscriptionId,
            'license_id' => $this->licenseId,
            'plan_id' => $this->planId,
            'amount' => $this->amount,
            'tax' => $this->tax,
            'total' => $this->total,
            'status' => $this->status,
            'due_date' => $this->dueDate,
            'paid_at' => $this->paidAt,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'business_name' => $this->businessName,
            'owner_name' => $this->ownerName,
            'plan_name' => $this->planName,
        ];
    }
}
