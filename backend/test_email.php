<?php
// backend/test_email.php
// Script to test email_service.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/services/email_service.php';

echo "Testing Email Service...\n\n";

// Check config
echo "MailConfig::isConfigured() -> " . (MailConfig::isConfigured() ? "true" : "false") . "\n";
echo "SMTP_HOST: " . MailConfig::$SMTP_HOST . "\n";
echo "SMTP_USER: " . MailConfig::$SMTP_USER . "\n\n";

echo "Attempting to send a welcome email...\n";
$result = EmailService::sendWelcomeEmail('testuser@example.com', 'Test User');

echo "Result: " . ($result ? "Success" : "Failed / Skipped") . "\n";

echo "\nCheck your PHP error logs for any 'SMTP not configured' messages.\n";
?>
