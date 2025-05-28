<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        Paginator::useBootstrapFive();

        // Ensure proper UTF-8 encoding for database connections
        \Illuminate\Support\Facades\DB::statement('SET NAMES utf8mb4');
        \Illuminate\Support\Facades\DB::statement('SET CHARACTER SET utf8mb4');
        \Illuminate\Support\Facades\DB::statement('SET character_set_connection=utf8mb4');

        // Clear cache to ensure fresh data
        \Illuminate\Support\Facades\Cache::flush();
    }
}
