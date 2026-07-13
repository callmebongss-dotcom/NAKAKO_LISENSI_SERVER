<?php

namespace App\Domain\Entities;

class HistoryLog
{
    public ?int $id;
    public int $licenseId;
    public string $action;
    public ?string $description;
    public ?string $oldValue;
    public ?string $newValue;
    public ?string $adminName;
    public ?string $ipAddress;
    public ?string $createdAt;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->licenseId = (int) ($data['license_id'] ?? 0);
        $this->action = $data['action'] ?? '';
        $this->description = $data['description'] ?? null;
        $this->oldValue = $data['old_value'] ?? null;
        $this->newValue = $data['new_value'] ?? null;
        $this->adminName = $data['admin_name'] ?? null;
        $this->ipAddress = $data['ip_address'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'license_id' => $this->licenseId,
            'action' => $this->action,
            'description' => $this->description,
            'old_value' => $this->oldValue,
            'new_value' => $this->newValue,
            'admin_name' => $this->adminName,
            'ip_address' => $this->ipAddress,
            'created_at' => $this->createdAt,
        ];
    }
}
