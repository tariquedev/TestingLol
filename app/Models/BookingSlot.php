<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSlot extends Model
{
    protected $guarded = [];
    public function appointment()
    {
        return $this->hasOne(Appointment::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function customers()
    {
        return $this->hasManyThrough(
            Customer::class, // The related model
            Appointment::class, // The intermediate model
            'booking_slot_id', // Foreign key on appointments table
            'id', // Foreign key on customers table
            'id', // Local key on booking_slots table
            'customer_id' // Local key on appointments table
        );
    }

}
