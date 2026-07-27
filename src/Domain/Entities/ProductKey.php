<?php

namespace App\Domain\Entities;

class ProductKey
{
    public ?int $id;
    public string $productKey;
    public string $status;
    public ?string $licenseType;
    public ?string $generatedAt;
    public ?string $activatedAt;
    public ?int $licenseId;
    public ?string $generatedBy;
    public ?string $createdAt;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->productKey = $data['product_key'] ?? '';
        $this->status = $data['status'] ?? 'UNUSED';
        $this->licenseType = $data['license_type'] ?? null;
        $this->generatedAt = $data['generated_at'] ?? null;
        $this->activatedAt = $data['activated_at'] ?? null;
        $this->licenseId = $data['license_id'] ?? null;
        $this->generatedBy = $data['generated_by'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_key' => $this->productKey,
            'status' => $this->status,
            'license_type' => $this->licenseType,
            'generated_at' => $this->generatedAt,
            'activated_at' => $this->activatedAt,
            'license_id' => $this->licenseId,
            'generated_by' => $this->generatedBy,
            'created_at' => $this->createdAt,
        ];
    }
}
