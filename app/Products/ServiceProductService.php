<?php
namespace App\Products;

use App\Http\Requests\StoreProductRequest;
use App\Models\CoachingProduct;
use App\Models\ServiceProduct;

/**
 * ServiceProduct product service class
 *
 * Handle sotre or update ServiceProduct product
 */
class ServiceProductService implements ProductServiceContract {
    /**
     * Store or update ServiceProduct product
     *
     * @return ServiceProduct
     */
    public function save(StoreProductRequest $request)
    {
        $product = new ServiceProduct;

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
        $productable->save();

        return $productable;
    }
}
