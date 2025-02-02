<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        // $this->hideSensitiveRequestDetails();

        // $isLocal = $this->app->environment('local');

        // Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
        //     return $isLocal ||
        //         $entry->isReportableException() ||
        //         $entry->isFailedRequest() ||
        //         $entry->isFailedJob() ||
        //         $entry->isScheduledTask() ||
        //         $entry->hasMonitoredTag();
        // });

        // $this->app->register(TelescopeServiceProvider::class);

        // Prevent Telescope from running the UI in production
        if ($this->app->environment('production')) {
            config(['telescope.enabled' => false]);
        }

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal || app()->environment('production')
            ? in_array($entry->type, [
                'exception',
                'failed-job',
                'query',  // Log database queries
                'request', // Log failed requests
                'schedule', // Log scheduled tasks
            ])
                : true;
        });

        // Ensure Telescope logs data in production but only runs UI locally
        if (!$this->app->environment('production')) {
            $this->app->register(TelescopeServiceProvider::class);
        }

 
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
