<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'bio'  => $this->bio ?? null,
            'occupation'  => $this->occupation ?? null,
            'social_links'  => $this->social_links ?? null,
        ];
    }
}
