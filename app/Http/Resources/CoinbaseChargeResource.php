<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoinbaseChargeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "url" => $this["hosted_url"],
            "amount" => $this["pricing"]["local"]["amount"],
            "currency" => $this["pricing"]["local"]["currency"],
            "internal_id" => $this["code"],
        ];
    }
}
