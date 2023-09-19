<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrderProxy extends Pivot
{
    protected $guarded = [];
    protected $hidden = [];

    public function proxy(): BelongsTo
    {
        return $this->belongsTo(Proxy::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rentalPeriods(): HasMany
    {
        return $this->hasMany(ProxyRentalPeriod::class, 'order_proxy_id', 'id')->orderByDesc('expires_at');
    }
}
