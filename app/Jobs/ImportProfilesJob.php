<?php

namespace App\Jobs;

use App\Models\BulkImportLog;
use App\Services\ImportUsersService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class ImportProfilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 3600;

    protected $logId;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($logId, $filePath)
    {
        $this->logId = $logId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(ImportUsersService $importService): void
    {
        $log = BulkImportLog::find($this->logId);
        if (!$log) return;

        $log->update(['status' => 'processing']);

        $zip = new ZipArchive();
        $absolutePath = Storage::disk('local')->path($this->filePath);
        $extractPath = storage_path('app/temp/import_' . $this->logId);

        if ($zip->open($absolutePath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            throw new Exception("Failed to open ZIP file.");
        }

        $jsonPath = $extractPath . '/users.json';
        if (!file_exists($jsonPath)) {
            // Find files recursively if not in root of extracted directory
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                    if ($ext === 'json') {
                        $jsonPath = $file->getRealPath();
                        $extractPath = dirname($jsonPath); // Adjust extractPath root for image linking
                        break;
                    }
                }
            }
        }

        if (!file_exists($jsonPath)) {
            // Check for any CSV files as fallback
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                    if ($ext === 'csv') {
                        // Dynamically convert CSV to matching JSON structure
                        $csvData = array_map('str_getcsv', file($file->getRealPath()));
                        if (!empty($csvData)) {
                            $headers = array_map('trim', array_map('strtolower', $csvData[0]));
                            $users = [];
                            for ($i = 1; $i < count($csvData); $i++) {
                                if (empty($csvData[$i]) || count($csvData[$i]) < 2) continue;
                                $row = [];
                                foreach ($headers as $index => $header) {
                                    $row[$header] = $csvData[$i][$index] ?? null;
                                }
                                $users[] = $row;
                            }
                            $jsonPath = $extractPath . '/users_converted.json';
                            file_put_contents($jsonPath, json_encode($users));
                            break;
                        }
                    }
                }
            }
        }

        if (!file_exists($jsonPath)) {
            throw new Exception("Invalid ZIP: No JSON or CSV file found inside ZIP archive.");
        }

        $users = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($users)) {
            throw new Exception("Invalid users.json format.");
        }

        $total = count($users);
        $log->update(['total_records' => $total]);

        $imported = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($users as $index => $user) {
            try {
                if (!isset($user['email'])) {
                    $skipped++;
                    continue;
                }

                $imageFile = $user['profile_pic_filename'] ?? null;
                $imageSourcePath = $extractPath . '/images/' . $imageFile;

                $result = $importService->import($user, $imageSourcePath);

                if ($result['status'] === 'skipped') {
                    $skipped++;
                } else {
                    $imported++;
                }
            } catch (Throwable $e) {
                $failed++;
            }

            // Update progress periodically
            if (($index + 1) % 50 === 0 || ($index + 1) === $total) {
                $log->update([
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'failed' => $failed
                ]);
            }
        }

        $log->update(['status' => 'completed']);
        
        // Cleanup
        File::deleteDirectory($extractPath);
        Storage::delete($this->filePath);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        BulkImportLog::where('id', $this->logId)->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage()
        ]);
    }
}
