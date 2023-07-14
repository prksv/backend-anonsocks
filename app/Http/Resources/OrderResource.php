<?php

namespace App\Http\Resources;

use App\Enums\Order\OrderStatus;
use App\Enums\Proxy\ProxyType;
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
        $extra = [];

        if ($this->status === OrderStatus::DONE && count($this->proxies) > 0) {
            $extra = [
                "country" => $this->country,
                "ip_type" => $this->type->getLabel(),
                "proxy_count" => $this->proxies->count(),
                "proxies" => ProxyResource::collection($this->proxies),
            ];
        }

        return [
            "id" => $this->id,
            "status" => $this->status->name,
            "amount" => $this->amount,
            "created_at" => $this->created_at,
        ] + $extra;
    }
}
