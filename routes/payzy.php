<?php

use Illuminate\Support\Facades\Route;
use PayzyLaravel\PaymentGateway\Controllers\PayzyPaymentController;

/*
|--------------------------------------------------------------------------
| Payzy Payment Gateway Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Payzy Payment Gateway package.
| These routes handle payment processing, callbacks, and status checks.
|
*/

// Get route configuration from config
$routeConfig = config('payzy.routes', [
    'prefix' => 'payzy',
    'middleware' => ['web'],
]);

Route::prefix($routeConfig['prefix'])->group(function () use ($routeConfig) {
    
    // Payment processing routes (requires authentication)
    Route::middleware($routeConfig['middleware'])->group(function () {
        
        // Process payment
        Route::post('/process', [PayzyPaymentController::class, 'processPayment'])
            ->name('payzy.process');
        
        // Get payment status
        Route::get('/payment/{paymentId}/status', [PayzyPaymentController::class, 'getPaymentStatus'])
            ->name('payzy.status');
        
        // Get user payments
        Route::get('/payments', [PayzyPaymentController::class, 'getUserPayments'])
            ->name('payzy.payments');
        
        // Cancel payment
        Route::post('/payment/{paymentId}/cancel', [PayzyPaymentController::class, 'cancelPayment'])
            ->name('payzy.cancel');
    });
    
    // Public routes (no authentication required)
    
    // Payment callback from Payzy
    Route::get('/callback', [PayzyPaymentController::class, 'handleCallback'])
        ->name('payzy.callback');
    
    // Webhook endpoint for Payzy notifications
    Route::post('/webhook', [PayzyPaymentController::class, 'handleWebhook'])
        ->name('payzy.webhook');
    
    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'Payzy Payment Gateway',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
        ]);
    })->name('payzy.health');
    
});