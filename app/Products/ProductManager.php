<?php
namespace App\Products;

class ProductManager {

    /**
     * Store product service
     *
     * @var array
     */
    private static $productServices = [
        'coaching'   => CoachingProductService::class,
        'group-call' => GroupCallProductService::class,
        'community'  => CommunityProductService::class,
        'digital'    => DigitalProductService::class,
        'affiliate'  => AffiliateProductService::class,
        'service'    => ServiceProductService::class,
    ];

    /**
     * Get product service type
     *
     * @param   string  $type
     *
     * @return
     */
    public static function getProductService( $type )
    {
        return new self::$productServices[$type];
    }
}
