<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberSubscription extends Model
{
    use HasFactory;

    protected $table = 'member_subscriptions';

    protected $fillable = [
        'member_id',
        'package_id',
        'start_time',
        'end_time',
        'status',           // 'active', 'expired', 'pending', 'cancelled'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}