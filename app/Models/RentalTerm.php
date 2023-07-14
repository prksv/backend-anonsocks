<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalTerm extends Model
{
    use HasFactory;
    protected $guarded;

    protected $casts = [
        "available" => "bool",
    ];

    const UPDATED_AT = null;
    const CREATED_AT = null;
}
