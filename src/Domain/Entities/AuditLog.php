<?php

namespace App\Domain\Entities;

class AuditLog
{
    public ?int $id;
    public string $action;
    public string $adminName;
    public ?int $licenseId;
    public ?string $details;
    public ?string $ipAddress;
    public ?string $userAgent;
    public ?string $createdAt;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->action = $data['action'] ?? '';
        $this->adminName = $data['admin_name'] ?? '';
        $this->licenseId = isset($data['license_id']) ? (int) $data['license_id'] : null;
        $this->details = $data['details'] ?? null;
        $this->ipAddress = $data['ip_address'] ?? null;
        $this->userAgent = $data['user_agent'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'admin_name' => $this->adminName,
            'license_id' => $this->licenseId,
            'details' => $this->details,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt,
        ];
    }
}
