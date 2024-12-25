<?php

namespace App\Http\Resources;

use App\Http\Resources\Product\ProductResourceManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'       => $this->slug,
            'description' => $this->description ?? "",
            'price'       => $this->price,
            'discount_price' => $this->discount_price ?? "",
            'currency'    => User::find($this->user_id)->currency ?? "$",
            'status'      => $this->status,
            'type'        => $this->getProductType(),
            'emails'      => EmailTemplateResource::collection($this->emails),
            'coupons'     => CouponResource::collection($this->coupons),
            'fields'      => FieldResource::collection($this->fields),
            'reminders'   => EmailReminderResource::collection($this->reminders),
        ];

        if ($this->getMedia("*")->isNotEmpty()) {
            $media = $this->getMedia("*")[0]; // Get the first media file
            $data['thumbnail'] = route('media.show', [$media->id, $media->file_name]);
        }
        else{
            $data['thumbnail'] = asset('product/'.$this->getProductType().'.svg');
        }
        if ($this->details->getMedia("*")->isNotEmpty()) {
            $media = $this->details->getMedia("*")[0]; // Get the first media file
            $data['header_image'] = route('media.show', [$media->id, $media->file_name]);
        }
        else{
            $data['header_image'] = asset('product/header-image.svg');
        }
        $details = (new ProductDetailResource($this->details))->toArray(request());
        $productaData = ProductResourceManager::getResource($this->getProductType(), $this->productable)->toArray(request());

        $data = array_merge( $data, $details, $productaData );

        return $data;
    }
}
