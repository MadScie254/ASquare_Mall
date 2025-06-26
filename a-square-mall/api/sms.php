<?php
/**
 * A Square Mall - SMS API Integration
 * Simulates Africa's Talking or Celcom SMS services
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
    $required_fields = ['phoneNumber', 'message'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Sanitize and validate input
    $phone_number = formatPhoneNumber($input['phoneNumber']);
    $message = trim($input['message']);
    $type = isset($input['type']) ? $input['type'] : 'notification';
    
    if (!isValidKenyanPhone($phone_number)) {
        throw new Exception('Invalid Kenyan phone number format');
    }
    
    if (strlen($message) === 0) {
        throw new Exception('Message cannot be empty');
    }
    
    if (strlen($message) > 1600) {
        throw new Exception('Message too long. Maximum 1600 characters.');
    }
    
    // Calculate SMS parts (160 characters per SMS)
    $sms_parts = ceil(strlen($message) / 160);
    
    // Generate message ID
    $message_id = generateMessageID();
    
    // Prepare SMS data
    $sms_data = [
        'message_id' => $message_id,
        'phone_number' => $phone_number,
        'message' => $message,
        'type' => $type,
        'sms_parts' => $sms_parts,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'pending',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Validate message content
    if (containsProhibitedContent($message)) {
        throw new Exception('Message contains prohibited content');
    }
    
    // Calculate cost (simulate pricing)
    $cost_per_sms = 1.50; // KSh per SMS part
    $total_cost = $sms_parts * $cost_per_sms;
    
    // Log the SMS request
    $log_file = __DIR__ . '/logs/sms_requests.log';
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    file_put_contents($log_file, json_encode($sms_data) . "\n", FILE_APPEND | LOCK_EX);
    
    // Simulate SMS sending delay
    usleep(200000); // 0.2 second delay
    
    // Simulate SMS delivery (95% success rate)
    $success_rate = 0.95;
    $is_successful = (mt_rand() / mt_getrandmax()) <= $success_rate;
    
    if ($is_successful) {
        // Successful SMS delivery
        $sms_data['status'] = 'sent';
        $sms_data['delivery_time'] = date('Y-m-d H:i:s');
        
        $response = [
            'success' => true,
            'message' => 'SMS sent successfully',
            'message_id' => $message_id,
            'phone_number' => $phone_number,
            'sms_parts' => $sms_parts,
            'cost' => $total_cost,
            'status' => 'sent',
            'timestamp' => $sms_data['timestamp'],
            'delivery_time' => $sms_data['delivery_time']
        ];
        
        // Log successful delivery
        logSMSEvent($message_id, 'delivered', $response);
        
        // Send delivery report (simulate)
        scheduleSMSDeliveryReport($message_id, $phone_number);
        
        echo json_encode($response);
        
    } else {
        // Failed SMS delivery
        $sms_data['status'] = 'failed';
        $sms_data['error'] = 'Network error or invalid number';
        
        logSMSEvent($message_id, 'failed', $sms_data);
        
        throw new Exception('Failed to send SMS. Please try again.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'SMS_ERROR'
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
    // Kenyan mobile numbers: 254[17]XXXXXXXX
    return preg_match('/^254[17][0-9]{8}$/', $phone);
}
/**
 * Generate unique message ID
 */
function generateMessageID() {
    return 'SMS_' . date('Ymd') . '_' . uniqid() . '_' . mt_rand(1000, 9999);
}
/**
 * Check for prohibited content
 */
function containsProhibitedContent($message) {
    $prohibited_keywords = [
        'spam', 'scam', 'fraud', 'illegal', 'drugs',
        'violence', 'hate', 'threat', 'adult'
    ];
    
    $message_lower = strtolower($message);
    
    foreach ($prohibited_keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}
/**
 * Log SMS events
 */
function logSMSEvent($message_id, $event, $data) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message_id' => $message_id,
        'event' => $event,
        'data' => $data
    ];
    
    $events_log_file = __DIR__ . '/logs/sms_events.log';
    if (!file_exists(dirname($events_log_file))) {
        mkdir(dirname($events_log_file), 0755, true);
    }
    file_put_contents($events_log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}
/**
 * Schedule SMS delivery report
 */
function scheduleSMSDeliveryReport($message_id, $phone_number) {
    // Simulate delivery report after random delay (1-10 seconds)
    $delivery_delay = mt_rand(1, 10);
    
    $delivery_report = [
        'message_id' => $message_id,
        'phone_number' => $phone_number,
        'status' => 'delivered',
        'delivery_time' => date('Y-m-d H:i:s', time() + $delivery_delay),
        'network' => getNetworkProvider($phone_number)
    ];
    
    // Log delivery report
    logSMSEvent($message_id, 'delivery_report', $delivery_report);
    
    return $delivery_report;
}
/**
 * Get network provider from phone number
 */
function getNetworkProvider($phone_number) {
    $prefix = substr($phone_number, 3, 3); // Get first 3 digits after 254
    
    $networks = [
        '701' => 'Safaricom',
        '702' => 'Safaricom',
        '703' => 'Safaricom',
        '704' => 'Safaricom',
        '705' => 'Safaricom',
        '706' => 'Safaricom',
        '707' => 'Safaricom',
        '708' => 'Safaricom',
        '709' => 'Safaricom',
        '710' => 'Airtel',
        '711' => 'Airtel',
        '712' => 'Airtel',
        '713' => 'Airtel',
        '714' => 'Airtel',
        '715' => 'Airtel',
        '716' => 'Airtel',
        '717' => 'Airtel',
        '718' => 'Airtel',
        '719' => 'Airtel',
        '720' => 'Telkom',
        '721' => 'Telkom',
        '722' => 'Telkom',
        '723' => 'Telkom',
        '724' => 'Telkom',
        '725' => 'Telkom',
        '726' => 'Telkom',
        '727' => 'Telkom',
        '728' => 'Telkom'
    ];
    
    return $networks[$prefix] ?? 'Unknown';
}
/**
 * Bulk SMS sending
 */
function sendBulkSMS($recipients, $message, $sender_id = 'ASQUARE') {
    $results = [];
    $total_cost = 0;
    
    foreach ($recipients as $phone) {
        try {
            $phone_formatted = formatPhoneNumber($phone);
            
            if (!isValidKenyanPhone($phone_formatted)) {
                $results[] = [
                    'phone' => $phone,
                    'status' => 'failed',
                    'error' => 'Invalid phone number'
                ];
                continue;
            }
            
            $message_id = generateMessageID();
            $sms_parts = ceil(strlen($message) / 160);
            $cost = $sms_parts * 1.50;
            $total_cost += $cost;
            
            // Simulate 90% success rate for bulk SMS
            $is_successful = (mt_rand() / mt_getrandmax()) <= 0.90;
            
            if ($is_successful) {
                $results[] = [
                    'phone' => $phone_formatted,
                    'message_id' => $message_id,
                    'status' => 'sent',
                    'sms_parts' => $sms_parts,
                    'cost' => $cost
                ];
                
                logSMSEvent($message_id, 'bulk_sent', [
                    'phone' => $phone_formatted,
                    'message' => $message,
                    'sender_id' => $sender_id
                ]);
            } else {
                $results[] = [
                    'phone' => $phone_formatted,
                    'status' => 'failed',
                    'error' => 'Network error'
                ];
            }
            
            // Small delay between messages
            usleep(100000); // 0.1 second
            
        } catch (Exception $e) {
            $results[] = [
                'phone' => $phone,
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    return [
        'total_sent' => count(array_filter($results, function($r) { return $r['status'] === 'sent'; })),
        'total_failed' => count(array_filter($results, function($r) { return $r['status'] === 'failed'; })),
        'total_cost' => $total_cost,
        'results' => $results
    ];
}
/**
 * Check SMS balance (simulation)
 */
function checkSMSBalance() {
    return [
        'balance' => mt_rand(100, 10000), // Random balance between 100-10000 KSh
        'sms_units' => mt_rand(50, 5000), // SMS units available
        'currency' => 'KSh'
    ];
}
/**
 * Get SMS delivery report
 */
function getSMSDeliveryReport($message_id) {
    // Simulate delivery report retrieval
    $statuses = ['delivered', 'pending', 'failed', 'expired'];
    $random_status = $statuses[array_rand($statuses)];
    
    return [
        'message_id' => $message_id,
        'status' => $random_status,
        'delivery_time' => $random_status === 'delivered' ? date('Y-m-d H:i:s', time() - mt_rand(60, 3600)) : null,
        'error' => $random_status === 'failed' ? 'Network timeout' : null
    ];
}
?>