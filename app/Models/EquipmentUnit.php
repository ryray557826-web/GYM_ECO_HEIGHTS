<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentUnit extends Model
{
    use HasFactory;

    protected $table = 'equipment_units';
    protected $guarded = [];

    protected $casts = [
        'last_maintenance_date' => 'date',
        'next_maintenance_due'  => 'date',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function maintenances()
    {
        return $this->hasMany(EquipmentMaintenance::class, 'equipment_unit_id')->orderBy('maintenance_date', 'desc');
    }
}