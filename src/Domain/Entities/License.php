<?php

namespace App\Domain\Entities;

class License
{
    public ?int $id;
    public ?string $licenseKey;
    public ?string $deviceId;
    public string $businessName;
    public string $ownerName;
    public string $phoneNumber;
    public ?string $email;
    public string $city;
    public string $licenseStatus;
    public string $licenseType;
    public ?string $deviceFingerprint;
    public ?string $deviceName;
    public ?string $platform;
    public ?string $appVersion;
    public ?string $lastOnline;
    public int $cloneDetected;
    public ?string $createdAt;
    public ?string $approvedAt;
    public ?string $updatedAt;
    public ?string $approvedBy;
    public ?string $remarks;
    public ?int $planId;
    public ?string $licenseExpired;
    public ?string $offlineExpired;
    public ?string $lastSync;
    public float $purchasePrice;
    public ?string $purchaseDate;
    public ?string $activationDate;
    public ?string $blockedDate;
    public ?string $blockedReason;
    public int $transferCount;
    public int $maxTransfer;
    public string $majorVersion;
    public string $minorVersion;
    public ?string $notes;
    public ?string $productKey;
    public ?int $productKeyId;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->licenseKey = $data['license_key'] ?? null;
        $this->deviceId = $data['device_id'] ?? null;
        $this->businessName = $data['business_name'] ?? '';
        $this->ownerName = $data['owner_name'] ?? '';
        $this->phoneNumber = $data['phone_number'] ?? '';
        $this->email = $data['email'] ?? null;
        $this->city = $data['city'] ?? '';
        $this->licenseStatus = $data['license_status'] ?? 'PENDING';
        $this->licenseType = $data['license_type'] ?? 'TRIAL';
        $this->deviceFingerprint = $data['device_fingerprint'] ?? null;
        $this->deviceName = $data['device_name'] ?? null;
        $this->platform = $data['platform'] ?? null;
        $this->appVersion = $data['app_version'] ?? null;
        $this->lastOnline = $data['last_online'] ?? null;
        $this->cloneDetected = (int) ($data['clone_detected'] ?? 0);
        $this->planId = isset($data['plan_id']) && $data['plan_id'] !== null ? (int) $data['plan_id'] : null;
        $this->licenseExpired = $data['license_expired'] ?? null;
        $this->offlineExpired = $data['offline_expired'] ?? null;
        $this->lastSync = $data['last_sync'] ?? null;
        $this->purchasePrice = (float) ($data['purchase_price'] ?? 0);
        $this->purchaseDate = $data['purchase_date'] ?? null;
        $this->activationDate = $data['activation_date'] ?? null;
        $this->blockedDate = $data['blocked_date'] ?? null;
        $this->blockedReason = $data['blocked_reason'] ?? null;
        $this->transferCount = (int) ($data['transfer_count'] ?? 0);
        $this->maxTransfer = (int) ($data['max_transfer'] ?? 3);
        $this->majorVersion = $data['major_version'] ?? '1';
        $this->minorVersion = $data['minor_version'] ?? '0';
        $this->notes = $data['notes'] ?? null;
        $this->productKey = $data['product_key'] ?? null;
        $this->productKeyId = isset($data['product_key_id']) && $data['product_key_id'] !== null ? (int) $data['product_key_id'] : null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->approvedAt = $data['approved_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        $this->approvedBy = $data['approved_by'] ?? null;
        $this->remarks = $data['remarks'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'license_key' => $this->licenseKey,
            'device_id' => $this->deviceId,
            'business_name' => $this->businessName,
            'owner_name' => $this->ownerName,
            'phone_number' => $this->phoneNumber,
            'email' => $this->email,
            'city' => $this->city,
            'license_status' => $this->licenseStatus,
            'license_type' => $this->licenseType,
            'device_fingerprint' => $this->deviceFingerprint,
            'device_name' => $this->deviceName,
            'platform' => $this->platform,
            'app_version' => $this->appVersion,
            'last_online' => $this->lastOnline,
            'clone_detected' => $this->cloneDetected,
            'created_at' => $this->createdAt,
            'approved_at' => $this->approvedAt,
            'updated_at' => $this->updatedAt,
            'approved_by' => $this->approvedBy,
            'remarks' => $this->remarks,
            'plan_id' => $this->planId,
            'license_expired' => $this->licenseExpired,
            'offline_expired' => $this->offlineExpired,
            'last_sync' => $this->lastSync,
            'purchase_price' => $this->purchasePrice,
            'purchase_date' => $this->purchaseDate,
            'activation_date' => $this->activationDate,
            'blocked_date' => $this->blockedDate,
            'blocked_reason' => $this->blockedReason,
            'transfer_count' => $this->transferCount,
            'max_transfer' => $this->maxTransfer,
            'major_version' => $this->majorVersion,
            'minor_version' => $this->minorVersion,
            'notes' => $this->notes,
            'product_key' => $this->productKey,
            'product_key_id' => $this->productKeyId,
        ];
    }
}
