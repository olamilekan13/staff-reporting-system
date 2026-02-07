<?php

namespace App\Actions;

use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\Process\Process;

class ConvertOfficeToPdfAction
{
    protected static array $officeExtensions = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    public static function shouldConvert(Media $media): bool
    {
        $extension = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));

        return in_array($extension, self::$officeExtensions);
    }

    public static function getLibreOfficePath(): ?string
    {
        $envPath = env('LIBREOFFICE_PATH');
        if ($envPath && file_exists($envPath)) {
            return $envPath;
        }

        $command = PHP_OS_FAMILY === 'Windows'
            ? ['where', 'soffice']
            : ['which', 'soffice'];

        $process = new Process($command);
        $process->run();

        if ($process->isSuccessful()) {
            return trim(explode("\n", $process->getOutput())[0]);
        }

        return null;
    }

    public static function convert(Media $media): ?string
    {
        $sofficePath = self::getLibreOfficePath();

        if (!$sofficePath) {
            Log::warning('LibreOffice not available for PDF conversion', [
                'media_id' => $media->id,
                'file_name' => $media->file_name,
            ]);

            return null;
        }

        $sourcePath = $media->getPath();
        $outputDir = dirname($sourcePath);

        $process = new Process([
            $sofficePath,
            '--headless',
            '--convert-to', 'pdf',
            '--outdir', $outputDir,
            $sourcePath,
        ]);

        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error('LibreOffice PDF conversion failed', [
                'media_id' => $media->id,
                'file_name' => $media->file_name,
                'error' => $process->getErrorOutput(),
            ]);

            return null;
        }

        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . pathinfo($media->file_name, PATHINFO_FILENAME) . '.pdf';

        if (!file_exists($pdfPath)) {
            Log::error('LibreOffice conversion produced no output file', [
                'media_id' => $media->id,
                'expected_path' => $pdfPath,
            ]);

            return null;
        }

        return $pdfPath;
    }
}
