<?php

namespace App\Domain\Entities;

class LicenseCommand
{
    public ?int $id;
    public int $licenseId;
    public string $command;
    public ?string $payload;
    public string $status;
    public ?string $createdAt;
    public ?string $executedAt;
    public ?string $result;
    public string $createdBy;

    const COMMAND_ACTIVATE = 'ACTIVATE';
    const COMMAND_BLOCK = 'BLOCK';
    const COMMAND_SUSPEND = 'SUSPEND';
    const COMMAND_UNSUSPEND = 'UNSUSPEND';
    const COMMAND_CHANGE_PLAN = 'CHANGE_PLAN';
    const COMMAND_FORCE_SYNC = 'FORCE_SYNC';
    const COMMAND_SHOW_MESSAGE = 'SHOW_MESSAGE';
    const COMMAND_RESTART_APP = 'RESTART_APP';
    const COMMAND_LOGOUT_LICENSE = 'LOGOUT_LICENSE';
    const COMMAND_EXTEND_LICENSE = 'EXTEND_LICENSE';

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->licenseId = (int) ($data['license_id'] ?? 0);
        $this->command = $data['command'] ?? '';
        $this->payload = $data['payload'] ?? null;
        $this->status = $data['status'] ?? 'PENDING';
        $this->createdAt = $data['created_at'] ?? null;
        $this->executedAt = $data['executed_at'] ?? null;
        $this->result = $data['result'] ?? null;
        $this->createdBy = $data['created_by'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'license_id' => $this->licenseId,
            'command' => $this->command,
            'payload' => $this->payload,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'executed_at' => $this->executedAt,
            'result' => $this->result,
            'created_by' => $this->createdBy,
        ];
    }
}
