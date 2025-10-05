<?php
include '../auth/login_required.php';
require_once '../config.php';

$post_id = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);


// $sql = "SELECT user_id, filename FROM posts WHERE id = $post_id";
// $stmt = $pdo->query($sql);
// $post = $stmt->fetch(PDO::FETCH_ASSOC);
// sql injection - prepared statement
$stmt = $pdo->prepare('SELECT user_id, filename FROM posts WHERE id = ?');
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    exit('The post does not exist.');
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['role'] ?? 'user';
$isOwner = $userId === (int) $post['user_id'];
$isAdmin = $userRole === 'admin';

if (!($isOwner || $isAdmin)) {
    http_response_code(403);
    exit('You do not have permission to delete this post.');
}

if (!empty($post['filename'])) {
    $basename = basename($post['filename']);
    $filePath = __DIR__ . '/uploads/' . $basename;
    if (is_file($filePath)) {
        unlink($filePath);
    }
}

// $sqlDel = "DELETE FROM posts WHERE id = $post_id";
// $stmtDel = $pdo->query($sqlDel);
// sql injection - prepared statement
$stmtDel = $pdo->prepare('DELETE FROM posts WHERE id = ?');
$stmtDel->execute([$post_id]);

header('Location: /board/board.php');
exit();
