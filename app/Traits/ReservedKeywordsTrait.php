<?php

namespace App\Traits;

trait ReservedKeywordsTrait
{
    public static $reservedKeywords = [
        'blog', 'user', 'profile', 'flexpoint', 'support',
        'about', 'terms', 'service', 'policy', 'gdpr',
        'article', 'password'
    ];

    public static function isReservedKeyword($keyword){
        return in_array($keyword, self::$reservedKeywords);
    }

    public static function getReservedKeywords(){
        return self::$reservedKeywords;
    }
}
