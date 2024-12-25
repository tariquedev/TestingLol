<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $guarded = [];
    public function details()
    {
        return $this->hasOne(ProductDetail::class);
    }

    public function emails()
    {
        return $this->hasMany(EmailTemplate::class);
    }

    public function reminders()
    {
        return $this->hasMany(EmailReminder::class);
    }

    public function productable()
    {
        return $this->morphTo();
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function getProductType()
    {
        $productTypes = [
            'App\Models\CoachingProduct'    => 'coaching',
            'App\Models\GroupCallProduct'   => 'group-call',
            'App\Models\AffiliateProduct'   => 'affiliate',
            'App\Models\CommunityProduct'   => 'community',
            'App\Models\DigitalProduct'     => 'digital',
            'App\Models\ServiceProduct'     => 'service',
        ];

        return $productTypes[$this->productable_type];
    }

    public function fields()
    {
        return $this->hasMany(Field::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
