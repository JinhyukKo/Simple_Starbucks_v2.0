<?php
include '../auth/login_required.php';
require_once '../config.php';


$post_id = $_GET['id'];
$sql = "SELECT filename FROM posts WHERE id = $post_id";
$result = $pdo->query($sql);
$file = $result->fetch();


if (file_exists('uploads/' . $file['filename'])) {
    unlink('uploads/' . $file['filename']);
}


// 게시글 삭제
$sql = "DELETE FROM posts WHERE id = $post_id";
$pdo->query($sql);

header("Location: /board/board.php");
exit();
?>