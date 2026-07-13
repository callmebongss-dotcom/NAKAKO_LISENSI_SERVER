<?php

namespace App\Domain\Entities;

class AppUpdate
{
    public ?int $id;
    public string $version;
    public ?string $releaseNotes;
    public string $fileUrl;
    public int $fileSize;
    public string $platform;
    public int $isForced;
    public ?string $createdAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->version = $data['version'];
        $this->releaseNotes = $data['release_notes'] ?? null;
        $this->fileUrl = $data['file_url'];
        $this->fileSize = (int)($data['file_size'] ?? 0);
        $this->platform = $data['platform'] ?? 'all';
        $this->isForced = (int)($data['is_forced'] ?? 0);
        $this->createdAt = $data['created_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'release_notes' => $this->releaseNotes,
            'file_url' => $this->fileUrl,
            'file_size' => $this->fileSize,
            'platform' => $this->platform,
            'is_forced' => $this->isForced,
            'created_at' => $this->createdAt,
        ];
    }
}
