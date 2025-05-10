<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tapp\FilamentInvite\Tables\InviteAction;
use TomatoPHP\FilamentUsers\Resources\UserResource\Table\UserActions;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        UserActions::register([
            InviteAction::make(),
        ]);
    }
}
