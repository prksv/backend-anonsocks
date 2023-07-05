<?php

namespace App\Models;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Proxy extends Model
{
    use HasFactory;

    protected $guarded;

    public function scopeWhereProviderAndType(
        Builder $query,
        ProxyProvider $proxyProvider,
        ProxyType $proxyType
    ): Builder
    {
        return $query->where('provider', $proxyProvider)
            ->where('type', $proxyType);
    }

    public function scopeFromPriorityPool(Builder $query): Builder
    {
        return $query->whereRelation('order', 'expires_at', '<', Carbon::now());
    }

    public function order(): BelongsToMany
    {
        return $this->belongsToMany(Order::class);
    }
}
