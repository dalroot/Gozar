<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BrandedQrService
{
    public function generate(string $link, string $type = 'paid'): string
    {
        try {
            $background = public_path('images/' . ($type === 'test' ? 'qr_test_bg.jpg' : 'qr_paid_bg.jpg'));
            $fallback = $this->remoteQrUrl($link);
            if (!is_file($background)) return $fallback;

            $qrContent = @file_get_contents($fallback);
            if (!$qrContent) return $fallback;

            $bg = @imagecreatefromjpeg($background);
            $qr = @imagecreatefromstring($qrContent);
            if (!$bg || !$qr) return $fallback;

            $targetSize = 460;
            $destX = (int) ((imagesx($bg) - $targetSize) / 2);
            $destY = $type === 'test' ? 465 : 405;
            imagecopyresampled($bg, $qr, $destX, $destY, 0, 0, $targetSize, $targetSize, imagesx($qr), imagesy($qr));

            $dir = public_path('qrcodes');
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $path = $dir . '/qr_' . md5($link . '_' . $type) . '.jpg';
            imagejpeg($bg, $path, 92);
            imagedestroy($bg);
            imagedestroy($qr);

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Branded QR generation failed', ['type' => $type, 'error' => $e->getMessage()]);
            return $this->remoteQrUrl($link);
        }
    }

    private function remoteQrUrl(string $link): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
            'size' => '450x450', 'data' => $link, 'ecc' => 'M', 'margin' => 10, 'format' => 'png',
        ]);
    }
}
