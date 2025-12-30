<?php

namespace Procorad\Procostat;

use Illuminate\Support\ServiceProvider;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Application\RunAnalysis;
use Procorad\Procostat\Contracts\AnalysisEngine;
use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\Contracts\NormalityAdapter;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;
use Procorad\Procostat\Infrastructure\Audit\NullAuditStore;
use RuntimeException;

final class ProcostatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/procostat.php',
            'procostat'
        );

        // AuditStore (port)
        $this->app->bind(
            AuditStore::class,
            fn () => new NullAuditStore
        );

        // NormalityAdapter MUST be bound by host app
        $this->app->bind(NormalityAdapter::class, function () {
            throw new RuntimeException(
                'No NormalityAdapter bound. Please bind an implementation in your application.'
            );
        });

        $this->app->singleton(AssignedValueResolver::class);
        $this->app->singleton(ThresholdsResolver::class);

        // RunAnalysis (use case)
        $this->app->singleton(AnalysisEngine::class, function ($app) {
            return new RunAnalysis(
                normalityAdapter: $app->make(NormalityAdapter::class),
                auditStore: $app->make(AuditStore::class),
                assignedValueResolver: $app->make(AssignedValueResolver::class),
                thresholdsResolver: $app->make(ThresholdsResolver::class),
                thresholdStandard: config('procostat.threshold_standard')
            );
        });

        $this->app->alias(AnalysisEngine::class, RunAnalysis::class);
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/procostat.php' => config_path('procostat.php'),
        ], 'procostat-config');
    }
}
