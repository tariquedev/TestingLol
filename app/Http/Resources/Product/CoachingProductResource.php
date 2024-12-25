<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\ScheduleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoachingProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'day'  => $this->day,
            'duration'  => $this->duration,
            'quantity'  => $this->quantity,
            'timezone'  => $this->timezone,
            'platform'  => $this->platform,
            'max_attendee'  => $this->max_attendee,
            'interval'  => $this->interval,
            'schedules' => ScheduleResource::collection($this->schedules)->groupBy('day'),
        ];
    }
}
