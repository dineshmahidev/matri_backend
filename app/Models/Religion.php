<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'is_active'];

    public function castes()
    {
        return $this->hasMany(Caste::class)->orderBy('name');
    }
}
