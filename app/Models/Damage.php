<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Damage extends Model
{
    use HasFactory;

    // optional kalau kamu mau eksplisit
    // protected $table = 'damages';

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
        'admin_id',
    ];

    protected $casts = [
        'reported_date' => 'date',
        'completion_date' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
