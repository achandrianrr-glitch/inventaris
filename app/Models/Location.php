<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['name', 'description', 'status'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
