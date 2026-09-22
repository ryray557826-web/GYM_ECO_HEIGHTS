<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $table = 'packages';

    protected $fillable = [
        'package_code',
        'name',
        'plan_type',        // 'monthly', 'daily', 'annual', 'special_promo'
        'price',
        'duration_in_days',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_in_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function features()
    {
        return $this->hasMany(PackageFeature::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(MemberSubscription::class);
    }
}