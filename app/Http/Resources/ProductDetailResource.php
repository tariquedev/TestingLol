<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'button_text'        => $this->button_text ?? "",
            'thumbnail_description' => $this->thumbnail_description ?? "",
            // 'header_image'       => $this->header_image ?? "",
            'bottom_title'       => $this->bottom_title ?? "",
            'bottom_button_text' => $this->bottom_button_text ?? "",
            'promo_video'        => $this->promo_video ?? "",
        ];
    }
}
