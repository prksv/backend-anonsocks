<?php

namespace App\Models;

use App\Enums\Order\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    use HasFactory;

    protected $guarded;

    protected $casts = [
        "status" => OrderStatus::class,
    ];

    protected $attributes = [
        "status" => OrderStatus::PROCESSING,
    ];

    public function proxies(): BelongsToMany
    {
        return $this->belongsToMany(Proxy::class)
            ->withPivot("expires_at")
            ->using(OrderProxy::class);
    }

    public function getTypeAttribute()
    {
        return $this->proxies->first()?->type;
    }

    public function getCountryAttribute()
    {
        return $this->proxies->first()?->country;
    }

    public function rentalTerm(): BelongsTo
    {
        return $this->belongsTo(RentalTerm::class);
    }
}
