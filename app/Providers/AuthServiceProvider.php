<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Guards\TelegramGuard;
use App\Models\Deposit;
use App\Policies\DepositPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Deposit::class => DepositPolicy::class,

    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //        Auth::extend("bot", function ($app, $name, array $config) {
        //            return new TelegramGuard(
        //                Auth::createUserProvider($config["provider"]),
        //                $app->make("request")
        //            );
        //        });
    }
}
