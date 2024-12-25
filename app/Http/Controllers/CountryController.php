<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Stevebauman\Location\Facades\Location;

class CountryController extends Controller
{
    public function index()
    {
        return Country::getCounties();
    }

    public function getCountry()
    {
        $position = Location::get(request()->ip());

        if ($position) {
            return  Country::findByCode($position->countryCode);
        }

        return null;
    }

    public function getIp()
    {
        return request()->ip();
    }
    public function getCurrencySymbols()
    {
        $path = public_path('currency.json');
        if (!File::exists($path)) {
            return response()->json([
                'message' => "File not found"
            ]);
        }
        $json = File::get($path);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'message' => "Invalid JSON format"
            ]);
        }

        return response()->json([
            "message" => "Valid JSON format",
            "data" => $data
        ]);
    }
}
