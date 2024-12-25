<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class Customer extends Model
{
    use Billable;

    protected $guarded = [];

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }
}
