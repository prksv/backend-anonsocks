<?php

namespace App\Models;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyStatus;
use App\Enums\Proxy\ProxyType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Proxy extends Model
{
    use HasFactory;

    protected $guarded;

    protected $casts = [
        "status" => ProxyStatus::class,
        "type" => ProxyType::class,
        "provider" => ProxyProvider::class,
    ];

    public function scopeActive($query)
    {
        return $query->where("status", ProxyStatus::ACTIVE);
    }

    public function scopeWhereProviderAndType(
        $query,
        ProxyProvider $proxyProvider,
        ProxyType $proxyType
    )
    {
        return $query->where("provider", $proxyProvider)->where("type", $proxyType);
    }

    public function scopeAvailable($query)
    {
        return $query
            ->active()
            ->whereExpired()
            ->orWhereDoesntHave("orders");
    }

    public function scopeFromPriorityPool($query)
    {
        return $query->active()->whereExpired();
    }

    public function orderProxy(): HasMany
    {
        return $this->hasMany(OrderProxy::class);
    }

    public function rentalPeriods(): \LaravelIdea\Helper\App\Models\_IH_ProxyRentalPeriod_QB|HasMany|null
    {
        return $this->orderProxy()
            ?->first()
            ?->rentalPeriods();
    }

    public function getRentalDaysAttribute()
    {
        return $this->rentalPeriods()
            ->get()
            ->last()
            ->rentalTerm
            ->days;
    }

    public function scopeWhereExpired(Builder $query): Builder
    {
        return $query->whereNot(function (Builder $query) {
            $query->whereNotExpired();
        });
    }

    public function scopeWhereNotExpired($query)
    {
        return $query->whereHas("orderProxy", function ($query) {
            $query->whereHas("rentalPeriods", function ($query) {
                $query->where('expires_at', '>=', Carbon::now());
            });
        });
    }

    public function scopeWhereUser($query, User $user)
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
        if (is_null($this->expires_at)) {
            return false;
        }
        return $this->expires_at < Carbon::now();
    }

    public function isTakenBy(User $user): bool
    {
        $active_order = $this->getActiveOrder();

        if (!$active_order) return false;

        return $this->getActiveOrder()->user_id === $user->id;
    }

    public function isTaken(User $user = null): bool
    {
        if (is_null($this->expires_at)) {
            return false;
        }
        return $this->expires_at > Carbon::now();
    }

    protected function expiresAt(): Attribute
    {
        return Attribute::make(function () {
            return $this->orderProxy()->where(function (Builder $query) {
                $query->whereHas('rentalPeriods', function ($query) {
                    $query->where('expires_at', '>=', Carbon::now());
                });
            })
                ?->first()
                ?->rentalPeriods()
                ->first()->expires_at;
        });
    }

    public function getActiveOrder(): Order|null
    {
        return $this->orderProxy()
            ->whereHas('rentalPeriods', function (Builder $query) {
                $query->where('expires_at', '>=', Carbon::now())
                    ->latest('expires_at');
            })?->first()->order;
    }
}
