<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Borrower extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',       // student | teacher
        'class',
        'major',
        'id_number',
        'contact',
        'status',     // active | blocked
    ];

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }
}
