<?php
/**
 * A Square Mall - Contact Form Handler
 * Handles contact form submissions and sends email notifications
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
    $required_fields = ['firstName', 'lastName', 'email', 'subject', 'message'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Sanitize input
    $contact_data = [
        'firstName' => htmlspecialchars(trim($input['firstName'])),
        'lastName' => htmlspecialchars(trim($input['lastName'])),
        'email' => filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL),
        'phone' => isset($input['phone']) ? htmlspecialchars(trim($input['phone'])) : '',
        'subject' => htmlspecialchars(trim($input['subject'])),
        'message' => htmlspecialchars(trim($input['message'])),
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    if (!$contact_data['email']) {
        throw new Exception('Invalid email address');
    }
    
    // Generate unique submission ID
    $submission_id = 'CNT_' . date('Ymd') . '_' . uniqid();
    
    // Log the submission (in a real app, save to database)
    $log_entry = [
        'submission_id' => $submission_id,
        'contact_data' => $contact_data,
        'status' => 'received'
    ];
    
    // Log to file (for demo purposes)
    $log_file = __DIR__ . '/logs/contact_submissions.log';
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    
    // Send email notification (simulation)
    $email_sent = sendContactEmail($contact_data, $submission_id);
    
    // Send SMS notification to admin (simulation)
    $sms_sent = sendAdminSMS($contact_data, $submission_id);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Contact form submitted successfully',
        'submission_id' => $submission_id,
        'email_sent' => $email_sent,
        'sms_sent' => $sms_sent,
        'timestamp' => $contact_data['timestamp']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
/**
 * Send contact form email notification
 */
function sendContactEmail($data, $submission_id) {
    // In a real application, you would use a proper email service
    // like SendGrid, Mailgun, or PHP's mail() function
    
    $to_admin = 'admin@asquaremall.co.ke';
    $to_customer = $data['email'];
    
    // Admin notification email
    $admin_subject = 'New Contact Form Submission - A Square Mall';
    $admin_message = "
    New contact form submission received:
    
    Submission ID: {$submission_id}
    Name: {$data['firstName']} {$data['lastName']}
    Email: {$data['email']}
    Phone: {$data['phone']}
    Subject: {$data['subject']}
    
    Message:
    {$data['message']}
    
    Submitted: {$data['timestamp']}
    IP Address: {$data['ip_address']}
    ";
    
    // Customer confirmation email
    $customer_subject = 'Thank you for contacting A Square Mall';
    $customer_message = "
    Dear {$data['firstName']},
    
    Thank you for contacting A Square Mall. We have received your message with the following details:
    
    Subject: {$data['subject']}
    Submission ID: {$submission_id}
    
    Our team will review your message and respond within 24 hours.
    
    Best regards,
    A Square Mall Customer Service Team
    
    Phone: +254 700 123 456
    Email: info@asquaremall.co.ke
    ";
    
    // Simulate email sending (log to file)
    $email_log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'admin_email' => [
            'to' => $to_admin,
            'subject' => $admin_subject,
            'message' => $admin_message
        ],
        'customer_email' => [
            'to' => $to_customer,
            'subject' => $customer_subject,
            'message' => $customer_message
        ]
    ];
    
    $email_log_file = __DIR__ . '/logs/email_notifications.log';
    file_put_contents($email_log_file, json_encode($email_log) . "\n", FILE_APPEND | LOCK_EX);
    
    return true;
}
/**
 * Send SMS notification to admin
 */
function sendAdminSMS($data, $submission_id) {
    // In a real application, integrate with SMS service like Africa's Talking
    
    $admin_phone = '+254700123456';
    $message = "New contact form: {$data['firstName']} {$data['lastName']} - {$data['subject']} (ID: {$submission_id})";
    
    // Simulate SMS sending (log to file)
    $sms_log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $admin_phone,
        'message' => $message,
        'submission_id' => $submission_id
    ];
    
    $sms_log_file = __DIR__ . '/logs/sms_notifications.log';
    if (!file_exists(dirname($sms_log_file))) {
        mkdir(dirname($sms_log_file), 0755, true);
    }
    file_put_contents($sms_log_file, json_encode($sms_log) . "\n", FILE_APPEND | LOCK_EX);
    
    return true;
}
?>