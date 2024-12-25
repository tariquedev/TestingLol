<?php
namespace App\Products;

use App\Http\Requests\StoreProductRequest;

interface ProductServiceContract {
    /**
     * Save product
     *
     * @return Model
     */
    public function save(StoreProductRequest $request);

    /**
     * Update productable
     *
     * @return Model
     */
    public function update($productable, StoreProductRequest $request);
}
