<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['slug', 'name', 'price', 'period', 'color', 'popular', 'features', 'contact_quota', 'message_quota', 'credits'];
    protected function casts(): array { return ['features' => 'array', 'popular' => 'boolean']; }
}
