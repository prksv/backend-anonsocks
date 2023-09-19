<?php

namespace App\Models;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $guarded;

    protected $casts = [
        'proxy_provider' => ProxyProvider::class,
        'proxy_type' => ProxyType::class
    ];

    public function rentalTerms(): HasMany
    {
        return $this->hasMany(RentalTerm::class);
    }
}
