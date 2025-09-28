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
    <title>Simple Board Main Page</title>
</head>
<body>
    <h1>Simple Board Main Page</h1>

    <?php if (isset($_SESSION['user_id'])): ?>
        <p>Welcome, <?php echo $_SESSION['username']; ?> !</p>
        <p>How's your day today ?</p>
        <p>
            <a href="/board/board.php">Board</a> |
            <a href="/board/profile.php">MyProfile</a> |
            <a href="/auth/logout.php">Logout</a>
        </p>
    <?php else: ?>
        <p>
            <a href="/auth/login.php">Login</a> |
            <a href="/auth/register.php">Register</a> |
            <a href="/board/board.php">Board</a>
        </p>
    <?php endif; ?>

    
</body>
</html>