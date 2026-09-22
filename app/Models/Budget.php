<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $table = 'budgets';

    protected $fillable = [
        'budget_code',
        'category_name',
        'fiscal_year',
        'allocated_amount',
        'spent_amount',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'allocated_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
    ];

    public function revenues()
    {
        return $this->hasMany(Revenue::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) ($this->allocated_amount - $this->spent_amount);
    }
}