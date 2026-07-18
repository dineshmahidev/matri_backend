<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'is_active'];

    public function cities()
    {
        return $this->hasMany(City::class)->orderBy('name');
    }
}
