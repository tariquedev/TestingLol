<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ImageResource;
use App\Http\Resources\ProductResource;
use App\Models\CoachingProduct;
use App\Models\Coupon;
use App\Models\EmailReminder;
use App\Models\EmailTemplate;
use App\Models\Field;
use App\Models\Product;
use App\Models\ProductDetail;
use Database\Factories\ProductFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Products\ProductManager;
use Illuminate\Support\Facades\Auth;
use Stripe\Price;
use Stripe\Stripe;

class ProductController extends Controller
{
    public function index()
    {
        return ProductResource::collection(Product::where('user_id', Auth::id())->orderBy('ordering','asc')->get());
    }

    public function store(StoreProductRequest $request)
    {
        $user = auth()->user();
        $productType =  ProductManager::getProductService($request->type)->save($request);

        // Save base product data.
        $product = new Product;
        $product->title       = $request->title;
        $product->slug        = Str::slug($request->title .'-'. Str::random(5));
        $product->description = $request->description;
        $product->price       = $request->price;
        $product->discount_price  = $request->discount_price;
        $product->user_id     = $user->id;
        $product->status     = $request->status;

        $product = $productType->product()->save($product);

        if ($request->thumbnail_media_id) {
            ImageResource::associateImageWithModel($request->thumbnail_media_id, $product, 'thumbnail');
        }
        // Save product details.
        $productDetail = new ProductDetail;
        $productDetail->button_text  = $request->button_text;
        $productDetail->thumbnail_description = $request->thumbnail_description;
        $productDetail->header_image = $request->header_image;
        $productDetail->bottom_title = $request->bottom_title;
        $productDetail->bottom_button_text = $request->bottom_button_text;
        $productDetail->promo_video = $request->promo_video;
        $product->details()->save($productDetail);

        if ($request->header_media_id) {
            ImageResource::associateImageWithModel($request->header_media_id, $productDetail, 'header_image' );
        }
        // Save product email template.
        $email = new EmailTemplate;

        // Map each email template data to a new EmailTemplate model instance
        if ( $request->emails ) {
            $emailTemplates = array_map(function ($template) {
                return new EmailTemplate($template);
            }, $request->emails);
            // Save the email templates using the relationship (saveMany)
            $product->emails()->saveMany($emailTemplates);
        }
        if ($request->reminders) {
            $emailReminders = array_map(function ($reminder) {
                return new EmailReminder($reminder);
            }, $request->reminders);

            $product->reminders()->saveMany($emailReminders);
        }

        // Save coupons
        if($request->coupons){
            $coupons = array_map(function($coupon) {
                return new Coupon($coupon);
            }, $request->coupons);

            $product->coupons()->saveMany($coupons);
        }

        // Save extra fields.
        if($request->extra_fields) {
            $fields = collect($request->extra_fields)->map(function($field) {
                return new Field($field);
            });

            $product->fields()->saveMany($fields);
        }

        return new ProductResource($product);
    }

    public function update(Product $product, StoreProductRequest $request) {

        $productType =  ProductManager::getProductService($request->type)->update($product->productable, $request);

        // Save base product data.
        $product->title       = $request->get('title', $product->title);
        $product->description = $request->get('description', $product->description);
        $product->price       = $request->get('price', $product->price);
        $product->discount_price       = $request->get('discount_price', $product->discount_price);

        $product = $productType->product()->save($product);
        if ($request->thumbnail_media_id) {
            $product->clearMediaCollection('thumbnail');
            ImageResource::associateImageWithModel($request->thumbnail_media_id, $product, $request->collectionName ?? 'thumbnail');
        }
        // Save product details.
        $productDetail = $product->details;
        $productDetail->button_text  = $request->get('button_text', $productDetail->button_text );
        $productDetail->thumbnail_description  = $request->get('thumbnail_description', $productDetail->thumbnail_description );

        if ($request->thumbnail_media_id) {
            $productDetail->clearMediaCollection('thumbnail');
            ImageResource::associateImageWithModel($request->header_media_id, $productDetail, $request->collectionName ?? 'header_image');
        }

        $productDetail->bottom_title = $request->get('bottom_title', $productDetail->bottom_title );

        $productDetail->bottom_button_text = $request->get('bottom_button_text', $productDetail->bottom_button_text);

        $productDetail->promo_video = $request->get('promo_video', $productDetail->promo_video);

        $product->details()->save($productDetail);

        // Save product email template.
        if ( $request->emails ) {
            $product->emails()->delete();
            $emailTemplates = array_map(function ($template) {
                return new EmailTemplate($template);
            }, $request->emails);
            // Save the email templates using the relationship (saveMany)
            $product->emails()->saveMany($emailTemplates);
        }

        // Save product email reminders.
        if ( $request->reminders ) {
            $product->reminders()->delete();
            $reminders = array_map(function ($reminder) {
                return new EmailReminder($reminder);
            }, $request->reminders);
            // Save the email templates using the relationship (saveMany)
            $product->reminders()->saveMany($reminders);
        }

        // Save coupons
        if($request->coupons){
            $product->coupons()->delete();

            $coupons = array_map(function($coupon) {
                return new Coupon($coupon);
            }, $request->coupons);

            $product->coupons()->saveMany($coupons);
        }

        // Save extra fields.
        if($request->extra_fields) {
            $product->fields()->delete();
            $fields = collect($request->extra_fields)->map(function($field) {
                return new Field($field);
            });

            $product->fields()->saveMany($fields);
        }

        return new ProductResource($product);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function delete(Product $product)
    {
        $product->productable()->getMedia('thumbnail')->first();
        $product->productable()->getMedia('header_image')->first();

        $product->productable()->delete();
        $product->delete();

        return response()->json([
            'message'   => 'Successfully deleted product'
        ]);
    }

    public function uploadImage(Request $request, $collectionName = 'payload')
    {
        return ImageResource::storeImage($request, $collectionName);
    }

    function reordering(Request $request){
        foreach ($request->product_id as $index => $productId) {
            Product::where('id',$productId)->where('user_id', Auth::id())->update(['ordering' => $index + 1]);
        }

        return response()->json([
            'message' => "Product ordering update successfully",
            "product" => ProductResource::collection(Product::where('user_id', Auth::id())->orderBy('ordering','asc')->get()),
        ]);
    }
}
