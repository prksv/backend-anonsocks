<?php

namespace App\Models;

use App\Enums\Deposit\DepositStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;

    protected $guarded;

    protected $casts = [
        "status" => DepositStatus::class,
    ];

    protected $attributes = [
        "status" => DepositStatus::PROCESSING,
    ];
}
