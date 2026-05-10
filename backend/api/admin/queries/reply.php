<?php
// backend/api/admin/queries/reply.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../../config/database.php';
include_once '../../../services/email_service.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || empty($data->email) || empty($data->reply_message)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit();
}

try {
    $id = intval($data->id);
    $email = htmlspecialchars(strip_tags($data->email));
    $name = htmlspecialchars(strip_tags($data->name));
    $original_subject = htmlspecialchars(strip_tags($data->subject));
    $reply_msg = htmlspecialchars(strip_tags($data->reply_message));
    
    // Send Email
    $emailService = new EmailService();
    $subject = "Re: " . $original_subject . " - FoldNest Support";
    
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
        <h2>FoldNest Support</h2>
        <p>Dear $name,</p>
        <p>Thank you for contacting us regarding <strong>'$original_subject'</strong>.</p>
        <div style='background: #f4f4f5; padding: 15px; border-left: 4px solid #3b82f6; margin: 20px 0;'>
            " . nl2br($reply_msg) . "
        </div>
        <p>If you have any further questions, please feel free to reply to this email.</p>
        <p>Best regards,<br>FoldNest Customer Success Team</p>
    </div>";

    $sent = $emailService->sendEmail($email, $subject, $htmlBody);
    
    if ($sent) {
        // Mark as resolved
        $stmt = $db->prepare("UPDATE contact_queries SET status = 'resolved' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        echo json_encode(["success" => true, "message" => "Reply sent and query resolved!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to send email. Check SMTP settings."]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
