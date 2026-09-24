<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';
    protected $guarded = [];

    protected $casts = [
        'posted_date' => 'date',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }
}