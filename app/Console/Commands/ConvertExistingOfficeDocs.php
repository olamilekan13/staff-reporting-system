<?php

namespace App\Console\Commands;

use App\Actions\ConvertOfficeToPdfAction;
use App\Models\Proposal;
use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConvertExistingOfficeDocs extends Command
{
    protected $signature = 'app:convert-existing-office-docs';

    protected $description = 'Convert existing Office documents (doc, docx, xls, xlsx, ppt, pptx) to PDF previews';

    public function handle(): int
    {
        $sofficePath = ConvertOfficeToPdfAction::getLibreOfficePath();

        if (!$sofficePath) {
            $this->error('LibreOffice is not installed or not found in PATH.');
            $this->line('Set LIBREOFFICE_PATH in your .env file or install LibreOffice.');

            return self::FAILURE;
        }

        $this->info("Using LibreOffice at: {$sofficePath}");
        $this->newLine();

        $converted = 0;
        $failed = 0;
        $skipped = 0;

        // Process Reports
        $this->info('Processing Reports...');
        $reports = Report::whereIn('file_type', ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])->get();

        $bar = $this->output->createProgressBar($reports->count());
        $bar->start();

        foreach ($reports as $report) {
            $result = $this->convertModel($report, 'report_file');
            match ($result) {
                'converted' => $converted++,
                'failed' => $failed++,
                'skipped' => $skipped++,
            };
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Process Proposals
        $this->info('Processing Proposals...');
        $proposals = Proposal::whereIn('file_type', ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])->get();

        $bar = $this->output->createProgressBar($proposals->count());
        $bar->start();

        foreach ($proposals as $proposal) {
            $result = $this->convertModel($proposal, 'proposal_attachment');
            match ($result) {
                'converted' => $converted++,
                'failed' => $failed++,
                'skipped' => $skipped++,
            };
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Done! Converted: {$converted}, Failed: {$failed}, Skipped (already have preview): {$skipped}");

        return self::SUCCESS;
    }

    private function convertModel($model, string $collection): string
    {
        // Skip if already has a PDF preview
        if ($model->getFirstMedia('pdf_preview')) {
            return 'skipped';
        }

        $media = $model->getFirstMedia($collection);

        if (!$media) {
            return 'skipped';
        }

        $pdfPath = ConvertOfficeToPdfAction::convert($media);

        if (!$pdfPath) {
            Log::warning('Retroactive conversion failed', [
                'model' => get_class($model),
                'id' => $model->id,
                'file_name' => $media->file_name,
            ]);

            return 'failed';
        }

        try {
            $model->addMedia($pdfPath)
                ->toMediaCollection('pdf_preview');

            return 'converted';
        } catch (\Throwable $e) {
            Log::error('Failed to store retroactive PDF preview', [
                'model' => get_class($model),
                'id' => $model->id,
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }
}
