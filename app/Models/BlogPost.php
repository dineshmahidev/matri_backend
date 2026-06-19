<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = ['title', 'slug', 'category', 'read_time', 'published_at', 'image', 'excerpt', 'body'];
    protected function casts(): array { return ['published_at' => 'date']; }
}
