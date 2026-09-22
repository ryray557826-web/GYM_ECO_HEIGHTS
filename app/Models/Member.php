<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $fillable = [
        'customer_id',
        'member_code',      // E.g., ECO-001
        'joined_date',
        'membership_status',// 'active', 'expired', 'pending', 'cancelled'
        'medical_notes',
    ];

    protected $casts = [
        'joined_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(MemberSubscription::class);
    }

    public function latestSubscription()
    {
        return $this->hasOne(MemberSubscription::class)->latestOfMany();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function gymNotes()
    {
        return $this->hasMany(GymNote::class)->orderBy('workout_date', 'desc');
    }
}