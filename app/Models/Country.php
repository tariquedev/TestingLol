<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nakanakaii\Countries\Countries;

class Country extends Model
{
    use HasFactory;

    public static function getCounties()
    {

        return (new self)->countries()
            ->map(function($country) {
                $flag = strtolower($country->code);

                $country->flag = asset("vendor/countries/flags/{$flag}.png");

                return $country;
            });
    }

    protected function countries()
    {
        $countries = collect(Countries::all());

        return $countries->map(function($country) {
            return (object) $country;
        });
    }

    public static function findByCode($code)
    {
        $country = (object) Countries::findByCode($code);
        $flag = strtolower($code);

        $country->flag = asset("vendor/countries/flags/{$flag}.png");

        return $country;
    }

    public static function getCodes()
    {
        return self::getProperty('code');
    }

    public static function getFlags()
    {
        return self::getProperty('flag');
    }

    private static function getProperty($property)
    {
        return self::getCounties()->pluck($property)->toArray();
    }
}
