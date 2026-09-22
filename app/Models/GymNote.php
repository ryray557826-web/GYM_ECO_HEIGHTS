<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GymNote extends Model
{
    use HasFactory;

    protected $table = 'gym_notes';

    protected $fillable = [
        'member_id',
        'attendance_id',
        'workout_date',
        'routine_title',
        'notes',
    ];

    protected $casts = [
        'workout_date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}