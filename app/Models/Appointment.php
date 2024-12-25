<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function bookingSlot()
    {
        return $this->belongsTo(BookingSlot::class, 'booking_slot_id');
    }

    public function dynamicFields()
    {
        return $this->hasMany(CustomerDynamicField::class, 'appointment_id');
    }
}
