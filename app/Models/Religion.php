<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'name'];

    public function castes()
    {
        return $this->hasMany(Caste::class)->orderBy('name');
    }
}
