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

    // This is the missing relationship method:
    public function category()
    {
        return $this->belongsTo(EquipmentCategory::class, 'category_id');
    }

    public function maintenances()
    {
        return $this->hasMany(EquipmentMaintenance::class, 'equipment_id')->orderBy('maintenance_date', 'desc');
    }

    public function latestMaintenance()
    {
        return $this->hasOne(EquipmentMaintenance::class, 'equipment_id')->latestOfMany();
    }
}