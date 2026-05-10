<?php
// backend/services/email_service.php
// Email sending service using PHP mail() function
// Falls back gracefully if SMTP is not configured
// For production, install PHPMailer via: composer require phpmailer/phpmailer

include_once __DIR__ . '/../config/mail_config.php';

class EmailService {

    /**
     * Send an email using PHP's built-in mail() function
     * For production, replace with PHPMailer SMTP
     */
    public static function sendEmail($to, $subject, $htmlBody) {
        if (!MailConfig::isConfigured()) {
            error_log("[EmailService] SMTP not configured. Email to $to skipped. Subject: $subject");
            return false;
        }

        // Try PHPMailer if available
        $phpmailerPath = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($phpmailerPath)) {
            return self::sendWithPHPMailer($to, $subject, $htmlBody);
        }

        // Fallback: use PHP mail()
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MailConfig::$FROM_NAME . " <" . MailConfig::$FROM_EMAIL . ">\r\n";

        return @mail($to, $subject, $htmlBody, $headers);
    }

    /**
     * Send using PHPMailer (if installed via Composer)
     */
    private static function sendWithPHPMailer($to, $subject, $htmlBody) {
        require_once __DIR__ . '/../vendor/autoload.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MailConfig::$SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MailConfig::$SMTP_USER;
            $mail->Password   = MailConfig::$SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MailConfig::$SMTP_PORT;

            $mail->setFrom(MailConfig::$FROM_EMAIL, MailConfig::$FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("[EmailService] PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Send Order Confirmation Email
     */
    public static function sendOrderConfirmation($userEmail, $userName, $orderId, $trackingId, $items, $totalAmount, $address, $estimatedDelivery) {
        $itemRows = '';
        foreach ($items as $item) {
            $itemRows .= "<tr><td style='padding:8px;border-bottom:1px solid #eee;'>{$item['name']}</td><td style='padding:8px;border-bottom:1px solid #eee;text-align:center;'>{$item['quantity']}</td><td style='padding:8px;border-bottom:1px solid #eee;text-align:right;'>₹" . number_format($item['price_at_purchase'], 2) . "</td></tr>";
        }

        $html = self::getEmailTemplate("Order Confirmed! 🎉", "
            <p>Hi <strong>{$userName}</strong>,</p>
            <p>Thank you for your order! Here are your order details:</p>
            <div style='background:#f8f9fa;padding:15px;border-radius:8px;margin:15px 0;'>
                <p><strong>Order ID:</strong> #{$orderId}</p>
                <p><strong>Tracking ID:</strong> {$trackingId}</p>
                <p><strong>Estimated Delivery:</strong> {$estimatedDelivery}</p>
            </div>
            <table style='width:100%;border-collapse:collapse;margin:15px 0;'>
                <thead><tr style='background:#f1f5f9;'><th style='padding:10px;text-align:left;'>Product</th><th style='padding:10px;text-align:center;'>Qty</th><th style='padding:10px;text-align:right;'>Price</th></tr></thead>
                <tbody>{$itemRows}</tbody>
                <tfoot><tr><td colspan='2' style='padding:10px;font-weight:700;'>Total</td><td style='padding:10px;text-align:right;font-weight:700;'>₹" . number_format($totalAmount, 2) . "</td></tr></tfoot>
            </table>
            <p><strong>Shipping Address:</strong><br>{$address}</p>
        ");

        return self::sendEmail($userEmail, "Order Confirmed - #{$orderId} | FoldNest", $html);
    }

    /**
     * Send Welcome Email
     */
    public static function sendWelcomeEmail($userEmail, $userName) {
        $html = self::getEmailTemplate("Welcome to FoldNest! 🏠", "
            <p>Hi <strong>{$userName}</strong>,</p>
            <p>Welcome to FoldNest Furniture! We're excited to have you on board.</p>
            <p>Explore our collection of premium, foldable furniture designed for modern living spaces.</p>
            <div style='text-align:center;margin:25px 0;'>
                <a href='http://localhost/foldnest/frontend/pages/products.html' style='background:#0f172a;color:white;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;'>Start Shopping →</a>
            </div>
        ");

        return self::sendEmail($userEmail, "Welcome to FoldNest Furniture! 🏠", $html);
    }

    /**
     * Base HTML email template with FoldNest branding
     */
    private static function getEmailTemplate($title, $bodyContent) {
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1.0'></head>
        <body style='margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#f4f4f5;'>
            <div style='max-width:600px;margin:0 auto;background:white;'>
                <div style='background:#0f172a;padding:25px;text-align:center;'>
                    <h1 style='color:white;margin:0;font-size:1.5rem;'>FoldNest Furniture</h1>
                </div>
                <div style='padding:30px;'>
                    <h2 style='color:#0f172a;margin-bottom:15px;'>{$title}</h2>
                    {$bodyContent}
                </div>
                <div style='background:#f8f9fa;padding:20px;text-align:center;font-size:0.85rem;color:#999;'>
                    <p>© 2026 FoldNest Furniture. All rights reserved.</p>
                    <p>Smart Furniture For Smart Homes</p>
                </div>
            </div>
        </body></html>";
    }
}
?>
