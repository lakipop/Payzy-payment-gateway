<?php

namespace PayzyLaravel\PaymentGateway\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use PayzyLaravel\PaymentGateway\Providers\PayzyServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadLaravelMigrations();
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    protected function getPackageProviders($app): array
    {
        return [
            PayzyServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set test Payzy configuration
        $app['config']->set('payzy.test_mode', true);
        $app['config']->set('payzy.shop_id', 'test_shop_id');
        $app['config']->set('payzy.secret_key', 'test_secret_key');
        $app['config']->set('payzy.api_url', 'https://api.payzy.test/checkout/custom-checkout');
        $app['config']->set('payzy.response_url', 'https://test.com/payzy/callback');
    }
}