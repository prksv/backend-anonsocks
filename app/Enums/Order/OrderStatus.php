<?php

namespace App\Enums\Order;

enum OrderStatus: int
{
    case PROCESSING = 0;
    case DONE = 1;
    case CANCELED = 2;
    case REFUNDED = 3;
}
