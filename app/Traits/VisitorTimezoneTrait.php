<?php

namespace App\Traits;

trait VisitorTimezoneTrait
{
    private function getVisitorIp()
    {
        if (!empty(request()->ip())) {
            return request()->ip();
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    public function getVisitorTimezone()
    {
        $ip = $this->getVisitorIp();
        $apiUrl = "http://ip-api.com/json/{$ip}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $geoData = $response ? json_decode($response, true) : null;

        if ($geoData && $geoData['status'] === 'success') {
            return $geoData['timezone'];
        }

        return env('APP_TIMEZONE');
    }
}