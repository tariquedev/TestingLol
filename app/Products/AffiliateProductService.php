<?php
namespace App\Products;

use App\Http\Requests\StoreProductRequest;
use App\Models\AffiliateProduct;

/**
 * Affiliate product service class
 *
 * Handle sotre or update coaching product
 */
class AffiliateProductService implements ProductServiceContract {
    /**
     * Store or update coaching product
     *
     * @return AffiliateProductService
     */
    public function save(StoreProductRequest $request)
    {
        $product = new AffiliateProduct;

        $product->product_link = $request->product_link;

        $product->save();

        return $product;
    }

    /**
     * Update productable
     *
     * @param   Object  $productable  [$productable description]
     *
     * @return  Object
     */
    public function update($productable, StoreProductRequest $request)
    {
        $productable->product_link = $request->get('product_link', $productable->product_link);

        $productable->save();

        return $productable;
    }
}
