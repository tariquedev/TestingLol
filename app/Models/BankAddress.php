<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAddress extends Model
{
    use HasFactory;

    public function banck()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
