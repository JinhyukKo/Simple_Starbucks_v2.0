<?php
session_start();

require '../config.php'; // 데이터베이스 연결 파일
include './send_mail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    #$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $email = $_POST['email'];
    if ($email) {
        
        // 사용자 확인
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // 재설정 토큰 생성
            $token = bin2hex(random_bytes(50));
            date_default_timezone_set('Asia/Seoul');
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // 토큰 저장
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id,token_hash,expires_at) VALUES (?,?,?) ");
            $stmt->execute([$user['id'], $token,$expiry]);
            
            // 이메일 발송
            $reset_link = "/reset_password_page.php?token=" . $token;
            $subject = "Reset your Password : Maybe Secure Web";
            $message = "Press this link to reset your password" . $reset_link;            
            if (sendEmailWithGmail($email, $subject, $message)) {
                $_SESSION['message'] = "Email Sent";
            } else {
                $_SESSION['error'] = "Failed to send.";
            }
        } else {
            $_SESSION['error'] = "Email Cannot Be Found";
        }
    } else {
        $_SESSION['error'] = "email exists";
    }
    
    header('Location: reset_password.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="/style.css">

</head>
<body>
    <h2>Reset Password</h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div style="color: green;"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div style="color: red;"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="email" required>
        <button type="submit">Send</button>
    </form>
</body>
</html>