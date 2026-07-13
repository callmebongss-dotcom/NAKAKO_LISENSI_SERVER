<?php

namespace App\Application\Services;

require_once __DIR__ . '/../../Libs/fpdf.php';
require_once __DIR__ . '/../../Libs/qrlib.php';

class CertificateGeneratorService
{
    private const CERT_PREFIX = 'CERT-2026-';

    public function generateCertificateNumber(): string
    {
        $repo = new \App\Infrastructure\Repository\LicenseCertificateRepository();
        $count = $repo->getTotalCount();
        return self::CERT_PREFIX . str_pad((string)($count + 1), 6, '0', STR_PAD_LEFT);
    }

    public function generateSignature(array $data): string
    {
        $payload = implode('|', [
            $data['certificate_number'] ?? '',
            $data['license_key'] ?? '',
            $data['product_key'] ?? '',
            $data['business_name'] ?? '',
            $data['owner_name'] ?? '',
            $data['license_type'] ?? '',
            $data['activation_date'] ?? '',
        ]);
        return hash_hmac('sha256', $payload, defined('JWT_SECRET') ? JWT_SECRET : 'nakako-secret-key-2026');
    }

    private function generateQrRaw(string $text): array
    {
        $data = \QRcode::raw($text);
        if (empty($data)) {
            $raw = \QRcode::text($text);
            if (is_array($raw)) return $raw;
            return [];
        }
        return \QRtools::binarize($data);
    }

    private function drawQrCode(\FPDF $pdf, string $text, float $x, float $y, float $size): void
    {
        $frame = $this->generateQrRaw($text);
        $count = count($frame);
        if ($count === 0) return;

        $cellSize = $size / $count;

        for ($row = 0; $row < $count; $row++) {
            for ($col = 0; $col < $count; $col++) {
                $char = $frame[$row][$col] ?? '0';
                if ($char === '1') {
                    $pdf->SetFillColor(0, 0, 0);
                    $pdf->Rect($x + ($col * $cellSize), $y + ($row * $cellSize), $cellSize, $cellSize, 'F');
                }
            }
        }
    }

    public function generatePdf(array $data): string
    {
        $verifyUrl = $this->getServerUrl() . '/verify.html?cert=' . urlencode($data['certificate_number'] ?? '');

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();

        // Border
        $pdf->SetDrawColor(41, 98, 255);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect(10, 10, 190, 277);

        // Inner border
        $pdf->SetDrawColor(200, 200, 220);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect(13, 13, 184, 271);

        // ===== HEADER =====
        $pdf->SetY(20);
        $pdf->SetX(15);

        // Logo placeholder - NAKAKO GAMES brand
        $pdf->SetFillColor(41, 98, 255);
        $pdf->Rect(15, 20, 50, 18, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 13);
        $pdf->SetXY(15, 22);
        $pdf->Cell(50, 14, 'NAKAKO GAMES', 0, 0, 'C');

        // Certificate title
        $pdf->SetTextColor(41, 98, 255);
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->SetXY(70, 20);
        $pdf->Cell(125, 10, 'LICENSE CERTIFICATE', 0, 1, 'R');

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(100, 100, 120);
        $pdf->SetXY(70, 31);
        $pdf->Cell(125, 6, 'Sertifikat Lisensi Resmi', 0, 1, 'R');

        // Divider
        $pdf->SetDrawColor(41, 98, 255);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(15, 42, 195, 42);

        // ===== CERTIFICATE NUMBER =====
        $pdf->SetY(48);
        $pdf->SetX(15);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(100, 100, 120);
        $pdf->Cell(40, 6, 'No. Sertifikat', 0, 0);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetTextColor(41, 98, 255);
        $pdf->Cell(0, 6, $data['certificate_number'] ?? '-', 0, 1);

        // ===== LICENSE INFORMATION =====
        $pdf->SetY(60);
        $pdf->SetX(15);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(30, 30, 50);
        $pdf->Cell(0, 7, 'INFORMASI LISENSI', 0, 1);

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(50, 50, 70);

        $leftX = 15;
        $rightX = 105;
        $labelW = 35;
        $valueW = 55;
        $rowH = 7;

        $y = 68;
        $fields = [
            ['Nama Rental', $data['business_name'] ?? '-'],
            ['Pemilik', $data['owner_name'] ?? '-'],
            ['No. HP', $data['phone_number'] ?? '-'],
            ['Device', $data['device_id'] ?? '-'],
            ['License Key', $data['license_key'] ?? '-'],
            ['Product Key', $data['product_key'] ?? '-'],
            ['Jenis Lisensi', $data['license_type'] ?? '-'],
            ['Masa Berlaku', 'Seumur Hidup (Lifetime)'],
            ['Tgl Aktivasi', $data['activation_date'] ?? '-'],
        ];

        foreach ($fields as $i => $field) {
            $col = $i % 2;
            $row = intdiv($i, 2);
            $x = $col === 0 ? $leftX : $rightX;
            $yPos = $y + ($row * $rowH);

            $pdf->SetXY($x, $yPos);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor(100, 100, 120);
            $pdf->Cell($labelW, $rowH, $field[0], 0, 0);

            $pdf->SetFont($field[0] === 'License Key' || $field[0] === 'Product Key' ? 'Courier' : 'Helvetica', '', 9);
            $pdf->SetTextColor(30, 30, 50);
            $pdf->Cell($valueW, $rowH, $this->truncate($field[1], 22), 0, 1);
        }

        // ===== DIGITAL SIGNATURE =====
        $sigY = 120;
        $pdf->SetY($sigY);
        $pdf->SetX(15);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(30, 30, 50);
        $pdf->Cell(0, 7, 'TANDA TANGAN DIGITAL', 0, 1);

        $pdf->SetX(15);
        $pdf->SetFont('Courier', '', 8);
        $pdf->SetTextColor(80, 80, 100);
        $sigHash = $data['signature_hash'] ?? '-';
        $sigWrapped = wordwrap($sigHash, 64, "\n", true);
        $pdf->MultiCell(180, 4, $sigWrapped, 0, 'L');

        $pdf->SetX(15);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(140, 140, 160);
        $pdf->Cell(0, 5, 'Tanda tangan digital ini memverifikasi keaslian sertifikat.', 0, 1);

        // ===== QR CODE =====
        $qrY = 155;
        $qrSize = 50;

        // QR Border
        $pdf->SetDrawColor(200, 200, 220);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect(145, $qrY - 3, $qrSize + 6, $qrSize + 6);

        // QR label
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(100, 100, 120);
        $pdf->SetXY(145, $qrY + $qrSize + 5);
        $pdf->Cell($qrSize + 6, 4, 'Scan untuk verifikasi', 0, 1, 'C');

        $this->drawQrCode($pdf, $verifyUrl, 148, $qrY, $qrSize);

        // ===== VERIFICATION INFO =====
        $pdf->SetY($qrY + $qrSize + 15);
        $pdf->SetX(15);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 120);
        $pdf->Cell(0, 4, 'Verifikasi online: ' . $verifyUrl, 0, 1);

        // ===== FOOTER =====
        $pdf->SetY(268);
        $pdf->SetX(15);
        $pdf->SetDrawColor(41, 98, 255);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(15, 266, 195, 266);

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(140, 140, 160);
        $pdf->Cell(0, 4, 'NAKAKO GAMES - License Server v1.0 | Dicetak: ' . date('Y-m-d H:i:s'), 0, 1, 'C');

        return $pdf->Output('S');
    }

    private function getServerUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        return $protocol . '://' . $host;
    }

    private function truncate(string $text, int $maxLen): string
    {
        if (strlen($text) <= $maxLen) return $text;
        return substr($text, 0, $maxLen - 3) . '...';
    }
}
