<?php

namespace Functional\Tickets\Providers;

use Functional\Tickets\Database\Seeders\CommentsSeeder;
use Functional\Tickets\Database\Seeders\TicketsSeeder;
use Xefi\LaravelOSDD\LayerServiceProvider;

class TicketsServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([TicketsSeeder::class], priority: 0);
            $this->loadSeeders([CommentsSeeder::class], priority: 10);
        }

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            api: __DIR__.'/../../routes/api.php',
            commands: __DIR__.'/../../routes/console.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }

    public function register(): void
    {
        //
    }
}
