<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name'          => $this->name,
            'type'          => $this->type,
            'placeholder'   => $this->placeholder,
            'help_text'     => $this->help_text,
            'is_required'   => $this->is_required,
        ];
    }
}
