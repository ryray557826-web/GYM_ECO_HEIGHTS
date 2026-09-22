<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentMaintenance extends Model
{
    use HasFactory;

    protected $table = 'equipment_maintenances';

    protected $fillable = [
        'maintenance_code',
        'equipment_id',
        'maintenance_date',
        'facility_or_location',
        'cost',
        'technician_name',
        'issue_description',
        'work_done',
        'status',           // 'scheduled', 'in_progress', 'completed'
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function expense()
    {
        return $this->hasOne(Expense::class, 'maintenance_id');
    }
}