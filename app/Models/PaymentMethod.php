<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    protected $fillable = [
        'code',
        'name',
        'is_online',
        'requires_reference',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'requires_reference' => 'boolean',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}