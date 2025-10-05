<?php
include './logout_required.php';
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
    // $stmt = $pdo->query($sql);

    // sqli - prepared statement
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $password]);
    echo "Regisration Compelete. <a href='login.php'>Login</a>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="/style.css">
<link rel="stylesheet" href="/auth/login.css">
</head>
<body>
    <h1>Register</h1>

    <form method="POST">
        <p>
            username: <br>
            <input type="text" name="username">
        </p>
        <p>
            email: <br>
            <input type="email" name="email">
        </p>
        <p>
            password: <br>
            <input type="password" name="password">
        </p>
        <p>
            <input type="submit" value="register">
        </p>
    </form>
<div class="container">
    <a href="login.php">Login</a>
</div>
</body>
</html>