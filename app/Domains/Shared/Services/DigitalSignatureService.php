<?php

namespace App\Domains\Shared\Services;

use App\Domains\HR\Models\Employee;
use Illuminate\Support\Facades\Storage;

class DigitalSignatureService
{
    /**
     * Membubuhkan Tanda Tangan Digital Internal dengan HMAC-SHA256 Stamp
     */
    public function generateSignatureStamp(Employee $employee, string $documentReference): array
    {
        $timestamp = now()->toIso8601String();
        $payload = "{$employee->nik}|{$documentReference}|{$timestamp}";
        $signatureHash = hash_hmac('sha256', $payload, (string) (config('app.key') ?: 'secret_key_erp_intel_creative'));

        // Generate QR Code Verifikasi Dokumen Internal
        $verificationUrl = config('app.url')."/verify-document?hash={$signatureHash}";
        $qrSvg = SvgQrCodeGenerator::generate($verificationUrl, 120);
        $qrImageBase64 = base64_encode($qrSvg);

        return [
            'signer_name' => $employee->full_name,
            'signer_title' => $employee->designation->title ?? 'Authorized Signer',
            'signed_at' => $timestamp,
            'document_hash' => $signatureHash,
            'qr_code_base64' => "data:image/svg+xml;base64,{$qrImageBase64}",
            'signature_specimen_url' => $employee->signature_specimen_path ? Storage::url($employee->signature_specimen_path) : null,
        ];
    }
}
