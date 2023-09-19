<?php

namespace App\Services;

use App\Exceptions\CustomException;
use App\Models\User;
use App\Models\UserTelegram;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UserService
{
    const DEFAULT_BALANCE = 0;

    private function hashToken(string $token): string
    {
        return hash("sha256", $token);
    }

    public function register(): array
    {
        $token = $this->generateToken();

        $user = User::create([
            "authorization_token" => $this->hashToken($token),
            "balance" => self::DEFAULT_BALANCE,
        ]);

        $access_token = $user->createToken("access")->plainTextToken;

        return [
            "authorization_token" => $token,
            "access_token" => $access_token
        ];
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
    public function login(string $token): User
    {
        $user = User::findByTokenOrFail($token);

        $token = $user->createToken("access");

        $user->access_token = $token->plainTextToken;

        return $user;
    }

    /**
     * @throws \Throwable
     */
    public function linkTelegram(User $user, int $telegram_id): User
    {
        $telegramAccounts = UserTelegram::where('telegram_id', $telegram_id)->get();

        throw_if($telegramAccounts->isNotEmpty(), new CustomException('Telegram ID is already in use!'));

        $user->telegramAccounts()->create([
            'telegram_id' => $telegram_id
        ]);

        return $user;
    }

    public function refreshToken(User $user): string
    {
        if ($user->created_at <= Carbon::now()->subMinutes(3)) {
            throw new CustomException('Refresh token is not available');
        }

        $token = $this->generateToken();

        $user->update([
            'authorization_token' => $this->hashToken($token)
        ]);

        return $token;
    }
}
