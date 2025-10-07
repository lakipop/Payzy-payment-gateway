# Payzy Laravel Payment Gateway - Implementation Guide

This guide provides step-by-step instructions for implementing the Payzy Laravel Payment Gateway package in your Laravel application.

## Table of Contents

1. [Quick Start](#quick-start)
2. [Installation Process](#installation-process)
3. [Basic Implementation](#basic-implementation)
4. [Advanced Features](#advanced-features)
5. [Common Use Cases](#common-use-cases)
6. [Best Practices](#best-practices)
7. [Troubleshooting](#troubleshooting)

## Quick Start

### 1. Install the Package

```bash
composer require payzy-laravel/payment-gateway
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag="payzy-config"
php artisan vendor:publish --tag="payzy-migrations"
php artisan migrate
```

### 3. Configure Environment

Add to your `.env` file:

```env
PAYZY_TEST_MODE=true
PAYZY_SHOP_ID=your_shop_id
PAYZY_SECRET_KEY=your_secret_key
PAYZY_RESPONSE_URL=https://your-domain.com/payzy/callback
```

### 4. Basic Usage

```php
use PayzyLaravel\PaymentGateway\Services\PayzyPaymentService;

class CheckoutController extends Controller
{
    public function processPayment(PayzyPaymentService $payzyService)
    {
        $result = $payzyService->initiatePayment([
            'order_id' => 'ORDER_123',
            'amount' => 1000.00,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+94771234567',
            'address' => '123 Main Street',
            'city' => 'Colombo',
            'country' => 'Sri Lanka',
        ]);
        
        if ($result['success']) {
            return redirect($result['redirect_url']);
        }
        
        return back()->withErrors(['payment' => $result['message']]);
    }
}
```

## Installation Process

### Step 1: System Requirements

Ensure your system meets the requirements:

```bash
# Check PHP version
php --version  # Should be 8.1+

# Check Laravel version
php artisan --version  # Should be 10.0+

# Check MySQL version
mysql --version  # Should be 8.0+
```

### Step 2: Install via Composer

```bash
# Install the package
composer require payzy-laravel/payment-gateway

# Verify installation
composer show payzy-laravel/payment-gateway
```

### Step 3: Publish Assets

```bash
# Publish configuration file
php artisan vendor:publish --tag="payzy-config"

# Publish migrations
php artisan vendor:publish --tag="payzy-migrations"

# Optional: Publish all assets
php artisan vendor:publish --provider="PayzyLaravel\PaymentGateway\Providers\PayzyServiceProvider"
```

### Step 4: Run Migrations

```bash
# Run the migrations
php artisan migrate

# Verify tables were created
php artisan tinker
>>> Schema::hasTable('payzy_payments');
>>> Schema::hasTable('payzy_payment_items');
```

### Step 5: Configure Service Provider (If Needed)

For Laravel < 5.5, add to `config/app.php`:

```php
'providers' => [
    // ...
    PayzyLaravel\PaymentGateway\Providers\PayzyServiceProvider::class,
],

'aliases' => [
    // ...
    'Payzy' => PayzyLaravel\PaymentGateway\Facades\Payzy::class,
],
```

## Basic Implementation

### 1. Payment Controller

Create a payment controller:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayzyLaravel\PaymentGateway\Services\PayzyPaymentService;
use PayzyLaravel\PaymentGateway\Models\PayzyPayment;

class PaymentController extends Controller
{
    protected $payzyService;
    
    public function __construct(PayzyPaymentService $payzyService)
    {
        $this->payzyService = $payzyService;
    }
    
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|string|unique:payzy_payments,order_id',
            'amount' => 'required|numeric|min:0.01',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
        ]);
        
        // Add user ID if authenticated
        if (auth()->check()) {
            $validated['user_id'] = auth()->id();
        }
        
        $result = $this->payzyService->initiatePayment($validated);
        
        if ($result['success']) {
            session()->flash('success', 'Payment initiated successfully');
            return redirect($result['redirect_url']);
        }
        
        return back()->withErrors(['payment' => $result['message']])->withInput();
    }
    
    public function success(Request $request)
    {
        $orderId = $request->query('order_id');
        $payment = PayzyPayment::where('order_id', $orderId)->first();
        
        if (!$payment) {
            return redirect()->route('home')->withErrors(['payment' => 'Payment not found']);
        }
        
        return view('payment.success', compact('payment'));
    }
    
    public function failed(Request $request)
    {
        $orderId = $request->query('order_id');
        $payment = PayzyPayment::where('order_id', $orderId)->first();
        
        return view('payment.failed', compact('payment'));
    }
    
    public function status($paymentId)
    {
        $payment = PayzyPayment::find($paymentId);
        
        if (!$payment) {
            abort(404);
        }
        
        return response()->json([
            'success' => true,
            'payment' => [
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->payment_status,
                'paid_at' => $payment->paid_at,
            ]
        ]);
    }
}
```

### 2. Routes

Add routes to `routes/web.php`:

```php
use App\Http\Controllers\PaymentController;

Route::prefix('payment')->name('payment.')->group(function () {
    Route::post('/checkout', [PaymentController::class, 'checkout'])->name('checkout');
    Route::get('/success', [PaymentController::class, 'success'])->name('success');
    Route::get('/failed', [PaymentController::class, 'failed'])->name('failed');
    Route::get('/status/{payment}', [PaymentController::class, 'status'])->name('status');
});
```

### 3. Views

Create payment form view `resources/views/payment/form.blade.php`:

```html
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Payment Information</div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('payment.checkout') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="first_name">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="{{ old('first_name') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="{{ old('last_name') }}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="{{ old('email') }}" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="{{ old('phone') }}" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <textarea class="form-control" id="address" name="address" required>{{ old('address') }}</textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="city">City</label>
                                    <input type="text" class="form-control" id="city" name="city" 
                                           value="{{ old('city') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="country">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" 
                                           value="{{ old('country', 'Sri Lanka') }}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="order_id">Order ID</label>
                                    <input type="text" class="form-control" id="order_id" name="order_id" 
                                           value="{{ old('order_id', 'ORDER_' . time()) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="amount">Amount (LKR)</label>
                                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                           value="{{ old('amount') }}" required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Proceed to Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

Create success view `resources/views/payment/success.blade.php`:

```html
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-success">Payment Successful</div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h4>Thank you for your payment!</h4>
                        <p>Your payment has been processed successfully.</p>
                    </div>
                    
                    @if($payment)
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Order ID:</strong> {{ $payment->order_id }}
                        </div>
                        <div class="col-md-6">
                            <strong>Transaction ID:</strong> {{ $payment->transaction_id }}
                        </div>
                        <div class="col-md-6">
                            <strong>Amount:</strong> {{ $payment->currency }} {{ number_format($payment->amount, 2) }}
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong> {{ $payment->payment_status }}
                        </div>
                        <div class="col-md-6">
                            <strong>Paid At:</strong> {{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : 'N/A' }}
                        </div>
                    </div>
                    @endif
                    
                    <div class="mt-3">
                        <a href="{{ route('home') }}" class="btn btn-primary">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

## Advanced Features

### 1. Payment Items Tracking

```php
use PayzyLaravel\PaymentGateway\Models\PayzyPaymentItem;

// After payment is created
$payment = PayzyPayment::where('order_id', $orderId)->first();

// Add payment items
$payment->paymentItems()->createMany([
    [
        'product_id' => 1,
        'product_name' => 'T-Shirt',
        'product_sku' => 'TSH-001',
        'quantity' => 2,
        'unit_price' => 500.00,
        'total_price' => 1000.00,
    ],
    [
        'product_id' => 2,
        'product_name' => 'Jeans',
        'product_sku' => 'JNS-001',
        'quantity' => 1,
        'unit_price' => 2000.00,
        'total_price' => 2000.00,
    ],
]);
```

### 2. Event Listeners

Create event listeners for payment events:

```php
// app/Listeners/PaymentVerifiedListener.php
<?php

namespace App\Listeners;

use PayzyLaravel\PaymentGateway\Events\PaymentVerified;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentConfirmation;

class PaymentVerifiedListener
{
    public function handle(PaymentVerified $event)
    {
        $payment = $event->payment;
        
        // Send confirmation email
        if ($payment->user && $payment->user->email) {
            Mail::to($payment->user->email)->send(new PaymentConfirmation($payment));
        }
        
        // Update order status
        if ($payment->order) {
            $payment->order->update(['status' => 'paid']);
        }
        
        // Log successful payment
        logger('Payment verified successfully', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => $payment->amount,
        ]);
    }
}
```

Register the listener in `app/Providers/EventServiceProvider.php`:

```php
use PayzyLaravel\PaymentGateway\Events\PaymentVerified;
use App\Listeners\PaymentVerifiedListener;

protected $listen = [
    PaymentVerified::class => [
        PaymentVerifiedListener::class,
    ],
];
```

### 3. Webhook Handling

If you need custom webhook handling:

```php
// app/Http/Controllers/PayzyWebhookController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayzyLaravel\PaymentGateway\Services\PayzyPaymentService;

class PayzyWebhookController extends Controller
{
    public function handle(Request $request, PayzyPaymentService $payzyService)
    {
        // Verify webhook signature
        $signature = $request->header('X-Payzy-Signature');
        $payload = $request->getContent();
        
        if (!$this->verifyWebhookSignature($payload, $signature)) {
            abort(403, 'Invalid signature');
        }
        
        $data = $request->json()->all();
        
        // Process webhook data
        if ($data['event'] === 'payment.completed') {
            $orderId = $data['order_id'];
            $responseCode = $data['response_code'];
            $payzySignature = $data['signature'];
            
            $result = $payzyService->verifyPayment($orderId, $responseCode, $payzySignature);
            
            if ($result['success']) {
                // Handle successful payment
                return response()->json(['status' => 'success']);
            }
        }
        
        return response()->json(['status' => 'ignored']);
    }
    
    private function verifyWebhookSignature($payload, $signature)
    {
        $expectedSignature = hash_hmac('sha256', $payload, config('payzy.webhook_secret'));
        return hash_equals($expectedSignature, $signature);
    }
}
```

## Common Use Cases

### 1. E-commerce Checkout

```php
class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $cart = session('cart', []);
        $total = collect($cart)->sum('total');
        
        $order = Order::create([
            'user_id' => auth()->id(),
            'total' => $total,
            'status' => 'pending',
        ]);
        
        foreach ($cart as $item) {
            $order->items()->create($item);
        }
        
        $paymentData = [
            'order_id' => $order->id,
            'amount' => $total,
            'first_name' => auth()->user()->first_name,
            'last_name' => auth()->user()->last_name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone,
            'address' => auth()->user()->address,
            'city' => auth()->user()->city,
            'country' => 'Sri Lanka',
        ];
        
        $payzyService = app(PayzyPaymentService::class);
        $result = $payzyService->initiatePayment($paymentData);
        
        if ($result['success']) {
            session()->forget('cart');
            return redirect($result['redirect_url']);
        }
        
        return back()->withErrors(['payment' => $result['message']]);
    }
}
```

### 2. Subscription Payments

```php
class SubscriptionController extends Controller
{
    public function subscribe(Request $request, Plan $plan)
    {
        $subscription = auth()->user()->subscriptions()->create([
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'status' => 'pending',
        ]);
        
        $paymentData = [
            'order_id' => 'SUB_' . $subscription->id,
            'amount' => $plan->price,
            'first_name' => auth()->user()->first_name,
            'last_name' => auth()->user()->last_name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone,
            'address' => auth()->user()->address,
            'city' => auth()->user()->city,
            'country' => 'Sri Lanka',
        ];
        
        $payzyService = app(PayzyPaymentService::class);
        $result = $payzyService->initiatePayment($paymentData);
        
        if ($result['success']) {
            return redirect($result['redirect_url']);
        }
        
        return back()->withErrors(['payment' => $result['message']]);
    }
}
```

### 3. Donation System

```php
class DonationController extends Controller
{
    public function donate(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
            'donor_name' => 'required|string|max:255',
            'donor_email' => 'required|email',
            'donor_phone' => 'nullable|string',
            'message' => 'nullable|string|max:500',
        ]);
        
        $donation = Donation::create($validated);
        
        $paymentData = [
            'order_id' => 'DON_' . $donation->id,
            'amount' => $validated['amount'],
            'first_name' => $validated['donor_name'],
            'last_name' => '',
            'email' => $validated['donor_email'],
            'phone' => $validated['donor_phone'] ?? '',
            'address' => 'N/A',
            'city' => 'N/A',
            'country' => 'Sri Lanka',
        ];
        
        $payzyService = app(PayzyPaymentService::class);
        $result = $payzyService->initiatePayment($paymentData);
        
        if ($result['success']) {
            return redirect($result['redirect_url']);
        }
        
        return back()->withErrors(['payment' => $result['message']]);
    }
}
```

## Best Practices

### 1. Security

```php
// Always validate input data
$validated = $request->validate([
    'amount' => 'required|numeric|min:0.01|max:1000000',
    'email' => 'required|email:rfc,dns',
    'phone' => 'required|regex:/^\+94[0-9]{9}$/',
]);

// Use database transactions for critical operations
DB::transaction(function () use ($paymentData) {
    $payment = PayzyPayment::create($paymentData);
    $this->updateInventory($paymentData['items']);
    $this->createOrder($paymentData);
});

// Log all payment activities
Log::info('Payment initiated', [
    'order_id' => $orderData['order_id'],
    'amount' => $orderData['amount'],
    'user_id' => auth()->id(),
]);
```

### 2. Error Handling

```php
try {
    $result = $payzyService->initiatePayment($orderData);
    
    if (!$result['success']) {
        Log::warning('Payment initiation failed', [
            'order_id' => $orderData['order_id'],
            'error' => $result['message'],
        ]);
        
        return back()->withErrors(['payment' => $result['message']]);
    }
    
    return redirect($result['redirect_url']);
    
} catch (\Exception $e) {
    Log::error('Payment processing error', [
        'order_id' => $orderData['order_id'],
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    return back()->withErrors(['payment' => 'Payment processing failed. Please try again.']);
}
```

### 3. Performance

```php
// Use queues for heavy processing
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessPaymentConfirmation implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;
    
    protected $payment;
    
    public function __construct(PayzyPayment $payment)
    {
        $this->payment = $payment;
    }
    
    public function handle()
    {
        // Send confirmation email
        Mail::to($this->payment->user->email)->send(new PaymentConfirmation($this->payment));
        
        // Update external systems
        $this->updateInventory();
        $this->updateAccounting();
        $this->updateReporting();
    }
}

// Dispatch the job
dispatch(new ProcessPaymentConfirmation($payment));
```

### 4. Testing

```php
// Use factories for test data
class PayzyPaymentFactory extends Factory
{
    protected $model = PayzyPayment::class;
    
    public function definition()
    {
        return [
            'order_id' => 'TEST_' . $this->faker->unique()->randomNumber(8),
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'LKR',
            'payment_status' => 'Pending',
            'transaction_id' => 'payzy_' . $this->faker->uuid(),
        ];
    }
    
    public function completed()
    {
        return $this->state([
            'payment_status' => 'Completed',
            'paid_at' => now(),
        ]);
    }
}

// Test payment processing
public function test_payment_can_be_initiated()
{
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $orderData = [
        'order_id' => 'TEST_' . time(),
        'amount' => 100.00,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '+94771234567',
        'address' => '123 Main Street',
        'city' => 'Colombo',
        'country' => 'Sri Lanka',
    ];
    
    $response = $this->post(route('payment.checkout'), $orderData);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('payzy_payments', [
        'order_id' => $orderData['order_id'],
        'user_id' => $user->id,
        'payment_status' => 'Pending',
    ]);
}
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Callback URL Not Working

**Problem:** Payment callbacks return 404 or don't update payment status.

**Debug Steps:**
```bash
# Check if routes are registered
php artisan route:list | grep payzy

# Test callback URL manually
curl -X GET "https://your-domain.com/payzy/callback?x_order_id=TEST_123&response_code=00&signature=test"

# Check logs
tail -f storage/logs/laravel.log
```

**Solution:**
- Ensure callback URL is publicly accessible
- Verify SSL certificate is valid
- Check middleware configuration
- Confirm route registration

#### 2. Signature Verification Fails

**Problem:** All payments fail signature verification.

**Debug Steps:**
```php
// Add debug logging in PayzyPaymentService
Log::debug('Signature verification data', [
    'signature_string' => $signatureString,
    'calculated_signature' => $calculatedSignature,
    'received_signature' => $receivedSignature,
]);
```

**Solution:**
- Verify secret key is correct
- Check field ordering
- Ensure test/live mode consistency
- Verify URL encoding

#### 3. Database Connection Issues

**Problem:** Migrations fail or payment data not saved.

**Debug Steps:**
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check migration status
php artisan migrate:status

# Test table creation
php artisan tinker
>>> Schema::hasTable('payzy_payments');
```

**Solution:**
- Verify database credentials
- Check MySQL version compatibility
- Ensure JSON column support
- Run migrations with --force if needed

This implementation guide should help you get started with the Payzy Laravel Payment Gateway package. For more detailed information, refer to the main README.md file.