<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImageUrlService
{
    public function __construct(
        private readonly PdfImageService $pdfImageService
    ) {
    }

    /**
     * Cek apakah request berasal dari Tailscale network (koneksi lambat).
     * Tailscale menggunakan CGNAT range: 100.64.0.0/10 (100.64.0.0 - 100.127.255.255)
     */
    public function isSlowConnection(): bool
    {
        $ip = request()->ip();

        // Tailscale IP range dimulai dengan 100.
        return str_starts_with($ip, '100.');
    }

    /**
     * Dapatkan URL gambar.
     * Jika akses dari Tailscale, gunakan versi compressed.
     * Jika akses dari LAN, gunakan versi original.
     *
     * @param string $storagePath Path relatif di storage/app/public (e.g. "ptk/123/image.jpg")
     * @return string URL yang bisa diakses browser
     */
    public function getImageUrl(string $storagePath): string
    {
        if ($this->isSlowConnection()) {
            return $this->getCompressedUrl($storagePath);
        }

        // Return original
        return asset(Storage::url($storagePath));
    }

    /**
     * Dapatkan URL versi compressed dari gambar.
     */
    public function getCompressedUrl(string $storagePath): string
    {
        // Convert relative storage path ke absolute path
        $fullPath = storage_path('app/public/' . $storagePath);

        // Pastikan file ada
        if (!file_exists($fullPath)) {
            return asset(Storage::url($storagePath)); // Fallback ke original
        }

        // Dapatkan path compressed (akan di-generate jika belum ada)
        $compressedPath = $this->pdfImageService->getCompressedPath($fullPath);

        // Convert absolute path ke URL
        // storage_path('app/public/') => /storage/
        $relativePath = str_replace(
            storage_path('app/public') . DIRECTORY_SEPARATOR,
            '',
            $compressedPath
        );

        // Normalize path separator untuk URL
        $relativePath = str_replace('\\', '/', $relativePath);

        return asset('storage/' . $relativePath);
    }
}
