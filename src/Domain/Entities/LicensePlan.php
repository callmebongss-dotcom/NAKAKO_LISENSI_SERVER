<?php

namespace App\Domain\Entities;

class LicensePlan
{
    public ?int $id;
    public string $planName;
    public ?string $description;
    public int $maxTv;
    public int $offlineDays;
    public int $licenseDurationDays;
    public int $allowTransfer;
    public int $maxTransfer;
    public int $allowRemoteDisable;
    public int $allowRemoteUpdate;
    public int $prioritySupport;
    public int $isActive;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->planName = $data['plan_name'] ?? '';
        $this->description = $data['description'] ?? null;
        $this->maxTv = (int) ($data['max_tv'] ?? 0);
        $this->offlineDays = (int) ($data['offline_days'] ?? 30);
        $this->licenseDurationDays = (int) ($data['license_duration_days'] ?? 365);
        $this->allowTransfer = (int) ($data['allow_transfer'] ?? 0);
        $this->maxTransfer = (int) ($data['max_transfer'] ?? 0);
        $this->allowRemoteDisable = (int) ($data['allow_remote_disable'] ?? 0);
        $this->allowRemoteUpdate = (int) ($data['allow_remote_update'] ?? 0);
        $this->prioritySupport = (int) ($data['priority_support'] ?? 0);
        $this->isActive = (int) ($data['is_active'] ?? 1);
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'plan_name' => $this->planName,
            'description' => $this->description,
            'max_tv' => $this->maxTv,
            'offline_days' => $this->offlineDays,
            'license_duration_days' => $this->licenseDurationDays,
            'allow_transfer' => $this->allowTransfer,
            'max_transfer' => $this->maxTransfer,
            'allow_remote_disable' => $this->allowRemoteDisable,
            'allow_remote_update' => $this->allowRemoteUpdate,
            'priority_support' => $this->prioritySupport,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
