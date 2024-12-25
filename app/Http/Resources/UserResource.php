<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'country'    => $this->country,
            'currency'   => $this->currency,
            'currency_symbol'   => $this->currency_symbol,
            'onboard'    => new OnboardResource($this->onboard),
            'store_name' => $this->store_name,
            'avatar'     => $this->getMedia("*")->isNotEmpty() ? route('media.show', [$this->getMedia("*")[0]->id, $this->getMedia("*")[0]->file_name]) : asset('images/demo.webp'),
            'reg_source' => $this->reg_source,
            'branding'   => $this->branding,
            'details'    => new UserDetailsResource($this->userDetails),
            // 'products'   => ProductResource::collection(
            //     $this->products()->where('user_id', $this->id)->orderBy('ordering', 'asc')->get()
            // ),
        ];
    }
}
