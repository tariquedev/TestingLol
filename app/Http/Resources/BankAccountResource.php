<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class BankAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'bank_name'                 => $this->bank_name,
            'account_holder_name'       => $this->account_holder_name,
            'account_number'            => $this->account_number,
            'routing_number'            => $this->routing_number,
            'account_type'              => $this->account_type,
            'currency'                  => Str::upper($this->currency),
            'bank_type'                 => Str::upper($this->bank_type),
            'is_default_payment_method' => $this->is_default_payment_method,
            'address'                   => $this->address,
        ];
    }
}
