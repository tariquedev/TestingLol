<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoachingProduct extends Model
{
    use HasFactory;

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function email()
    {
        return $this->hasOne(EmailTemplate::class);
    }

    public function product()
    {
        return $this->morphOne(Product::class, 'productable');
    }
}
