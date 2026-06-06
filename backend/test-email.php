<?php

require __DIR__ . '/vendor/autoload.php';

use UpApp\Config\Environment;
use UpApp\Services\EmailService;

// Load environment
Environment::load();

echo "Testing Email Service...\n\n";

echo "SMTP Configuration:\n";
echo "Host: " . Environment::get('SMTP_HOST') . "\n";
echo "Port: " . Environment::get('SMTP_PORT') . "\n";
echo "Username: " . Environment::get('SMTP_USERNAME') . "\n";
echo "From Email: " . Environment::get('SMTP_FROM_EMAIL') . "\n";
echo "APP_URL: " . Environment::get('APP_URL') . "\n\n";

echo "Creating EmailService...\n";
$emailService = new EmailService();

echo "Sending test password reset email...\n";
$result = $emailService->sendPasswordResetEmail(
    'janczewski.piotr@gmail.com',
    'Piotr',
    'TEST-TOKEN-' . bin2hex(random_bytes(16))
);

if ($result) {
    echo "✓ Email sent successfully!\n";
    echo "Check your inbox at janczewski.piotr@gmail.com\n";
} else {
    echo "✗ Email sending failed!\n";
    echo "Check error_log for details.\n";
}
