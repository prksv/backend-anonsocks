<?php

namespace App\Enums\Deposit;

enum DepositStatus: int
{
    case PROCESSING = 0;
    case COMPLETED = 1;
    case CANCELLED = 2;
    case FAILED = 3;
}
