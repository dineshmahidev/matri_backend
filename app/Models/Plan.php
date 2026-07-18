<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['slug', 'name', 'price', 'period', 'color', 'popular', 'features', 'contact_quota', 'message_quota', 'credits', 'interest_express_limit', 'profile_show_limit', 'image_upload_limit', 'is_active'];
    protected function casts(): array { return ['features' => 'array', 'popular' => 'boolean', 'is_active' => 'boolean']; }
}
