<?php

namespace App\Domain\Entities;

class LicenseCertificate
{
    public ?int $id;
    public string $certificateNumber;
    public int $licenseId;
    public ?string $licenseKey;
    public ?string $productKey;
    public ?string $businessName;
    public ?string $ownerName;
    public ?string $phoneNumber;
    public ?string $deviceId;
    public ?string $licenseType;
    public ?string $activationDate;
    public ?string $signatureHash;
    public ?string $qrData;
    public ?string $generatedAt;
    public ?string $generatedBy;
    public ?string $createdAt;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->certificateNumber = $data['certificate_number'] ?? '';
        $this->licenseId = (int) ($data['license_id'] ?? 0);
        $this->licenseKey = $data['license_key'] ?? null;
        $this->productKey = $data['product_key'] ?? null;
        $this->businessName = $data['business_name'] ?? null;
        $this->ownerName = $data['owner_name'] ?? null;
        $this->phoneNumber = $data['phone_number'] ?? null;
        $this->deviceId = $data['device_id'] ?? null;
        $this->licenseType = $data['license_type'] ?? null;
        $this->activationDate = $data['activation_date'] ?? null;
        $this->signatureHash = $data['signature_hash'] ?? null;
        $this->qrData = $data['qr_data'] ?? null;
        $this->generatedAt = $data['generated_at'] ?? null;
        $this->generatedBy = $data['generated_by'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'certificate_number' => $this->certificateNumber,
            'license_id' => $this->licenseId,
            'license_key' => $this->licenseKey,
            'product_key' => $this->productKey,
            'business_name' => $this->businessName,
            'owner_name' => $this->ownerName,
            'phone_number' => $this->phoneNumber,
            'device_id' => $this->deviceId,
            'license_type' => $this->licenseType,
            'activation_date' => $this->activationDate,
            'signature_hash' => $this->signatureHash,
            'qr_data' => $this->qrData,
            'generated_at' => $this->generatedAt,
            'generated_by' => $this->generatedBy,
            'created_at' => $this->createdAt,
        ];
    }
}
