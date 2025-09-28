<?php
$host = 'localhost';
$dbname = 'board_system';
$username = 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}
?>