<?php
include './auth/login_required.php';
require_once './config.php';
include "./header.php";


$stmt = $pdo->query("
    SELECT p.id, p.title, u.username, p.created_at
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recent_posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Starbucks</title>
    <link rel="stylesheet" href="/style.css">

</head>
<body>
    <div>
    <h1>Simple Starbucks ☕️ </h1>

    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <p>Welcome, <?php echo $_SESSION['username']; ?> !</p>
        <p>How's your day today ?</p>
        <p>
            <a href="/commerce/store.php">Store</a> |
            <a href="/board/board.php">Board</a> 
        </p>
    <?php endif; ?>
</body>
</html>