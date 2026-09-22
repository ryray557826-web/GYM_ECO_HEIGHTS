<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentCategory extends Model
{
    use HasFactory;

    protected $table = 'equipment_categories';

    protected $fillable = [
        'category_code',
        'name',
        'description',
    ];

    public function equipment()
    {
        return $this->hasMany(Equipment::class, 'category_id');
    }
}