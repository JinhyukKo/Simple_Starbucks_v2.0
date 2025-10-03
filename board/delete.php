<?php
include '../auth/login_required.php';
require_once '../config.php';


$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT filename FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$file = $stmt->fetch();


if (file_exists('uploads/' . $file['filename'])) {
    unlink('uploads/' . $file['filename']);
}


// 게시글 삭제
$stmtDel = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmtDel->execute([$post_id]);

header("Location: /board/board.php");
exit();
?>