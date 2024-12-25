<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleCalendarToken extends Model
{
    protected $guarded = [];
    protected $casts = [
        'token_expires_at' => 'datetime',
    ];
}
