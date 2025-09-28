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
</head>
<body>
    <h1>LOGIN</h1>

    <form method="POST">
        <p>
            username : <br>
            <input type="text" name="username">
        </p>
        <p>
            password : <br>
            <input type="password" name="password">
        </p>
        <p>
            <input type="submit" value="로그인">
        </p>
    </form>
    <a href="reset_password.php">password reset</a>

    <p><a href="register.php">register</a>
</body>
</html>