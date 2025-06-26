<?php
/**
 * A Square Mall - M-Pesa API Integration
 * Simulates Safaricom Daraja API for M-Pesa STK Push
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}
try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $required_fields = ['phoneNumber', 'amount', 'description'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Sanitize and validate input
    $phone_number = formatPhoneNumber($input['phoneNumber']);
    $amount = floatval($input['amount']);
    $description = htmlspecialchars(trim($input['description']));
    
    if (!isValidKenyanPhone($phone_number)) {
        throw new Exception('Invalid Kenyan phone number format');
    }
    
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than 0');
    }
    
    if ($amount < 1) {
        throw new Exception('Minimum amount is KSh 1');
    }
    
    if ($amount > 300000) {
        throw new Exception('Maximum amount is KSh 300,000');
    }
    
    // Generate transaction IDs
    $merchant_request_id = generateTransactionID('MR');
    $checkout_request_id = generateTransactionID('CR');
    $reference_code = generateReferenceCode();
    
    // Simulate M-Pesa STK Push
    $mpesa_request = [
        'merchant_request_id' => $merchant_request_id,
        'checkout_request_id' => $checkout_request_id,
        'reference_code' => $reference_code,
        'phone_number' => $phone_number,
        'amount' => $amount,
        'description' => $description,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'pending',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Log the transaction (in a real app, save to database)
    $log_file = __DIR__ . '/logs/mpesa_transactions.log';
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    file_put_contents($log_file, json_encode($mpesa_request) . "\n", FILE_APPEND | LOCK_EX);
    
    // Simulate processing delay
    usleep(500000); // 0.5 second delay
    
    // Simulate STK Push response (90% success rate)
    $success_rate = 0.9;
    $is_successful = (mt_rand() / mt_getrandmax()) <= $success_rate;
    
    if ($is_successful) {
        // Successful STK Push
        $response = [
            'success' => true,
            'message' => 'STK Push sent successfully',
            'merchant_request_id' => $merchant_request_id,
            'checkout_request_id' => $checkout_request_id,
            'reference_code' => $reference_code,
            'phone_number' => $phone_number,
            'amount' => $amount,
            'description' => $description,
            'status' => 'sent',
            'timestamp' => date('Y-m-d H:i:s'),
            'instructions' => 'Please check your phone for M-Pesa prompt and enter your PIN to complete the payment.'
        ];
        
        // Send SMS confirmation to customer
        sendPaymentSMS($phone_number, $amount, $reference_code, 'stk_sent');
        
        // Log successful STK Push
        logMpesaEvent($merchant_request_id, 'stk_sent', $response);
        
        echo json_encode($response);
        
    } else {
        // Failed STK Push
        throw new Exception('Failed to send STK Push. Please try again.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'MPESA_ERROR'
    ]);
}
/**
 * Format phone number to Kenyan format
 */
function formatPhoneNumber($phone) {
    $clean_phone = preg_replace('/\D/', '', $phone);
    
    if (substr($clean_phone, 0, 1) === '0') {
        $clean_phone = '254' . substr($clean_phone, 1);
    } elseif (substr($clean_phone, 0, 3) !== '254') {
        $clean_phone = '254' . $clean_phone;
    }
    
    return $clean_phone;
}
/**
 * Validate Kenyan phone number
 */
function isValidKenyanPhone($phone) {
    return preg_match('/^254[17][0-9]{8}$/', $phone);
}
/**
 * Generate transaction ID
 */
function generateTransactionID($prefix = 'TXN') {
    return $prefix . '_' . date('Ymd') . '_' . uniqid() . '_' . mt_rand(1000, 9999);
}
/**
 * Generate reference code
 */
function generateReferenceCode() {
    return 'ASM' . date('ymd') . mt_rand(100000, 999999);
}
/**
 * Send payment SMS notification
 */
function sendPaymentSMS($phone, $amount, $reference, $type) {
    $messages = [
        'stk_sent' => "M-Pesa payment request sent for KSh {$amount}. Check your phone and enter PIN. Ref: {$reference} - A Square Mall",
        'success' => "Payment of KSh {$amount} received successfully. Ref: {$reference}. Thank you for choosing A Square Mall!",
        'failed' => "Payment of KSh {$amount} failed. Ref: {$reference}. Please try again or contact support."
    ];
    
    $message = $messages[$type] ?? $messages['stk_sent'];
    
    $sms_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'phone' => $phone,
        'message' => $message,
        'type' => $type,
        'reference' => $reference,
        'amount' => $amount
    ];
    
    $sms_log_file = __DIR__ . '/logs/mpesa_sms.log';
    if (!file_exists(dirname($sms_log_file))) {
        mkdir(dirname($sms_log_file), 0755, true);
    }
    file_put_contents($sms_log_file, json_encode($sms_data) . "\n", FILE_APPEND | LOCK_EX);
    
    return true;
}
/**
 * Log M-Pesa events
 */
function logMpesaEvent($transaction_id, $event, $data) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'transaction_id' => $transaction_id,
        'event' => $event,
        'data' => $data
    ];
    
    $events_log_file = __DIR__ . '/logs/mpesa_events.log';
    if (!file_exists(dirname($events_log_file))) {
        mkdir(dirname($events_log_file), 0755, true);
    }
    file_put_contents($events_log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}
/**
 * Simulate payment confirmation callback
 * This would normally be called by Safaricom's servers
 */
function simulatePaymentCallback($merchant_request_id, $checkout_request_id, $result_code) {
    $callback_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'merchant_request_id' => $merchant_request_id,
        'checkout_request_id' => $checkout_request_id,
        'result_code' => $result_code,
        'result_desc' => $result_code === 0 ? 'Success' : 'Payment failed'
    ];
    
    if ($result_code === 0) {
        // Payment successful
        $callback_data['mpesa_receipt_number'] = 'MPesa' . mt_rand(100000000, 999999999);
        $callback_data['transaction_date'] = date('YmdHis');
        $callback_data['phone_number'] = '254712345678'; // This would come from the original request
        $callback_data['amount'] = 100; // This would come from the original request
    }
    
    // Log callback (in a real app, update database and notify customer)
    logMpesaEvent($merchant_request_id, 'callback_received', $callback_data);
    
    return $callback_data;
}
/**
 * Check transaction status
 * This endpoint would be called to check the status of a transaction
 */
function checkTransactionStatus($checkout_request_id) {
    // In a real app, query the database for transaction status
    // For simulation, return random status
    
    $statuses = ['pending', 'success', 'failed', 'timeout'];
    $random_status = $statuses[array_rand($statuses)];
    
    $status_data = [
        'checkout_request_id' => $checkout_request_id,
        'status' => $random_status,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($random_status === 'success') {
        $status_data['mpesa_receipt_number'] = 'MPesa' . mt_rand(100000000, 999999999);
        $status_data['amount'] = 100;
        $status_data['phone_number'] = '254712345678';
    }
    
    return $status_data;
}
?>