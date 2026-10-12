<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    // Allows all fields (expense_code, budget_id, maintenance_id, etc.)
    protected $guarded = [];

    protected $casts = [
        'expense_date' => 'date',   // ← this is the important fix
        'amount'       => 'float',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function maintenance()
    {
        return $this->belongsTo(EquipmentMaintenance::class, 'maintenance_id');
    }
}