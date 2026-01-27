<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $fillable = [
        'code',
        'opname_date',
        'item_id',
        'system_stock',
        'physical_stock',
        'difference',
        'status',
        'validation',
        'admin_id',
        'notes'
    ];

    protected function casts(): array
    {
        return [
            'opname_date' => 'date',
        ];
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
