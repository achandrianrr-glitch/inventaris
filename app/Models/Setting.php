<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'school_name',
        'city',
        'code_format',
        'notification_email',
        'notification_wa'
    ];
}
