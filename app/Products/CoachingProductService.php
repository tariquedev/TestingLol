<?php
namespace App\Products;

use App\Http\Requests\StoreProductRequest;
use App\Models\CoachingProduct;
use App\Models\Schedule;

/**
 * Coaching product service class
 *
 * Handle sotre or update coaching product
 */
class CoachingProductService implements ProductServiceContract {
    /**
     * Store or update coaching product
     *
     * @return CoachingProduct
     */
    public function save(StoreProductRequest $request)
    {
        $product = new CoachingProduct;
        $product->day = $request->day;
        $product->quantity = $request->quantity;
        $product->duration = $request->duration;
        $product->timezone = $request->timezone;
        $product->interval = $request->interval;
        $product->platform = $request->platform;
        $product->max_attendee = $request->max_attendee;

        $product->save();

        if ( $request->schedules ) {
            $schedules = collect($request->schedules)
            ->flatMap(function($timeslots, $day) {
                return collect($timeslots)->map(function($timeslot) use ($day) {
                    return array_merge($timeslot, ['day' => $day]);
                });
            });
            $schedules = $schedules->map(function($timeslot) {
                return new Schedule($timeslot);
            });
            $product->schedules()->saveMany($schedules);
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
        $productable->duration = $request->get('duration', $productable->duration);
        $productable->day = $request->get('day', $productable->day);
        $productable->timezone = $request->get('timezone', $productable->timezone);
        $productable->interval = $request->get('interval', $productable->interval);
        $productable->platform = $request->get('platform', $productable->platform);
        $productable->max_attendee = $request->get('max_attendee', $productable->max_attendee);

        $productable->save();

        if ( $request->schedules ) {
            $productable->schedules()->delete();

            $schedules = collect($request->schedules)
            ->flatMap(function($timeslots, $day) {
                return collect($timeslots)->map(function($timeslot) use ($day) {
                    return array_merge($timeslot, ['day' => $day]);
                });
            });

            $schedules = $schedules->map(function($timeslot) {
                return new Schedule($timeslot);
            });

            $productable->schedules()->saveMany($schedules);
        }

        return $productable;
    }
}
