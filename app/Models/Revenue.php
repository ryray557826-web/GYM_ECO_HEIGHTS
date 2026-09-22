<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    use HasFactory;

    protected $table = 'revenues';

    protected $fillable = [
        'revenue_code',
        'payment_id',
        'budget_id',
        'amount',
        'revenue_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'revenue_date' => 'date',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }
}