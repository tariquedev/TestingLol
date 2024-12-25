<?php
namespace App\Products;

use App\Http\Requests\StoreProductRequest;
use App\Models\DigitalFile;
use App\Models\DigitalProduct;
use App\Models\RedirectUrl;

/**
 * digital product service class
 *
 * Handle sotre or update digital product
 */
class DigitalProductService implements ProductServiceContract {
    /**
     * Store or update DigitalProduct product
     *
     * @return DigitalProduct
     */
    public function save(StoreProductRequest $request)
    {
        $product = new DigitalProduct();

        $product->save();

        if ( $request->file_urls ) {
            $urls = collect($request->file_urls)->map(function($url) {
                return new DigitalFile(['file_url' => $url]);
            });

            $product->files()->saveMany($urls);
        }

        if ( $request->redirect_urls ) {
            $urls = collect($request->redirect_urls)->map(function($url) {
                return new RedirectUrl($url);
            });

            $product->urls()->saveMany($urls);
        }


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

        if ( $request->file_urls ) {
            $productable->files()->delete();

            $urls = collect($request->file_urls)->map(function($url) {
                return new DigitalFile(['file_url' => $url]);
            });

            $productable->files()->saveMany($urls);
        }

        if ( $request->redirect_urls ) {
            $productable->urls()->delete();

            $urls = collect($request->redirect_urls)->map(function($url) {
                return new RedirectUrl($url);
            });

            $productable->urls()->saveMany($urls);
        }


        return $productable;
    }
}
