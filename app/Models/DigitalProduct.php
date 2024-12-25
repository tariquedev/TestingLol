<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigitalProduct extends Model
{
    use HasFactory;

    public function product()
    {
        return $this->morphOne(Product::class, 'productable');
    }

    public function files()
    {
        return $this->hasMany(DigitalFile::class);
    }

    public function urls()
    {
        return $this->hasMany(RedirectUrl::class);
    }
}
