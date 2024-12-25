<?php
namespace App\Products;

use App\Http\Requests\StoreProductRequest;
use App\Models\CommunityProduct;

class CommunityProductService implements ProductServiceContract {
    /**
     * Store or update Community product
     *
     * @param   StoreProductRequest  $request
     *
     * @return  CommunityProduct
     */
    public function save(StoreProductRequest $request)
    {
        $product = new CommunityProduct;

        $product->title = $request->community_name;
        $product->url   = $request->community_url;

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
        $productable->title = $request->get('community_name', $productable->community_name);
        $productable->url   = $request->get('community_url', $productable->community_url);

        $productable->save();

        return $productable;
    }
}
