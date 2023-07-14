<?php

namespace App\Models;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyStatus;
use App\Enums\Proxy\ProxyType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proxy extends Model
{
    use HasFactory;

    protected $guarded;

    protected $casts = [
        "status" => ProxyStatus::class,
        "type" => ProxyType::class,
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where("status", ProxyStatus::ACTIVE);
    }

    public function scopeWhereProviderAndType(
        Builder $query,
        ProxyProvider $proxyProvider,
        ProxyType $proxyType
    ): Builder {
        return $query->where("provider", $proxyProvider)->where("type", $proxyType);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereExpired()
            ->orWhereDoesntHave("orders");
    }

    public function scopeFromPriorityPool(Builder $query): Builder
    {
        return $query->active()->whereExpired();
    }

    public function scopeWhereExpired(Builder $query): Builder
    {
        return $query->whereRelation("orders", "expires_at", "<=", Carbon::now());
    }

    public function scopeWhereNotExpired(Builder $query): Builder
    {
        return $query->whereRelation("orders", "expires_at", ">", Carbon::now());
    }

    public function scopeWhereUser(Builder $query, User $user): Builder
    {
        return $query->whereHas("orders", function ($query) use ($user) {
            $query->where("user_id", $user->id);
        });
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at < Carbon::now();
    }

    protected function expiresAt(): Attribute
    {
        return Attribute::make(function () {
            return OrderProxy::latest("proxy_id", $this->id)->first()->expires_at;
        });
    }
}
