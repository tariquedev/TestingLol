<?php
namespace App\Products;

use App\Http\Requests\StoreProductRequest;
use App\Models\Event;
use App\Models\GroupCallProduct;

/**
 * GroupCall product service class
 *
 * Handle sotre or update GroupCall product
 */
class GroupCallProductService implements ProductServiceContract {
    /**
     * Store or update coaching product
     *
     * @return CoachingProduct
     */
    public function save(StoreProductRequest $request)
    {
        $product = new GroupCallProduct;
        $product->quantity = $request->quantity;
        $product->duration = $request->duration;
        $product->interval = $request->interval;
        $product->timezone = $request->timezone;
        $product->platform = $request->platform;
        $product->max_attendee = $request->max_attendee;

        $product->save();

        $events = collect($request->events)->map(function($event) {
            return new Event($event);
        });

        $product->events()->saveMany($events);

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
        $productable->duration = $request->get('duration', $productable->duration);
        $productable->timezone = $request->get('timezone', $productable->timezone);
        $productable->interval = $request->get('interval', $productable->interval);
        $productable->platform = $request->get('platform', $productable->platform);
        $productable->max_attendee = $request->get('max_attendee', $productable->max_attendee);

        $productable->save();

        $productable->events()->delete();

        $events = collect($request->events)->map(function($event) {
            return new Event($event);
        });

        $productable->events()->saveMany($events);

        return $productable;
    }
}
