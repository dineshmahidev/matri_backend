<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'total_records',
        'imported',
        'skipped',
        'failed',
        'status',
        'error_message'
    ];
}
