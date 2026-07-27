<?php

namespace App\Domain\Entities;

class LicensePackage
{
    public ?int $id;
    public string $name;
    public ?string $description;
    public float $price;
    public int $durationDays;
    public int $maxDevices;
    public ?string $features;
    public string $status;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? null;
        $this->price = (float) ($data['price'] ?? 0);
        $this->durationDays = (int) ($data['duration_days'] ?? 30);
        $this->maxDevices = (int) ($data['max_devices'] ?? 1);
        $this->features = $data['features'] ?? null;
        $this->status = $data['status'] ?? 'ACTIVE';
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration_days' => $this->durationDays,
            'max_devices' => $this->maxDevices,
            'features' => $this->features,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
