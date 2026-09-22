<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'position',         // 'owner', 'front_desk', 'attendant', 'encoder', 'collector'
        'contact_number',
        'hire_date',
        'status',           // 'active', 'on_leave', 'resigned'
    ];

    protected $casts = [
        'hire_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendancesLogged()
    {
        return $this->hasMany(Attendance::class, 'logged_by_employee_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}