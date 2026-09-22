<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'customer_id',
        'member_id',
        'payment_id',
        'logged_by_employee_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'entry_type',        // 'membership', 'per_session'
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function logger()
    {
        return $this->belongsTo(Employee::class, 'logged_by_employee_id');
    }

    public function gymNote()
    {
        return $this->hasOne(GymNote::class);
    }
}