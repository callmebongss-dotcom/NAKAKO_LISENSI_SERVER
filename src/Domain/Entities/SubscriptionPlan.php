<?php

namespace App\Domain\Entities;

class SubscriptionPlan
{
    public ?int $id;
    public string $name;
    public float $price;
    public int $durationDays;
    public int $offlineDays;
    public int $maxTv;
    public int $maxUser;
    public ?string $featureFlags;
    public ?string $description;
    public string $status;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->price = (float) ($data['price'] ?? 0);
        $this->durationDays = (int) ($data['duration_days'] ?? 30);
        $this->offlineDays = (int) ($data['offline_days'] ?? 7);
        $this->maxTv = (int) ($data['max_tv'] ?? 2);
        $this->maxUser = (int) ($data['max_user'] ?? 1);
        $this->featureFlags = $data['feature_flags'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->status = $data['status'] ?? 'ACTIVE';
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'duration_days' => $this->durationDays,
            'offline_days' => $this->offlineDays,
            'max_tv' => $this->maxTv,
            'max_user' => $this->maxUser,
            'feature_flags' => $this->featureFlags,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
