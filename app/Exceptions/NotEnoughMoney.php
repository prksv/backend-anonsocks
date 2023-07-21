<?php

namespace App\Exceptions;

class NotEnoughMoney extends CustomException
{
    public function __construct()
    {
        parent::__construct("Not enough money", 0, null);
    }
}
