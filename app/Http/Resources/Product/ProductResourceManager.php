<?php

namespace App\Http\Resources\Product;

use Illuminate\Support\Facades\Log;

class ProductResourceManager
{
    /**
     * Store product resource
     *
     * @var string
     */
    protected $productResource;

    /**
     * Store resource type
     *
     * @var array
     */
    protected $resourceTypes = [
        'coaching'      => CoachingProductResource::class,
        'group-call'    => GroupCallProductResource::class,
        'community'     => CommunityProductResource::class,
        'digital'       => DigitalProductResource::class,
        'affiliate'     => AffiliateProductResource::class,
        'service'       => ServiceProductResource::class,
    ];

    /**
     * Resolve the product resource based on the type.
     *
     * @param string $type
     * @param mixed $product
     * @return mixed
     * @throws \Exception
     */
    protected function resolveProductResource(string $type, $product)
    {
        return new $this->resourceTypes[$type]($product);
    }

    /**
     * Return the product resource.
     *
     * @return mixed
     */
    public static function getResource(string $type, $product)
    {
        $static = new self();
        
        return $static->resolveProductResource($type, $product);
    }

    /**
     * Automatically return the resource when casting the object.
     *
     * @return mixed
     */
    public function __toString()
    {
        return $this->productResource;
    }

}