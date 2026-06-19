<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'state_id', 'name'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
