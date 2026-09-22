<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'member_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'email_verified_at',
        'date_of_birth',
        'age',
        'contact_number',
        'emergency_contact',
        'address'
    ];

    protected $hidden = ['password', 'remember_token'];

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    public function member()
    {
        return $this->hasOneThrough(Member::class, Customer::class, 'user_id', 'customer_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(MemberSubscription::class);
    }

    public function latestSubscription()
    {
        return $this->hasOne(MemberSubscription::class)->latestOfMany();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class)->orderBy('attendance_date', 'desc')->orderBy('check_in_time', 'desc');
    }

    public function gymNotes()
    {
        return $this->hasMany(GymNote::class)->orderBy('workout_date', 'desc');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}