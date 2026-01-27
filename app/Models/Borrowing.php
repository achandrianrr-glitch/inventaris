<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrowing extends Model
{
    protected $fillable = [
        'code',
        'borrower_id',
        'item_id',
        'qty',
        'borrow_type',
        'lesson_hour',
        'subject',
        'teacher',
        'borrow_date',
        'borrow_time',
        'return_due',
        'return_date',
        'return_condition',
        'status',
        'admin_id',
        'notes'
    ];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'borrow_time' => 'string',
            'return_due' => 'datetime',
            'return_date' => 'datetime',
        ];
    }

    public function borrower()
    {
        return $this->belongsTo(Borrower::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function damages()
    {
        return $this->hasMany(Damage::class);
    }
}
