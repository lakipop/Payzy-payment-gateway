<?php

namespace App\Http\Controllers;

use App\Services\PayzyPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayzyPaymentController extends Controller
{
    protected $payzyService;
    
    public function __construct(PayzyPaymentService $payzyService)
    {
        $this->payzyService = $payzyService;
    }
    
    /**
     * Process a new Payzy payment.
     *
     * Expects a POST request with customer & order details.
     * Returns a JSON response with a redirect_url to the Payzy checkout page.
     *
     * Example request body:
     * {
     *     "order_id": "ORDER-123",
     *     "amount": 1500.00,
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "email": "john@example.com",
     *     "phone": "0771234567",
     *     "address": "123 Main St",
     *     "city": "Colombo",
     *     "state": "Western",
     *     "country": "Sri Lanka",
     *     "zip": "10100"
     * }
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'zip' => 'required|string',
        ]);

        try {
            // Build the order data array
            $orderData = [
                'order_id' => $request->order_id,
                'amount' => number_format($request->amount, 2, '.', ''),
                'currency' => $request->currency ?? 'LKR',
                'user_id' => auth()->id() ?? $request->user_id ?? null,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'zip' => $request->zip,
                'company' => $request->company ?? '',
                'freight' => $request->freight ?? '0',
                // Shipping defaults to billing if not provided
                'ship_to_first_name' => $request->ship_to_first_name ?? $request->first_name,
                'ship_to_last_name' => $request->ship_to_last_name ?? $request->last_name,
                'ship_to_company' => $request->ship_to_company ?? $request->company ?? '',
                'ship_to_address' => $request->ship_to_address ?? $request->address,
                'ship_to_city' => $request->ship_to_city ?? $request->city,
                'ship_to_state' => $request->ship_to_state ?? $request->state,
                'ship_to_country' => $request->ship_to_country ?? $request->country,
                'ship_to_zip' => $request->ship_to_zip ?? $request->zip,
            ];

            $result = $this->payzyService->initiatePayment($orderData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect_url' => $result['redirect_url'],
                    'payment_id' => $result['payment_id'],
                    'transaction_id' => $result['transaction_id'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null,
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Payzy payment processing error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment.',
            ], 500);
        }
    }

    /**
     * Handle the callback from Payzy after the customer completes payment.
     *
     * Payzy redirects the customer to your PAYZY_RESPONSE_URL with query params:
     *   ?x_order_id=ORDER-123&response_code=00&signature=XXXXX
     *
     * response_code '00' = success, anything else = failure.
     *
     * Customize this method to redirect to your own success/failure pages.
     */
    public function handleCallback(Request $request)
    {
        try {
            Log::info('Payzy callback received', [
                'all_params' => $request->all(),
            ]);
            
            $orderId = $request->input('x_order_id');
            $responseCode = $request->input('response_code');
            $signature = $request->input('signature');
            
            if (!$orderId || !$responseCode || !$signature) {
                Log::error('Payzy callback missing required parameters');
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters',
                ], 400);
            }
            
            // Verify the payment signature
            $result = $this->payzyService->verifyPayment($orderId, $responseCode, $signature);
            
            if ($result['success']) {
                // ✅ PAYMENT SUCCESSFUL
                // TODO: Customize this redirect to your success page
                // Examples:
                //   return redirect()->route('payment.success', ['payment_id' => $result['payment']->id]);
                //   return redirect('/checkout/success?order_id=' . $orderId);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'payment_id' => $result['payment']->id,
                ]);
            } else {
                // ❌ PAYMENT FAILED
                // TODO: Customize this redirect to your failure page
                // Examples:
                //   return redirect()->route('payment.failed', ['payment_id' => $result['payment']->id ?? null]);
                //   return redirect('/checkout/failed');
                
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error handling Payzy callback', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment callback',
            ], 500);
        }
    }
}
