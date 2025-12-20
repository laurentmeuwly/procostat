<?php

namespace Procorad\Procostat;

use Illuminate\Support\ServiceProvider;
use Procorad\Procostat\Application\RunAnalysis;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\Infrastructure\Audit\NullAuditStore;

final class ProcostatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/procostat.php',
            'procostat'
        );

        // AuditStore (port)
        $this->app->bind(
            AuditStore::class,
            fn () => new NullAuditStore()
        );

        // RunAnalysis (use case)
        $this->app->singleton(RunAnalysis::class, function ($app) {
            return new RunAnalysis(
                thresholdsResolver: new ThresholdsResolver(),
                auditStore: $app->make(AuditStore::class)
            );
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/procostat.php' => config_path('procostat.php'),
        ], 'procostat-config');
    }
}
