<?php

namespace Functional\Tickets\Providers;

use Functional\Tickets\Access\Controls\TicketControl;
use Functional\Tickets\Database\Seeders\CommentsSeeder;
use Functional\Tickets\Database\Seeders\TicketsAccessSeeder;
use Functional\Tickets\Database\Seeders\TicketsSeeder;
use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Listeners\NotifyAssignedTechnician;
use Illuminate\Support\Facades\Event;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class TicketsServiceProvider extends LayerServiceProvider
{
    public function register(): void
    {
        /**
         * Registered by hand rather than discovered: Access::discoverControls()
         * derives the class name from the file path relative to base_path(),
         * which only resolves for app/Access/Controls — a layer path would
         * yield "Functional\tickets\src\..." and be silently skipped.
         */
        (new Access)->addControl(new TicketControl);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([TicketsAccessSeeder::class], priority: -10);
            $this->loadSeeders([TicketsSeeder::class], priority: 0);
            $this->loadSeeders([CommentsSeeder::class], priority: 10);
        }

        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'tickets');

        /**
         * Registered explicitly rather than relying on Laravel's event
         * discovery: discovery only scans the application's own Listeners
         * directory, never a layer's.
         */
        Event::listen(TicketAssigned::class, NotifyAssignedTechnician::class);

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            api: __DIR__.'/../../routes/api.php',
            commands: __DIR__.'/../../routes/console.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }
}
