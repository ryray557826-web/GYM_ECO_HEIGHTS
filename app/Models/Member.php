<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';
    
    // Allows all attributes (verification_status, suspension_reason, reward_points) to save
    protected $guarded = [];

    protected $casts = [
        'joined_date'   => 'date',
        'reward_points' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
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
        return $this->hasMany(Attendance::class)->orderBy('attendance_date', 'desc')->orderBy('check_in_time', 'desc');
    }

    public function gymNotes()
    {
        return $this->hasMany(GymNote::class)->orderBy('workout_date', 'desc');
    }
}