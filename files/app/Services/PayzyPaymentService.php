<?php

namespace App\Services;

use App\Models\PayzyPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayzyPaymentService
{
    protected $config;
    
    public function __construct()
    {
        $this->config = config('payzy');
    }
    
    /**
     * Prepare data for the Payzy payment request.
     *
     * Builds the full payload including HMAC signature.
     *
     * @param array $orderData Must contain: amount, order_id, first_name, last_name,
     *                         email, phone, address, city, state, country, zip.
     *                         Optional: company, ship_to_* fields, freight.
     * @return array The signed payment data ready to POST to the Payzy API.
     */
    public function preparePaymentData(array $orderData): array
    {
        $testMode = $this->config['test_mode'] ? 'on' : 'off';
        
        // Build the core payment data — field order matters for signature!
        $paymentData = [
            'x_test_mode' => $testMode,
            'x_shopid' => $this->config['shop_id'],
            'x_amount' => (string)$orderData['amount'],
            'x_order_id' => $orderData['order_id'],
            'x_response_url' => $this->config['response_url'],
            'x_first_name' => $orderData['first_name'],
            'x_last_name' => $orderData['last_name'],
            'x_company' => $orderData['company'] ?? '',
            'x_address' => $orderData['address'],
            'x_country' => $orderData['country'],
            'x_state' => $orderData['state'],
            'x_city' => $orderData['city'],
            'x_zip' => $orderData['zip'],
            'x_phone' => $orderData['phone'],
            'x_email' => $orderData['email'],
            'x_ship_to_first_name' => $orderData['ship_to_first_name'] ?? $orderData['first_name'],
            'x_ship_to_last_name' => $orderData['ship_to_last_name'] ?? $orderData['last_name'],
            'x_ship_to_company' => $orderData['ship_to_company'] ?? $orderData['company'] ?? '',
            'x_ship_to_address' => $orderData['ship_to_address'] ?? $orderData['address'],
            'x_ship_to_country' => $orderData['ship_to_country'] ?? $orderData['country'],
            'x_ship_to_state' => $orderData['ship_to_state'] ?? $orderData['state'],
            'x_ship_to_city' => $orderData['ship_to_city'] ?? $orderData['city'],
            'x_ship_to_zip' => $orderData['ship_to_zip'] ?? $orderData['zip'],
            'x_freight' => isset($orderData['freight']) && $orderData['freight'] !== '' ? (string)$orderData['freight'] : '0',
            'x_platform' => $this->config['platform'],
            'x_version' => '1.0',
        ];
        
        // These exact field names are included in the signature (order matters!)
        $signedFieldNames = 'x_test_mode,x_shopid,x_amount,x_order_id,x_response_url,x_first_name,x_last_name,x_company,x_address,x_country,x_state,x_city,x_zip,x_phone,x_email,x_ship_to_first_name,x_ship_to_last_name,x_ship_to_company,x_ship_to_address,x_ship_to_country,x_ship_to_state,x_ship_to_city,x_ship_to_zip,x_freight,x_platform,x_version,signed_field_names';
        
        $paymentData['signed_field_names'] = $signedFieldNames;
        
        // Generate the HMAC-SHA256 signature
        $paymentData['signature'] = $this->generateSignature($paymentData);
        
        Log::debug('Payzy payment data prepared', [
            'order_id' => $orderData['order_id'],
            'amount' => $orderData['amount'],
        ]);

        return $paymentData;
    }
    
    /**
     * Generate the HMAC-SHA256 signature for the Payzy request.
     */
    protected function generateSignature(array $data): string
    {
        $list = $this->getSignatureString($data);
        $hash = hash_hmac('sha256', $list, $this->config['secret_key'], true);
        return base64_encode($hash);
    }
    
    /**
     * Build the string that gets signed.
     *
     * IMPORTANT: The x_version field intentionally omits the '=' sign.
     * This matches Payzy's JavaScript SDK behavior and is required for
     * the signature to be valid. Do NOT "fix" this — it will break payments.
     *
     * Format: x_test_mode=on,x_shopid=SHOP123,...,x_version1.0,signed_field_names=...
     *         (notice: x_version has NO equals sign)
     */
    protected function getSignatureString(array $data): string
    {
        $list = '';
        $fieldNames = explode(',', $data['signed_field_names']);
        
        foreach ($fieldNames as $index => $fieldName) {
            if (isset($data[$fieldName])) {
                if ($index > 0) {
                    $list .= ',';
                }
                
                // x_version field intentionally has NO '=' sign — this matches Payzy's JS SDK
                if ($fieldName === 'x_version') {
                    $list .= "x_version" . $data[$fieldName];
                } else {
                    $list .= "$fieldName=" . $data[$fieldName];
                }
            }
        }
        
        return $list;
    }
    
    /**
     * Initiate a payment with Payzy.
     *
     * This method:
     * 1. Prepares the signed payment data
     * 2. Creates a PayzyPayment record in the database
     * 3. Sends the request to the Payzy API
     * 4. Returns the redirect URL if successful
     *
     * @param array $orderData Payment details (amount, customer info, etc.)
     * @return array ['success' => bool, 'redirect_url' => string|null, 'message' => string, ...]
     */
    public function initiatePayment(array $orderData): array
    {
        try {
            $paymentData = $this->preparePaymentData($orderData);
            
            Log::info('Initiating Payzy payment', [
                'order_id' => $orderData['order_id'],
                'amount' => $orderData['amount'],
            ]);

            // Create a payment record in the database
            $payment = PayzyPayment::create([
                'order_id' => $orderData['order_id'],
                'user_id' => $orderData['user_id'] ?? null,
                'amount' => $orderData['amount'],
                'currency' => $orderData['currency'] ?? 'LKR',
                'payment_method' => 'payzy',
                'payment_status' => PayzyPayment::STATUS_PENDING,
                'transaction_id' => 'PAYZY-' . uniqid() . '-' . time(),
                'payment_data' => $paymentData,
            ]);
            
            // Send the request to Payzy API
            $response = Http::post($this->config['api_url'], $paymentData);

            Log::info('Payzy API response', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                $payment->update([
                    'response_data' => $responseData,
                ]);
                
                return [
                    'success' => true,
                    'redirect_url' => $responseData['url'] ?? null,
                    'transaction_id' => $payment->transaction_id,
                    'payment_id' => $payment->id,
                    'message' => 'Payment initiated successfully',
                ];
            } else {
                Log::error('Payzy payment initiation failed', [
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
                
                $payment->update([
                    'payment_status' => PayzyPayment::STATUS_FAILED,
                    'response_data' => [
                        'error' => $response->body(),
                        'status_code' => $response->status(),
                    ],
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to initiate payment. Please try again.',
                    'error' => $response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception in Payzy payment initiation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while processing your payment.',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Verify the payment callback from Payzy.
     *
     * After the customer completes payment on the Payzy page, Payzy redirects
     * them back to your response_url with these query parameters:
     *   - x_order_id: The order ID you sent
     *   - response_code: '00' = success, anything else = failure
     *   - signature: HMAC signature to verify the callback is genuine
     *
     * @param string $orderId   The x_order_id from the callback
     * @param string $responseCode The response_code from the callback
     * @param string $signature The signature from the callback
     * @return array ['success' => bool, 'message' => string, 'payment' => PayzyPayment|null]
     */
    public function verifyPayment(string $orderId, string $responseCode, string $signature): array
    {
        try {
            Log::info('Payment callback received', [
                'order_id' => $orderId,
                'response_code' => $responseCode,
            ]);
            
            // Find the payment record
            $payment = PayzyPayment::where('order_id', $orderId)->first();
            
            if (!$payment) {
                // Also try finding by payment ID (in case order_id IS the payment ID)
                $payment = PayzyPayment::find($orderId);
            }
            
            if (!$payment) {
                Log::error('Payment not found', ['order_id' => $orderId]);
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                    'payment' => null,
                ];
            }

            $paymentData = $payment->payment_data;
            
            if (!$paymentData || !is_array($paymentData)) {
                Log::error('Invalid payment data', ['payment_id' => $payment->id]);
                return [
                    'success' => false,
                    'message' => 'Invalid payment data',
                    'payment' => $payment,
                ];
            }

            // Validate that required fields exist in stored payment data
            $requiredFields = ['x_test_mode', 'x_shopid', 'x_amount', 'x_order_id', 'x_response_url'];
            foreach ($requiredFields as $field) {
                if (!isset($paymentData[$field])) {
                    Log::error("Missing required field: {$field}", [
                        'payment_id' => $payment->id,
                    ]);
                    return [
                        'success' => false,
                        'message' => "Missing payment data: {$field}",
                        'payment' => $payment,
                    ];
                }
            }

            // Build verification data — NOTE: response_code is PREPENDED to the signed fields
            $signedFieldNames = 'response_code,x_test_mode,x_shopid,x_amount,x_order_id,x_response_url,x_first_name,x_last_name,x_company,x_address,x_country,x_state,x_city,x_zip,x_phone,x_email,x_ship_to_first_name,x_ship_to_last_name,x_ship_to_company,x_ship_to_address,x_ship_to_country,x_ship_to_state,x_ship_to_city,x_ship_to_zip,x_freight,x_platform,x_version,signed_field_names';
            
            $verificationData = [
                'response_code' => $responseCode,
                'x_test_mode' => $paymentData['x_test_mode'],
                'x_shopid' => $paymentData['x_shopid'],
                'x_amount' => $paymentData['x_amount'],
                'x_order_id' => $paymentData['x_order_id'],
                'x_response_url' => $paymentData['x_response_url'],
                'x_first_name' => $paymentData['x_first_name'],
                'x_last_name' => $paymentData['x_last_name'],
                'x_company' => $paymentData['x_company'],
                'x_address' => $paymentData['x_address'],
                'x_country' => $paymentData['x_country'],
                'x_state' => $paymentData['x_state'],
                'x_city' => $paymentData['x_city'],
                'x_zip' => $paymentData['x_zip'],
                'x_phone' => $paymentData['x_phone'],
                'x_email' => $paymentData['x_email'],
                'x_ship_to_first_name' => $paymentData['x_ship_to_first_name'],
                'x_ship_to_last_name' => $paymentData['x_ship_to_last_name'],
                'x_ship_to_company' => $paymentData['x_ship_to_company'],
                'x_ship_to_address' => $paymentData['x_ship_to_address'],
                'x_ship_to_country' => $paymentData['x_ship_to_country'],
                'x_ship_to_state' => $paymentData['x_ship_to_state'],
                'x_ship_to_city' => $paymentData['x_ship_to_city'],
                'x_ship_to_zip' => $paymentData['x_ship_to_zip'],
                'x_freight' => $paymentData['x_freight'],
                'x_platform' => $paymentData['x_platform'],
                'x_version' => $paymentData['x_version'],
                'signed_field_names' => $signedFieldNames,
            ];
            
            // Build the verification string and generate signature
            $list = $this->getVerificationString($verificationData);
            $hash = hash_hmac('sha256', $list, $this->config['secret_key'], true);
            $calculatedSignature = base64_encode($hash);
            
            Log::debug('Signature verification', [
                'received' => $signature,
                'calculated' => $calculatedSignature,
            ]);
            
            // Handle URL-encoded '+' that becomes space
            $receivedSignature = str_replace(' ', '+', $signature);
            
            // Verify signatures match
            if ($calculatedSignature === $receivedSignature) {
                if ($responseCode === '00') {
                    // Payment successful
                    $payment->update([
                        'payment_status' => PayzyPayment::STATUS_COMPLETED,
                        'paid_at' => now(),
                        'response_data' => array_merge($payment->response_data ?? [], [
                            'verified' => true,
                            'response_code' => $responseCode,
                        ]),
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Payment verified successfully',
                        'payment' => $payment,
                    ];
                } else {
                    // Payment failed (valid signature but non-success response code)
                    $payment->update([
                        'payment_status' => PayzyPayment::STATUS_FAILED,
                        'response_data' => array_merge($payment->response_data ?? [], [
                            'verified' => true,
                            'response_code' => $responseCode,
                        ]),
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => 'Payment failed with response code: ' . $responseCode,
                        'payment' => $payment,
                    ];
                }
            } else {
                // Signature mismatch — possible tampering
                $payment->update([
                    'payment_status' => PayzyPayment::STATUS_FAILED,
                    'response_data' => array_merge($payment->response_data ?? [], [
                        'verified' => false,
                        'response_code' => $responseCode,
                        'signature_match' => false,
                    ]),
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Payment verification failed. Signature mismatch.',
                    'payment' => $payment,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception in Payzy payment verification', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while verifying the payment.',
                'error' => $e->getMessage(),
                'payment' => null,
            ];
        }
    }
    
    /**
     * Build the verification string for callback signature verification.
     *
     * NOTE: For verification, ALL fields use 'field=value' format (including x_version).
     * This is different from the initiation signature where x_version omits the '='.
     * Payzy's callback signature uses consistent '=' format.
     */
    protected function getVerificationString(array $data): string
    {
        $fieldsList = [];
        $fieldNames = explode(',', $data['signed_field_names']);
        
        foreach ($fieldNames as $fieldName) {
            if (isset($data[$fieldName])) {
                $fieldsList[] = "$fieldName={$data[$fieldName]}";
            }
        }
        
        return implode(',', $fieldsList);
    }
}
