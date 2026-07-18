<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['display_id', 'name', 'phone', 'email', 'source', 'status', 'assigned_to', 'created_by'];

    public function assignedStaff() { return $this->belongsTo(User::class, 'assigned_to'); }
    
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function notes()
    {
        return $this->hasMany(LeadNote::class)->latest();
    }
}
