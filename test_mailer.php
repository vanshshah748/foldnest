<?php
include_once __DIR__ . '/backend/services/email_service.php';
$res = EmailService::sendEmail('vanshshah748@gmail.com', 'Test Email', 'This is a test');
if ($res) echo "SUCCESS"; else echo "FAILED";
?>
