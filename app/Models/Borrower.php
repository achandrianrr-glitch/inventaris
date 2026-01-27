<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrower extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'class',
        'major',
        'id_number',
        'contact',
        'status'
    ];

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }
}
