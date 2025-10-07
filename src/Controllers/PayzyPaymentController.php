<?php

namespace PayzyLaravel\PaymentGateway\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use PayzyLaravel\PaymentGateway\Services\PayzyPaymentService;
use PayzyLaravel\PaymentGateway\Models\PayzyPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PayzyPaymentController extends Controller
{
    protected $payzyService;
    
    public function __construct(PayzyPaymentService $payzyService)
    {
        $this->payzyService = $payzyService;
    }
    
    /**
     * Process Payzy payment
     */
    public function processPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'string|max:3',
            'user_id' => 'nullable|integer',
            'address' => 'required|string',
            'phone' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'zip' => 'required|string',
            'company' => 'nullable|string',
            'freight' => 'nullable|numeric',
            // Shipping details (optional, will default to billing if not provided)
            'ship_to_first_name' => 'nullable|string',
            'ship_to_last_name' => 'nullable|string',
            'ship_to_company' => 'nullable|string',
            'ship_to_address' => 'nullable|string',
            'ship_to_city' => 'nullable|string',
            'ship_to_state' => 'nullable|string',
            'ship_to_country' => 'nullable|string',
            'ship_to_zip' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Prepare order data
            $orderData = $request->only([
                'order_id', 'amount', 'currency', 'user_id', 'address', 'phone',
                'first_name', 'last_name', 'email', 'city', 'state', 'country', 'zip',
                'company', 'freight', 'ship_to_first_name', 'ship_to_last_name',
                'ship_to_company', 'ship_to_address', 'ship_to_city', 'ship_to_state',
                'ship_to_country', 'ship_to_zip'
            ]);

            // Set default currency if not provided
            $orderData['currency'] = $orderData['currency'] ?? 'LKR';

            // Initiate payment
            $result = $this->payzyService->initiatePayment($orderData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect_url' => $result['redirect_url'],
                    'transaction_id' => $result['transaction_id'],
                    'payment_id' => $result['payment_id'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null,
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error processing Payzy payment', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Handle Payzy callback
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $orderId = $request->query('x_order_id');
            $responseCode = $request->query('response_code');
            $signature = $request->query('signature');

            Log::info('Payzy callback received', [
                'order_id' => $orderId,
                'response_code' => $responseCode,
                'signature' => $signature,
                'full_request' => $request->all()
            ]);

            if (!$orderId || !$responseCode || !$signature) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required callback parameters',
                ], 400);
            }

            // Verify payment
            $result = $this->payzyService->verifyPayment($orderId, $responseCode, $signature);

            if ($result['success']) {
                // Fire event if configured
                if (class_exists('\PayzyLaravel\PaymentGateway\Events\PaymentVerified')) {
                    event(new \PayzyLaravel\PaymentGateway\Events\PaymentVerified($result['payment']));
                }

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'payment_status' => $result['payment']->payment_status,
                    'transaction_id' => $result['payment']->transaction_id,
                ]);
            } else {
                // Fire event if configured
                if (class_exists('\PayzyLaravel\PaymentGateway\Events\PaymentFailed')) {
                    event(new \PayzyLaravel\PaymentGateway\Events\PaymentFailed(
                        $result['payment'] ?? null, 
                        $result['message']
                    ));
                }

                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'payment_status' => $result['payment']->payment_status ?? 'Unknown',
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error handling Payzy callback', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the payment callback.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request, $paymentId): JsonResponse
    {
        try {
            $payment = PayzyPayment::findOrFail($paymentId);

            return response()->json([
                'success' => true,
                'payment' => [
                    'id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_status' => $payment->payment_status,
                    'payment_method' => $payment->payment_method,
                    'paid_at' => $payment->paid_at,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found or error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 404);
        }
    }

    /**
     * Get all payments for a user
     */
    public function getUserPayments(Request $request): JsonResponse
    {
        try {
            $userId = $request->user_id ?? auth()->id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required',
                ], 400);
            }

            $payments = PayzyPayment::byUser($userId)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'payments' => $payments,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payments',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Cancel a pending payment
     */
    public function cancelPayment(Request $request, $paymentId): JsonResponse
    {
        try {
            $payment = PayzyPayment::findOrFail($paymentId);

            if (!$payment->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending payments can be cancelled',
                ], 400);
            }

            $payment->update([
                'payment_status' => PayzyPayment::STATUS_CANCELLED,
                'response_data' => array_merge($payment->response_data ?? [], [
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id() ?? 'system',
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully',
                'payment' => $payment,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling payment',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Webhook endpoint for Payzy notifications (alternative to callback)
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            // Log webhook data
            Log::info('Payzy webhook received', $request->all());

            // Process webhook data similar to callback
            $orderId = $request->input('x_order_id');
            $responseCode = $request->input('response_code');
            $signature = $request->input('signature');

            if (!$orderId || !$responseCode || !$signature) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required webhook parameters',
                ], 400);
            }

            // Verify payment
            $result = $this->payzyService->verifyPayment($orderId, $responseCode, $signature);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Error handling Payzy webhook', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }
}