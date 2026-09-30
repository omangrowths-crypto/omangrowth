<?php
/**
 * Oman Growth - Contact Form Handler
 * Lamar Business Center, Way No. 4551, Muscat 111, Sultanate of Oman
 * Contact: +968 9811 0669 | hello@omangrowth.com
 */

header('Content-Type: application/json; charset=UTF-8');

// Allow CORS for local dev / testing if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed. Please submit the form via POST.'
    ]);
    exit;
}

// Sanitize inputs
$name    = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
$email   = isset($_POST['email']) ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
$phone   = isset($_POST['phone']) ? trim(strip_tags($_POST['phone'])) : '';
$service = isset($_POST['service']) ? trim(strip_tags($_POST['service'])) : 'General Inquiry';
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

// Validation
if (empty($name) || strlen($name) < 2) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Please provide your full name.'
    ]);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Please provide a valid business email address.'
    ]);
    exit;
}

if (empty($message) || strlen($message) < 5) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Please provide a brief message describing your business challenge.'
    ]);
    exit;
}

// Log inquiry to leads log so inquiries are never lost on servers without active SMTP
$leadData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'name'      => $name,
    'email'     => $email,
    'phone'     => $phone,
    'service'   => $service,
    'message'   => $message,
    'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];

$logFile = __DIR__ . '/contact-leads.log';
@file_put_contents($logFile, json_encode($leadData, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);

// Email notification to Oman Growth team
$to = 'hello@omangrowth.com';
$subject = "New Consulting Inquiry: {$name} - [{$service}]";
$headers = "From: Oman Growth Website <no-reply@omangrowth.com>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>New Inquiry</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 20px auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
        <div style='background: #0f172a; padding: 20px; color: #fff;'>
            <h2 style='margin: 0; color: #fff;'>Oman Growth - New Inquiry</h2>
            <p style='margin: 5px 0 0; color: #94a3b8;'>Muscat Consulting Office</p>
        </div>
        <div style='padding: 24px; background: #ffffff;'>
            <p><strong>Full Name:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Email:</strong> <a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></p>
            <p><strong>Phone:</strong> <a href='tel:" . htmlspecialchars($phone) . "'>" . htmlspecialchars($phone) . "</a></p>
            <p><strong>Practice / Service:</strong> " . htmlspecialchars($service) . "</p>
            <div style='margin-top: 15px; padding: 15px; background: #f8fafc; border-left: 4px solid #0284c7; border-radius: 4px;'>
                <strong>Message:</strong><br>
                " . nl2br(htmlspecialchars($message)) . "
            </div>
            <p style='font-size: 12px; color: #64748b; margin-top: 20px;'>
                Submitted on " . date('d M Y, H:i') . " GST from IP: " . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '') . "<br>
                Lamar Business Center, Way No. 4551, Muscat 111, Oman
            </p>
        </div>
    </div>
</body>
</html>
";

@mail($to, $subject, $body, $headers);

echo json_encode([
    'status' => 'success',
    'message' => 'Thank you! Your message has been sent successfully to the Oman Growth Muscat consulting team. We will review your requirements and respond within 24 hours.'
]);
exit;
