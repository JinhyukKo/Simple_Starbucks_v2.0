<?php
session_start();
require '../config.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("유효하지 않은 토큰입니다.");
}

// 토큰 유효성 검사
$stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$password_reset = $stmt->fetch();

if (!$password_reset) {
    die("만료되었거나 유효하지 않은 토큰입니다.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password === $confirm_password) {
        if (strlen($new_password) >= 8) {
            // 새 비밀번호 해시화
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // 비밀번호 업데이트 및 토큰 초기화
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            $stmt->execute([$hashed_password, $user['id']]);
            
            $_SESSION['message'] = "비밀번호가 성공적으로 변경되었습니다.";
            header('Location: login.php');
            exit();
        } else {
            $error = "비밀번호는 8자 이상이어야 합니다.";
        }
    } else {
        $error = "비밀번호가 일치하지 않습니다.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>비밀번호 재설정</title>
</head>
<body>
    <h2>새 비밀번호 설정</h2>
    
    <?php if (isset($error)): ?>
        <div style="color: red;"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label>새 비밀번호:</label>
            <input type="password" name="password" required minlength="8">
        </div>
        <div>
            <label>비밀번호 확인:</label>
            <input type="password" name="confirm_password" required minlength="8">
        </div>
        <button type="submit">비밀번호 변경</button>
    </form>
</body>
</html>