<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Damage extends Model
{
    protected $fillable = [
        'code',
        'item_id',
        'borrowing_id',
        'damage_level',
        'description',
        'reported_date',
        'status',
        'solution',
        'completion_date',
        'admin_id'
    ];

    protected function casts(): array
    {
        return [
            'reported_date' => 'date',
            'completion_date' => 'date',
        ];
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function borrowing()
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
