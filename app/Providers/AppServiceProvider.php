<?php

namespace App\Providers;

use App\Livewire\Auth\Login;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        /**
         * Livewire resolves no class namespace by default in this project, so
         * class-based components are named explicitly.
         */
        Livewire::component('auth.login', Login::class);
    }
}
