<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'title', 'description', 'type', 'read'];
    protected function casts(): array { return ['read' => 'boolean']; }

    public function user() { return $this->belongsTo(User::class); }
}
