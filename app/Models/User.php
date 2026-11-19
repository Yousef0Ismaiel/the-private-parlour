<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['username'];

    protected $hidden = ['password', 'two_factor_secret', 'invite_hash'];

    protected function casts(): array
    {
        return [
            'two_factor_secret' => 'encrypted',
            'invite_expires_at' => 'immutable_datetime',
            'last_totp_step' => 'integer',
        ];
    }
}
