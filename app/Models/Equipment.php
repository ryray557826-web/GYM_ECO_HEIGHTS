<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = [
        'equipment_code',
        'code',
        'category_id',
        'name',
        'brand',
        'quantity',
        'location_in_gym',
        'status',
        'purchase_date',
        'next_maintenance_due',
    ];

    // Category Relationship (Free Weights, Cardio, Machines, etc.)
    public function category()
    {
        return $this->belongsTo(EquipmentCategory::class, 'category_id');
    }

    // NEW: Specific physical units (e.g., EQP-001-U1, EQP-001-U2...)
    public function units()
    {
        return $this->hasMany(EquipmentUnit::class, 'equipment_id');
    }

    // Maintenance history across this equipment
    public function maintenances()
    {
        return $this->hasMany(EquipmentMaintenance::class, 'equipment_id')->orderBy('maintenance_date', 'desc');
    }

    // Most recent maintenance entry
    public function latestMaintenance()
    {
        return $this->hasOne(EquipmentMaintenance::class, 'equipment_id')->latestOfMany();
    }
}