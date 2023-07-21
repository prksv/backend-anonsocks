<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class UserService
{
    const DEFAULT_BALANCE = 0;

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

    private function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (User::findByToken($token) !== null);
        return $token;
    }

    /**
     * @throws \Throwable
     */
    public function login(string $token)
    {
        $user = User::findByToken($token);

        throw_if(!$user, ModelNotFoundException::class);

        $token = $user->createToken("access");

        $user->access_token = $token->plainTextToken;

        return $user;
    }
}
