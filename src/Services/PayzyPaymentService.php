<?php

namespace PayzyLaravel\PaymentGateway\Services;

use PayzyLaravel\PaymentGateway\Models\PayzyPayment;
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
     * Prepare data for the payment request
     */
    public function preparePaymentData(array $orderData): array
    {
        $testMode = $this->config['test_mode'] ? 'on' : 'off';
        
        // Initialize the core payment data with EXACT field order
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
            'x_version' => $this->config['version'] ?? '1.0', // Payzy version
        ];
        
        // Define the field names to be included in the signature - EXACT match with example
        $signedFieldNames = 'x_test_mode,x_shopid,x_amount,x_order_id,x_response_url,x_first_name,x_last_name,x_company,x_address,x_country,x_state,x_city,x_zip,x_phone,x_email,x_ship_to_first_name,x_ship_to_last_name,x_ship_to_company,x_ship_to_address,x_ship_to_country,x_ship_to_state,x_ship_to_city,x_ship_to_zip,x_freight,x_platform,x_version,signed_field_names';
        
        $paymentData['signed_field_names'] = $signedFieldNames;
        
        // Generate the signature
        $paymentData['signature'] = $this->generateSignature($paymentData);
        
        // Log the exact payload for debugging
        Log::debug('Payzy payment data', [
            'payload' => json_encode($paymentData, JSON_PRETTY_PRINT),
            'signature_input_string' => $this->getSignatureString($paymentData)
        ]);
        
        return $paymentData;
    }
    
    /**
     * Generate the HMAC signature for the Payzy request
     */
    protected function generateSignature(array $data): string
    {
        $list = $this->getSignatureString($data);
        $hash = hash_hmac('sha256', $list, $this->config['secret_key'], true);
        return base64_encode($hash);
    }
    
    /**
     * Get the string to sign for signature generation
     * This ensures fields are in the exact order specified in signed_field_names
     */
    protected function getSignatureString(array $data): string
    {
        $list = '';
        $fieldNames = explode(',', $data['signed_field_names']);
        
        foreach ($fieldNames as $index => $fieldName) {
            if (isset($data[$fieldName])) {
                // Add comma before field if not the first field
                if ($index > 0) {
                    $list .= ',';
                }
                
                // Special case for x_version field to match JavaScript bug
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
     * Initiate a payment request to Payzy
     */
    public function initiatePayment(array $orderData, PayzyPayment $payment = null): array
    {
        try {
            // Prepare payment data
            $paymentData = $this->preparePaymentData($orderData);
            
            // Log the request
            Log::info('Initiating Payzy payment', [
                'order_id' => $orderData['order_id'],
                'amount' => $orderData['amount']
            ]);

            // Create or update payment record
            if (!$payment) {
                $payment = PayzyPayment::create([
                    'order_id' => $orderData['order_id'],
                    'user_id' => $orderData['user_id'] ?? null,
                    'amount' => $orderData['amount'],
                    'currency' => $orderData['currency'] ?? 'LKR',
                    'payment_method' => 'payzy',
                    'payment_status' => 'Pending',
                    'transaction_id' => $this->generateTransactionId(),
                    'payment_data' => $paymentData,
                ]);
            } else {
                $payment->update([
                    'payment_data' => $paymentData,
                ]);
            }
            
            // Make the API request to Payzy
            $response = Http::post($this->config['api_url'], $paymentData);

            // Log complete response for debugging
            Log::info('Payzy API response', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Update payment with response data
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
                // Log the error
                Log::error('Payzy payment initiation failed', [
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
                
                // Update payment status
                $payment->update([
                    'payment_status' => 'Failed',
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
                'message' => 'An error occurred while processing your payment. Please try again.',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Verify the payment callback from Payzy
     */
    public function verifyPayment(string $orderId, string $responseCode, string $signature): array
    {
        try {
            // Log callback params for debugging
            Log::info('Payment callback received', [
                'order_id' => $orderId,
                'response_code' => $responseCode,
                'signature' => $signature
            ]);
            
            // Find the payment by order_id or payment_id
            $payment = PayzyPayment::where('order_id', $orderId)->first() ?? PayzyPayment::find($orderId);
            
            if (!$payment) {
                Log::error('Payment not found', ['order_id' => $orderId]);
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                ];
            }

            $paymentData = $payment->payment_data;
            
            if (!$paymentData || !is_array($paymentData)) {
                Log::error('Invalid payment data structure', [
                    'payment_id' => $payment->id,
                    'payment_data' => $paymentData
                ]);
                return [
                    'success' => false,
                    'message' => 'Invalid payment data',
                ];
            }

            // Validate required fields
            $requiredFields = ['x_test_mode', 'x_shopid', 'x_amount', 'x_order_id', 'x_response_url'];
            foreach ($requiredFields as $field) {
                if (!isset($paymentData[$field])) {
                    Log::error("Missing required field in payment data: {$field}", [
                        'payment_id' => $payment->id,
                        'available_keys' => array_keys($paymentData)
                    ]);
                    return [
                        'success' => false,
                        'message' => "Missing payment data: {$field}",
                    ];
                }
            }
            
            // Create verification data in the exact format expected by PayZy
            $signedFieldNames = 'response_code,x_test_mode,x_shopid,x_amount,x_order_id,x_response_url,x_first_name,x_last_name,x_company,x_address,x_country,x_state,x_city,x_zip,x_phone,x_email,x_ship_to_first_name,x_ship_to_last_name,x_ship_to_company,x_ship_to_address,x_ship_to_country,x_ship_to_state,x_ship_to_city,x_ship_to_zip,x_freight,x_platform,x_version,signed_field_names';
            
            // Create the verification data structure exactly as expected
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
                'signed_field_names' => $signedFieldNames
            ];
            
            // Generate verification string
            $list = $this->getVerificationString($verificationData);
            
            // Generate signature as expected by PayZy
            $hash = hash_hmac('sha256', $list, $this->config['secret_key'], true);
            $calculatedSignature = base64_encode($hash);
            
            // Log signature comparison for debugging
            Log::debug('Signature verification', [
                'received' => $signature,
                'calculated' => $calculatedSignature,
                'verification_string' => $list
            ]);
            
            // Handle spaces in signature (URL encoded '+' becomes space)
            $receivedSignature = str_replace(' ', '+', $signature);
            
            // Check if signatures match
            if ($calculatedSignature === $receivedSignature) {
                // Update payment status based on response code
                if ($responseCode === '00') {
                    $payment->update([
                        'payment_status' => 'Completed',
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
                    $payment->update([
                        'payment_status' => 'Failed',
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
                 // Update payment with verification failure
                $payment->update([
                    'payment_status' => 'Failed',
                    'response_data' => array_merge($payment->response_data ?? [], [
                        'verified' => false,
                        'response_code' => $responseCode,
                        'signature_match' => false,
                        'received_signature' => $signature,
                        'calculated_signature' => $calculatedSignature,
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
            ];
        }
    }
    
    /**
     * Get the verification string for callback verification
     * Uses the exact field order that PayZy expects
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
    
    /**
     * Generate a unique transaction ID
     */
    protected function generateTransactionId(): string
    {
        return 'payzy_' . uniqid() . '_' . time();
    }
}