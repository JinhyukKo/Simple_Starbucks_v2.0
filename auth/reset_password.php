<?php
#! incomplete
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // PHPMailer

include "./logout_required.php";
require_once "../config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = $_POST['username'];
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $user["email"] === $email) {
        try {
            echo "<h1>email sent</h1>";
        } catch (Exception $e) {
            echo "전송실패 : ".$e;
        }
        exit();
    } else {
               $_SESSION['error'] = "the username or email address doesn't exist.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email</title>
</head>
<body>
    <h1>Password Reset</h1>

    <form method="POST">
        <p>
            username : <br>
            <input type="text" name="username">
        </p>
        <p>
            email : <br>
            <input type="email" name="email">
        </p>
        <p>
            <input type="submit" value="password_reset">
        </p>
    </form>
    
</body>
</html>

