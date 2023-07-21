<?php

namespace App\Guards;

use App\Models\ThirdPartyApplication;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Guard;

class CustomSanctumGuard extends Guard
{
    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function __invoke(Request $request)
    {
        $user = parent::__invoke($request);
        $telegram_id = $request->header("Telegram-Id");

        if (!$user) {
            return $user;
        }

        if (get_class($user) == ThirdPartyApplication::class) {
            throw_if(
                !$telegram_id,
                new AuthenticationException("Telegram id is must be provided.")
            );
            $request->merge(["thirdPartyApplication" => $user]);
            return User::findByTelegramId($telegram_id);
        }

        return $user;
    }
}
