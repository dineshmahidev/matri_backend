<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ImportProfilesJob;
use App\Models\BulkImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BulkUploadController extends Controller
{
    /**
     * Handle the upload of a bulk import ZIP file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:zip|max:102400', // max 100MB
        ]);

        $path = $request->file('file')->store('temp');

        $log = BulkImportLog::create([
            'file_name' => $request->file('file')->getClientOriginalName(),
            'status' => 'pending'
        ]);

        // Dispatch the job
        ImportProfilesJob::dispatch($log->id, $path);

        return response()->json([
            'message' => 'Import queued successfully',
            'log_id' => $log->id
        ]);
    }

    /**
     * Check the status of a bulk import log
     */
    public function status($id)
    {
        $log = BulkImportLog::findOrFail($id);
        
        return response()->json([
            'status' => $log->status,
            'total_records' => $log->total_records,
            'imported' => $log->imported,
            'skipped' => $log->skipped,
            'failed' => $log->failed,
            'error_message' => $log->error_message
        ]);
    }
}
