<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $casts = [
        'is_default_payment_method' => 'boolean'
    ];
    public function address()
    {
        return $this->hasOne(BankAddress::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
