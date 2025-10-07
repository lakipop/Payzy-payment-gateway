<?php

namespace PayzyLaravel\PaymentGateway\Providers;

use Illuminate\Support\ServiceProvider;
use PayzyLaravel\PaymentGateway\Services\PayzyPaymentService;

class PayzyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/payzy.php', 'payzy'
        );

        // Bind the Payzy service
        $this->app->singleton(PayzyPaymentService::class, function ($app) {
            return new PayzyPaymentService();
        });

        // Register the Payzy facade
        $this->app->bind('payzy', function ($app) {
            return $app->make(PayzyPaymentService::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/payzy.php' => config_path('payzy.php'),
        ], 'payzy-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'payzy-migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/payzy.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Add any commands here
            ]);
        }

        // Publish views if you create any
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/payzy'),
        ], 'payzy-views');

        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Register event listeners
     */
    protected function registerEventListeners(): void
    {
        // Register any event listeners here
        // Example:
        // Event::listen(PaymentVerified::class, PaymentVerifiedListener::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            PayzyPaymentService::class,
            'payzy',
        ];
    }
}