<?php

namespace App\Http\Controllers;

use App\Http\Resources\Product\ProductResourceManager;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\CoachingProduct;
use App\Models\Product;
use App\Models\User;
use App\Traits\VisitorTimezoneTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StoreController extends Controller
{
    use VisitorTimezoneTrait;

    public function publicStore($store){
        $user = User::where('store_name', $store)->first();
        if (!$user) {
            return response()->json([
                'message' => "Store not found",
            ],404);
        }
        $userData = (new UserResource($user))->toArray(request());
        $products = Product::with([
            'productable',
            'details',
            'user',
            'coupons',
            ])
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($product) {
                return [
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'discount_price' => $product->discount_price ?? null,
                    'button_text' => $product->details->button_text ?? null,
                    'product_type' => $product->getProductType(),
                    'header_image' => $product->details->getMedia("*")->isNotEmpty() ? route('media.show', [$product->details->getMedia("*")[0]->id, $product->details->getMedia("*")[0]->file_name]): asset('product/header-image.svg'),
                    'thumbnail' => $product->getMedia("*")->isNotEmpty() ? route('media.show', [$product->getMedia("*")[0]->id, $product->getMedia("*")[0]->file_name]): asset('product/'.$product->getProductType().'.svg'),
                    'coupons' => $product->coupons ?? null,

                ];
            });
            return response()->json([
            'message' => "Store details found",
            'data' => [
                'user' => $userData,
                'products' => $products,
            ]
        ]);
    }

    public function storeDetails($store = null, $slug){
        $timezone = $this->getVisitorTimezone();
        $product = Product::where('slug', $slug)->first();
        if (!$product) {
            return response()->json([
                'message' => "Product not found",
            ]);
        }
        $productDetails = new ProductResource($product);
        return response()->json([
            'data' => [
                'productDetails' => $productDetails,
                'visitor_timezone' => $timezone
            ]
        ]);
    }
    public function storeCalendarDetails($slug){
        $visitor_timezone = $this->getVisitorTimezone();
        $products = Product::where('slug', $slug)->with('productable')->first();

        if (!$products) {
            return response()->json([
                'message' => "Product not found",
            ]);
        }

        $product = [
            'id'          => $products->id,
            'title'       => $products->title,
            'slug'       => $products->slug,
            'description' => $products->description,
            'price'       => $products->price == 0 ? 'Free': $products->price,
            'discount_price' => $products->discount_price,
            'currency'    => User::find($products->user_id)->currency ?? "$",
            'status'      => $products->status,
            'type'        => $products->getProductType(),
            'visitor_timezone'   => $visitor_timezone
        ];

        $productaData = ProductResourceManager::getResource($products->getProductType(), $products->productable)->toArray(request());


        $data = array_merge($product, $productaData );
        $schedules = $data['schedules'] ?? [];
        if ($schedules instanceof Collection) {
            $schedules = $schedules->toArray();
        }

        $timezone = $data['visitor_timezone'] ?? $data['timezone'];
        $nextNDays = $productaData['day'];

        $createdAt = $products->updated_at ?
             Carbon::parse($products->updated_at)->setTimezone($timezone) :
             Carbon::now($timezone);
        $currentDate = Carbon::now($timezone);

        // Ensure the start date is not in the past
        $startDate = $createdAt->greaterThan($currentDate) ? $createdAt : $currentDate;

        $availableDates = [];

        for ($i = 0; $i < $nextNDays; $i++) {
            $dayName = $startDate->format('l'); // Get the day name (e.g., Monday)
            if (is_array($schedules) && array_key_exists($dayName, $schedules)) {
                $availableDates[] = [
                    'date' => $startDate->format('Y-m-d'),
                    'day' => $dayName,
                ];
            }
            $startDate->addDay();
        }

        $data['calendar'] = $availableDates;
        return response()->json([
            'data' => $data
        ]);
    }

    public function getAvailableSlots($slug, $date)
    {
        $selectedDate = $date;
        $timezone = $this->getVisitorTimezone();

        if (!$selectedDate) {
            return response()->json(['success' => false, 'message' => 'Date is required.'], 400);
        }

        $date = Carbon::parse($selectedDate, $timezone);

        $product = Product::where('slug', $slug)->first();
        $schedules = $product->productable->schedules ?? collect();
        $groupedSchedules = $schedules->groupBy('day');

        $dayName = $date->format('l'); // Get the day name (e.g., "Tuesday")

        if (!$groupedSchedules->has($dayName)) {
            return response()->json(['success' => false, 'message' => 'No slots available for this day.']);
        }

        $duration = (int) $product->productable->duration;
        $slots = [];

        foreach ($groupedSchedules->get($dayName) as $timeRange) {
            $scheduleId = $timeRange['id'];
            $slotStart = Carbon::parse($timeRange['start_at'], $timezone)->setDate(
                $date->year,
                $date->month,
                $date->day
            );
            $slotEnd = Carbon::parse($timeRange['end_at'], $timezone)->setDate(
                $date->year,
                $date->month,
                $date->day
            );

            while ($slotStart->lt($slotEnd)) {
                $nextSlot = $slotStart->copy()->addMinutes($duration);
                if ($nextSlot->lte($slotEnd)) {
                    $slots[] = [
                        'schedule_id' => $scheduleId,
                        'start' => $slotStart->format('h:i'),
                        'end'   => $nextSlot->format('h:i'),
                        'meridiem'   => $slotStart->format('A'),
                    ];
                }
                $slotStart = $nextSlot;
            }
        }
        $data = [
            'id'          => $product->id,
            'title'       => $product->title,
            'slug'        => $product->slug,
            'description' => $product->description,
            'price'       => $product->price == 0 ? 'Free': $product->price,
            'discount_price' => $product->discount_price,
            'currency'    => User::find($product->user_id)->currency ?? "$",
            'status'      => $product->status,
            'type'        => $product->getProductType(),
            'duration'    => $product->productable->duration,
            'timezone'    => $product->productable->timezone,
            'visitor_timezone' => $this->getVisitorTimezone(),
            'details'     => (new ProductDetailResource($product->details))->toArray(request()),
        ];
        return response()->json([
            'success' => true,
            'data' => [
                'products' => $data,
                'slots' => $slots
            ]
        ]);
    }
}
