<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\EventResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupCallProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'duration'      => $this->duration,
            'interval'      => $this->interval,
            'timezone'      => $this->timezone,
            'platform'      => $this->platform,
            'max_attendee'  => $this->max_attendee,
            'events'        => EventResource::collection($this->events),
        ];
    }
}
