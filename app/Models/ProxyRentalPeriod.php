<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProxyRentalPeriod extends Model
{
    protected $guarded = [];

    protected $casts = [
        "expires_at" => "datetime",
    ];

    use HasFactory;

    public function rentalTerm(): BelongsTo
    {
        return $this->belongsTo(RentalTerm::class);
    }

    public function scopeWhereNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>=', Carbon::now());
    }
}
