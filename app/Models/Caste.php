<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caste extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'religion_id', 'name', 'is_active'];

    public function religion()
    {
        return $this->belongsTo(Religion::class);
    }
}
