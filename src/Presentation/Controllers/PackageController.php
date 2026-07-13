<?php

namespace App\Presentation\Controllers;

use App\Application\Services\PackageService;

class PackageController
{
    private PackageService $service;

    public function __construct()
    {
        $this->service = new PackageService();
    }

    public function list(): void
    {
        $this->jsonResponse($this->service->getAll());
    }

    public function get(int $id): void
    {
        $this->jsonResponse($this->service->getById($id));
    }

    public function create(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $this->jsonResponse($this->service->create($data));
    }

    public function update(int $id): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $this->jsonResponse($this->service->update($id, $data));
    }

    public function delete(int $id): void
    {
        $this->jsonResponse($this->service->delete($id));
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        echo json_encode($data);
    }
}
