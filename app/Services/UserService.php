<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserService
{
    const DEFAULT_BALANCE = 0;

    private function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (User::findByToken($token) !== null);
        return $token;
    }

    public function register(): string
    {
        $token = $this->generateToken();
        $hashed_token = hash("sha256", $token);

        $user = User::create([
            "authorization_token" => $hashed_token,
            "balance" => self::DEFAULT_BALANCE,
        ]);
        return $token;
    }

    /**
     * @throws \Throwable
     */
    public function login(string $token): array
    {
        $user = User::findByToken($token);

        throw_if(!$user, ModelNotFoundException::class);

        $token = $user->createToken("access");

        return $user->toArray() + ["access_token" => $token->plainTextToken];
    }
}
