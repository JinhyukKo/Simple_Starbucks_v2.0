<?php
include './logout_required.php';
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 세션에 로그인 실패 기록 초기화
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = time();
    }
    if ((time() - $_SESSION['last_attempt_time']) >= 300) {
        $_SESSION['login_attempts'] = 0;
    }

        // 제한 조건: 5회 실패 후 15분 동안 잠금
    if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < 300) {
        echo '<h3>Login is Locked try again in 5 mins<br/></h3>';
    }
    
    // $sql = "SELECT * FROM users WHERE username = '$username'";
    // $stmt = $pdo->query($sql);
    
    // sql injection - prepared statement
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = time();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $file = $_SERVER['DOCUMENT_ROOT'].'/index.php';
        header("Location: /");
        exit();
    } else {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        echo "<h3>Login attempts : ".$_SESSION['login_attempts'].'</h3>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>LOGIN</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/auth/login.css">

</head>
<body>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    <form method="POST">
        <h1>LOGIN</h1>
        <p>
            username : <br>
            <input type="text" name="username">
        </p>
        <p>
            password : <br>
            <input type="password" name="password">
        </p>
        <p>
            <input type="submit" value="Login">
        </p>
    </form>
    <div class="login-links">
        <a href="reset_password.php">password reset</a>
        <span> · </span>
        <a href="register.php">register</a>
    </div>
</body>
</html>