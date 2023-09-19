<?php

namespace App\Models;

use App\Exceptions\NotEnoughMoney;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRolesAndAbilities;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ["authorization_token", "balance"];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ["authorization_token"];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    public static function findByToken(string $token): User|null
    {
        $hashed_token = hash("sha256", $token);
        return self::where("authorization_token", $hashed_token)->first();
    }

    /**
     * @throws \Throwable
     */
    public static function findByTokenOrFail(string $token): User|null
    {
        $user = self::findByToken($token);

        throw_if(!$user, ModelNotFoundException::class);

        return $user;
    }

    /**
     * @throws \Throwable
     *
     * @var integer $amount Must be a positive.
     */
    public function decrementBalance(int $amount): bool
    {
        throw_if($this->balance < $amount, NotEnoughMoney::class);
        throw_if($amount <= 0, new \Exception("Decrement value must be greater than zero."));

        return $this->decrement("balance", $amount);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function telegramAccounts(): HasMany
    {
        return $this->hasMany(UserTelegram::class);
    }

    public function scopeWhereTelegramId(Builder $query, int $telegram_id)
    {
        return $query
            ->whereHas("telegramAccounts", function (Builder $query) use ($telegram_id) {
                $query->where("telegram_id", $telegram_id);
            });
    }
}
