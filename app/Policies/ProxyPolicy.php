<?php

namespace App\Policies;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Http\Request;

class ProxyPolicy
{
    public function view(User $user, Deposit $deposit): bool
    {
        return $user->id === $deposit->user_id;
    }
}
