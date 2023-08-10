<?php

namespace App\Http\Resources;

use App\Enums\Proxy\ProxyStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProxyResource extends JsonResource
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
            $this->mergeWhen($this->expires_at, [
                "days_remains" => Carbon::now()->diff($this->expires_at)->days,
                "expires_at" => $this->expires_at,
            ]),
            "status" => ($this->isExpired() ? ProxyStatus::EXPIRED : $this->status)->name,
            "ip" => $this->ip,
            "port" => $this->port,
            "username" => $this->username,
            "password" => $this->password,
            "country" => $this->country,
        ];
    }
}
