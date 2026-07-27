<?php

namespace App\Domain\Entities;

class Subscription
{
    public ?int $id;
    public int $licenseId;
    public int $planId;
    public string $status;
    public ?string $startDate;
    public ?string $endDate;
    public ?string $graceEndDate;
    public int $autoRenew;
    public ?string $createdAt;
    public ?string $updatedAt;

    public ?string $businessName;
    public ?string $ownerName;
    public ?string $planName;
    public ?float $planPrice;

    const STATUS_PENDING = 'PENDING';
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_GRACE = 'GRACE';

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->licenseId = (int) ($data['license_id'] ?? 0);
        $this->planId = (int) ($data['plan_id'] ?? 0);
        $this->status = $data['status'] ?? 'PENDING';
        $this->startDate = $data['start_date'] ?? null;
        $this->endDate = $data['end_date'] ?? null;
        $this->graceEndDate = $data['grace_end_date'] ?? null;
        $this->autoRenew = (int) ($data['auto_renew'] ?? 0);
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        $this->businessName = $data['business_name'] ?? null;
        $this->ownerName = $data['owner_name'] ?? null;
        $this->planName = $data['plan_name'] ?? null;
        $this->planPrice = isset($data['plan_price']) ? (float) $data['plan_price'] : null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'license_id' => $this->licenseId,
            'plan_id' => $this->planId,
            'status' => $this->status,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'grace_end_date' => $this->graceEndDate,
            'auto_renew' => $this->autoRenew,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'business_name' => $this->businessName,
            'owner_name' => $this->ownerName,
            'plan_name' => $this->planName,
            'plan_price' => $this->planPrice,
        ];
    }
}
