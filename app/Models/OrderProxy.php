<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class OrderProxy extends Pivot
{
    protected $hidden;

    protected $casts = [
        "expires_at" => "datetime",
    ];
}
