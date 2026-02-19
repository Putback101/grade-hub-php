<?php

require_once __DIR__ . '/../config.php';

class Mailer {
    private static $instance = null;
    
    // Simple mail implementation using PHP mail() function
    // For production, integrate with Symfony Mailer or SwiftMailer
    
    public static function sendEmail($to, $subject, $body, $isHtml = true) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= $isHtml ? "Content-type: text/html; charset=UTF-8" : "Content-type: text/plain; charset=UTF-8";
        $headers .= "\r\n";
        $headers .= "From: " . APP_NAME . " <noreply@gradehub.local>" . "\r\n";
        
        return mail($to, $subject, $body, $headers);
    }
    
    public static function sendGradeApprovalNotification($studentEmail, $studentName, $subjectCode, $subjectName, $grade) {
        $subject = "Grade Approved - {$subjectCode}";
        $body = "
            <html><body>
            <p>Dear {$studentName},</p>
            <p>Your grade for <strong>{$subjectCode} - {$subjectName}</strong> has been approved.</p>
            <p><strong>Final Grade: {$grade}</strong></p>
            <p>You can view your grades in the GradeHub system.</p>
            <p>Best regards,<br/>GradeHub Assessment System</p>
            </body></html>
        ";
        return self::sendEmail($studentEmail, $subject, $body, true);
    }
    
    public static function sendGradeVerificationNotification($registrarEmail, $registrarName, $count) {
        $subject = "Grade Verification Required";
        $body = "
            <html><body>
            <p>Dear {$registrarName},</p>
            <p>You have <strong>{$count}</strong> grade(s) pending verification in the GradeHub system.</p>
            <p>Please review and approve them at your earliest convenience.</p>
            <p><a href='" . APP_URL . "/public/grade-verification'>Go to Grade Verification</a></p>
            <p>Best regards,<br/>GradeHub Assessment System</p>
            </body></html>
        ";
        return self::sendEmail($registrarEmail, $subject, $body, true);
    }
    
    public static function sendCorrectionRequestNotification($registrarEmail, $registrarName, $studentName, $subjectCode, $reason) {
        $subject = "Grade Correction Request - {$subjectCode}";
        $body = "
            <html><body>
            <p>Dear {$registrarName},</p>
            <p>A grade correction request has been submitted.</p>
            <p><strong>Student:</strong> {$studentName}</p>
            <p><strong>Subject:</strong> {$subjectCode}</p>
            <p><strong>Reason:</strong> {$reason}</p>
            <p>Please review the request in the GradeHub system.</p>
            <p><a href='" . APP_URL . "/public/grade-corrections'>View Corrections</a></p>
            <p>Best regards,<br/>GradeHub Assessment System</p>
            </body></html>
        ";
        return self::sendEmail($registrarEmail, $subject, $body, true);
    }
    
    public static function sendLoginNotification($userEmail, $userName) {
        $subject = "Login Alert - GradeHub";
        $body = "
            <html><body>
            <p>Dear {$userName},</p>
            <p>You logged into GradeHub on " . date('Y-m-d H:i:s') . "</p>
            <p>If this wasn't you, please contact the administrator.</p>
            <p>Best regards,<br/>GradeHub Assessment System</p>
            </body></html>
        ";
        // Optional: Send login notifications
        // return self::sendEmail($userEmail, $subject, $body, true);
        return true;
    }
    
    public static function sendBulkNotification($recipients, $subject, $body) {
        $success = 0;
        foreach ($recipients as $email) {
            if (self::sendEmail($email, $subject, $body, true)) {
                $success++;
            }
        }
        return ['success' => $success, 'total' => count($recipients)];
    }

    public static function sendPasswordResetEmail($userEmail, $userName, $resetLink) {
        $subject = "Reset Your Password - GradeHub";
        $body = "
            <html><body>
            <p>Dear {$userName},</p>
            <p>We received a request to reset your password.</p>
            <p><a href='{$resetLink}'>Click here to reset your password</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you did not request this, you can ignore this email.</p>
            <p>Best regards,<br/>GradeHub Assessment System</p>
            </body></html>
        ";
        return self::sendEmail($userEmail, $subject, $body, true);
    }
}
?>
