<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $guarded = [];

    // The missing relationship method:
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function member()
    {
        return $this->hasOne(Member::class, 'customer_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'customer_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'customer_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}