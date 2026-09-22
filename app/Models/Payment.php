<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'payment_code',
        'customer_id',
        'member_id',
        'member_subscription_id',
        'payment_method_id',
        'amount',
        'payment_type',      // 'monthly_subscription', 'per_session'
        'reference_number',
        'status',            // 'pending', 'verified', 'rejected'
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription()
    {
        return $this->belongsTo(MemberSubscription::class, 'member_subscription_id');
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function revenue()
    {
        return $this->hasOne(Revenue::class);
    }

    public function attendance()
    {
        return $this->hasOne(Attendance::class);
    }
}