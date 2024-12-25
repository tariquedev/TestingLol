<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Onboard extends Model
{
    use HasFactory;
    protected $fillable = ['checkout','email_verification'];
    protected $casts = [
        'checkout' => 'boolean',
        'email_verification' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
