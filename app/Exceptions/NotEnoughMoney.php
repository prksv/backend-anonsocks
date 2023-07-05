<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Nette\Schema\ValidationException;
use RuntimeException;

class NotEnoughMoney extends CustomException
{
    public function __construct()
    {
        parent::__construct('Not enough money', 0, null);
    }
}
