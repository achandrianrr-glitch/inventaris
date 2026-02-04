<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login' => 'datetime',
        ];
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'admin_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'admin_id');
    }

    public function damages()
    {
        return $this->hasMany(Damage::class, 'admin_id');
    }

    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class, 'admin_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'admin_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'admin_id');
    }
}
