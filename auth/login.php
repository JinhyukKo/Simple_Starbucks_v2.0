<?php
include './logout_required.php';
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $file = $_SERVER['DOCUMENT_ROOT'].'/index.php';
        header("Location: /");
        exit();
    } else {
        echo "로그인 실패";
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