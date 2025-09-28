<?php
require __DIR__ . '/../vendor/autoload.php';

// 1. PHPMailer 불러오기
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function sendEmailWithGmail($to_email, $subject, $message) {
    // 3. PHPMailer 객체 생성
    $mail = new PHPMailer(true); // true = 예외처리 사용
    
    try {
        // 🔧 4. SMTP 설정 (핵심)
        $mail->isSMTP();                      // SMTP 사용 선언
        $mail->Host = 'smtp.gmail.com';       // Gmail SMTP 서버 주소
        $mail->SMTPAuth = true;               // SMTP 인증 사용
        $mail->Username = 'kobin1970@gmail.com'; // 본인 Gmail 주소
        $mail->Password = 'mckd jhje wleu ntfb'; // Gmail 앱 비밀번호 (16자리)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // 암호화 방식
        $mail->Port = 587;                    // Gmail SMTP 포트
        
        // ✉️ 5. 메일 내용 설정
        $mail->setFrom('kobin1970@gmail.com', 'Maybe-Secure-Web-Team-F4'); // 발신자
        $mail->addAddress($to_email);         // 수신자
        #$mail->addReplyTo('help@yourdomain.com', '고객센터'); // 답장받을 주소
        
        // 📝 6. 메일 본문
        $mail->isHTML(true);                  // HTML 메일 사용
        $mail->Subject = $subject;            // 제목
        $mail->Body    = $message;            // HTML 본문
        $mail->AltBody = strip_tags($message); // 텍스트 전용 본문
        
        // 🚀 7. 메일 발송
        if ($mail->send()) {
            return true;
        }
        
    } catch (Exception $e) {
        return false;
    }
}