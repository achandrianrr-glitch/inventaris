<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'reference_id',
        'reference_type',
        'is_read',
        'admin_id',
    ];

    // ✅ lebih aman & umum dipakai di Laravel
    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
