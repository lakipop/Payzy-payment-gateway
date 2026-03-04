# Payzy Laravel Payment Gateway - Integration Guide

A simple, step-by-step guide to integrate the **Payzy** payment gateway into your Laravel project. No package installation needed — just copy the files and configure.

> Based on a working production implementation.

## What You Get

- ✅ Complete Payzy API integration (initiate payment + verify callback)
- ✅ Secure HMAC-SHA256 signature generation & verification
- ✅ Database table for tracking payments
- ✅ Ready-to-use Service, Model, and Controller

## Requirements

- PHP 8.1+
- Laravel 10.0+
- A Payzy merchant account ([payzy.lk](https://payzy.lk))

---

## Quick Setup (5 Steps)

### Step 1: Add Environment Variables

Add these to your `.env` file:

```env
PAYZY_TEST_MODE=true
PAYZY_SHOP_ID=your_shop_id_here
PAYZY_SECRET_KEY=your_secret_key_here  
PAYZY_API_URL=https://api.payzy.lk/checkout/custom-checkout
PAYZY_RESPONSE_URL=https://your-domain.com/payzy/callback
```

> **Get credentials:** Sign up at [payzy.lk](https://payzy.lk), go to your merchant dashboard, and copy your Shop ID and Secret Key.

---

### Step 2: Copy the Files

Copy these files from the `files/` directory into your Laravel project:

| From (this repo) | To (your project) |
|---|---|
| `files/config/payzy.php` | `config/payzy.php` |
| `files/app/Services/PayzyPaymentService.php` | `app/Services/PayzyPaymentService.php` |
| `files/app/Models/PayzyPayment.php` | `app/Models/PayzyPayment.php` |
| `files/app/Http/Controllers/PayzyPaymentController.php` | `app/Http/Controllers/PayzyPaymentController.php` |
| `files/database/migrations/create_payzy_payments_table.php` | `database/migrations/2024_01_01_000001_create_payzy_payments_table.php` |

> **Note:** Rename the migration file with today's date if you prefer, e.g. `2024_06_15_000001_create_payzy_payments_table.php`

---

### Step 3: Run the Migration

```bash
php artisan migrate
```

This creates the `payzy_payments` table.

---

### Step 4: Add Routes

Add these routes to your Laravel routes file:

**For API routes** (`routes/api.php`):

```php
use App\Http\Controllers\PayzyPaymentController;

// Initiate a payment (your frontend calls this)
Route::post('/payzy/process', [PayzyPaymentController::class, 'processPayment']);

// Payzy callback (Payzy redirects here after payment)
// IMPORTANT: This URL must match your PAYZY_RESPONSE_URL env variable
Route::get('/payzy/callback', [PayzyPaymentController::class, 'handleCallback'])
    ->name('payzy.callback');
```

**OR for Web routes** (`routes/web.php`):

```php
use App\Http\Controllers\PayzyPaymentController;

Route::post('/payzy/process', [PayzyPaymentController::class, 'processPayment']);

// Callback must be excluded from CSRF verification (see below)
Route::get('/payzy/callback', [PayzyPaymentController::class, 'handleCallback'])
    ->name('payzy.callback');
```

> **If using web routes:** Add the callback URL to CSRF exceptions in `app/Http/Middleware/VerifyCsrfToken.php`:
> ```php
> protected $except = [
>     'payzy/callback',
> ];
> ```

---

### Step 5: Test It!

Set `PAYZY_TEST_MODE=true` in your `.env`, then send a POST request:

```bash
curl -X POST http://your-app.test/api/payzy/process \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "TEST-001",
    "amount": 100.00,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "0771234567",
    "address": "123 Main Street",
    "city": "Colombo",
    "state": "Western",
    "country": "Sri Lanka",
    "zip": "10100"
  }'
```

**Expected response:**
```json
{
  "success": true,
  "message": "Payment initiated successfully",
  "redirect_url": "https://payzy.lk/checkout/...",
  "payment_id": 1,
  "transaction_id": "PAYZY-abc123-1234567890"
}
```

Redirect the user to the `redirect_url` — they'll see the Payzy checkout page.

---

## How It Works

### Payment Flow

```
1. Your App → POST /payzy/process (with order + customer data)
2. PayzyPaymentService prepares the data + HMAC signature
3. PayzyPaymentService sends request to Payzy API
4. Payzy API returns a checkout URL
5. Your App redirects the customer to the checkout URL
6. Customer completes payment on Payzy's page
7. Payzy redirects customer to your PAYZY_RESPONSE_URL (callback)
8. PayzyPaymentController::handleCallback() verifies the signature
9. Payment status updated in database (Completed or Failed)
10. Customer sees your success/failure page
```

### Files Overview

| File | Purpose |
|---|---|
| `config/payzy.php` | Configuration — Shop ID, Secret Key, API URL, etc. |
| `PayzyPaymentService.php` | Core logic — prepares data, generates signatures, calls API, verifies callbacks |
| `PayzyPayment.php` | Eloquent model — tracks payments in the database |
| `PayzyPaymentController.php` | HTTP endpoints — processPayment() and handleCallback() |
| `Migration` | Creates the `payzy_payments` database table |

---

## Customization

### Custom Success/Failure Redirects

Edit `PayzyPaymentController.php` → `handleCallback()` method. Look for the `TODO` comments:

```php
if ($result['success']) {
    // ✅ Customize this:
    return redirect()->route('your.success.route', ['order_id' => $orderId]);
} else {
    // ❌ Customize this:
    return redirect()->route('your.failure.route');
}
```

### Using in Your Own Controller

You don't have to use the provided controller. Inject the service directly:

```php
use App\Services\PayzyPaymentService;

class YourCheckoutController extends Controller
{
    public function checkout(Request $request, PayzyPaymentService $payzy)
    {
        $result = $payzy->initiatePayment([
            'order_id' => 'YOUR-ORDER-123',
            'amount' => 2500.00,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '0771234567',
            'address' => '123 Main St',
            'city' => 'Colombo',
            'state' => 'Western',
            'country' => 'Sri Lanka',
            'zip' => '10100',
        ]);

        if ($result['success']) {
            return redirect($result['redirect_url']);
        }

        return back()->with('error', $result['message']);
    }
}
```

### Querying Payments

```php
use App\Models\PayzyPayment;

// Get all completed payments
$completed = PayzyPayment::completed()->get();

// Get payments for a specific user
$userPayments = PayzyPayment::byUser($userId)->get();

// Check payment status
$payment = PayzyPayment::find(1);
if ($payment->isCompleted()) {
    // Payment was successful
}
```

---

## Going Live

1. Set `PAYZY_TEST_MODE=false` in your `.env`
2. Use your **production** Shop ID and Secret Key
3. Make sure `PAYZY_RESPONSE_URL` is your **live domain** (e.g., `https://yoursite.com/api/payzy/callback`)
4. Ensure the callback URL is **publicly accessible** (not behind auth middleware)

---

## Important Notes

### ⚠️ The x_version Quirk

Payzy's signature generation has a quirk where the `x_version` field is concatenated **without** an equals sign in the initiation request signature:

```
...x_freight=0,x_platform=custom,x_version1.0,signed_field_names=...
```

Notice: `x_version1.0` (no `=`), while all other fields use `x_version=1.0`.

**This is intentional and matches Payzy's JavaScript SDK.** The `PayzyPaymentService` handles this automatically — do NOT "fix" it or signatures will break.

### ⚠️ Response URL Must Be Public

The `PAYZY_RESPONSE_URL` must be a publicly accessible URL that Payzy can redirect to. If you're developing locally, use a tool like [ngrok](https://ngrok.com) to create a public tunnel.

### ⚠️ Signature Spaces

URL encoding can convert `+` characters in the signature to spaces. The service handles this automatically by replacing spaces back to `+` before verification.

---

## Troubleshooting

| Problem | Solution |
|---|---|
| `Payment initiation failed` | Check your Shop ID and Secret Key in `.env` |
| `Signature mismatch on callback` | Ensure `PAYZY_RESPONSE_URL` exactly matches what's configured in Payzy dashboard |
| `404 on callback` | Make sure the callback route exists and isn't behind auth middleware |
| `CSRF token mismatch on callback` | Add `payzy/callback` to CSRF exceptions (see Step 4) |
| `Payment always pending` | Make sure your callback URL is publicly accessible |
