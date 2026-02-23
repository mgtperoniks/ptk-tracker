<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;

class AttachmentService
{
    /**
     * Proses upload lampiran untuk PTK tertentu.
     * File disimpan di: storage/app/public/ptk/{ptkId}/...
     */
    public function handle(UploadedFile $file, int $ptkId): Attachment
    {
        $path = $file->store("ptk/{$ptkId}", 'public');
        $mime = $file->getMimeType();
        $isImage = str_starts_with(strtolower($mime ?? ''), 'image/');

        $isForPdf = false;
        $sortOrder = 0;

        // Auto-select first 6 images for PDF
        if ($isImage) {
            $currentPdfCount = Attachment::where('ptk_id', $ptkId)
                ->where('is_for_pdf', true)
                ->count();

            if ($currentPdfCount < 6) {
                $isForPdf = true;
                $sortOrder = $currentPdfCount + 1;
            }
        }

        return Attachment::create([
            'ptk_id' => $ptkId,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $mime,
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
            'is_for_pdf' => $isForPdf,
            'sort_order' => $sortOrder,
        ]);
    }
}
