<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloodGroup extends Model
{
    protected $fillable = ['name', 'name_ta', 'is_active', 'id'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
