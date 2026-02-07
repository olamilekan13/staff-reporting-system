<?php

namespace App\Listeners;

use App\Actions\ConvertOfficeToPdfAction;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class ConvertOfficeDocumentToPdf
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        // Only process files in source collections, skip pdf_preview to prevent infinite loop
        if (!in_array($media->collection_name, ['report_file', 'proposal_attachment'])) {
            return;
        }

        if (!ConvertOfficeToPdfAction::shouldConvert($media)) {
            return;
        }

        $pdfPath = ConvertOfficeToPdfAction::convert($media);

        if (!$pdfPath) {
            return;
        }

        try {
            $model = $media->model;
            $model->addMedia($pdfPath)
                ->toMediaCollection('pdf_preview');
        } catch (\Throwable $e) {
            Log::error('Failed to store PDF preview', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
