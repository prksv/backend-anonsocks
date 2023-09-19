<?php

namespace App\Http\Resources;

use App\Enums\Order\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "status" => $this->status->name,
            "amount" => $this->amount,
            "created_at" => $this->created_at,
            "proxy_count" => $this->proxy_count,
            "proxy_type" => $this->category->name,
            "country" => $this->country,
            $this->mergeWhen($this->isDone(), [
                "proxies" => ProxyResource::collection($this->proxies),
            ])
        ];
    }
}
