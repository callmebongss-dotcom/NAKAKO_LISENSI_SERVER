<?php

namespace App\Domain;

interface PaymentProvider
{
    public function getName(): string;
    public function createPayment(float $amount, string $invoiceNumber, array $customerData): array;
    public function verifyPayment(string $providerReference): array;
    public function refundPayment(string $providerReference, float $amount): array;
}
