<?php

namespace App\Models;

use App\Enums\Order\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderProxy(): HasMany
    {
        return $this->hasMany(OrderProxy::class);
    }

    public function proxies(): BelongsToMany
    {
        return $this->belongsToMany(Proxy::class)
            ->withTimestamps('created_at', 'updated_at')
            ->withPivot('id')
            ->using(OrderProxy::class);
    }

    public function rentalTerm(): BelongsTo
    {
        return $this->belongsTo(RentalTerm::class);
    }

    public function getAmountAttribute(): float|int
    {
        return $this->proxy_count * $this->rentalTerm->price;
    }

    public function isDone(): bool
    {
        return $this->status === OrderStatus::DONE;
    }

    public function isRefunded(): bool
    {
        return $this->status === OrderStatus::REFUNDED;
    }
}
