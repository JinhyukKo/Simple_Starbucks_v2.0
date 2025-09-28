<?php
include '../auth/login_required.php';
require_once '../config.php';
include '../header.php';


$post_id = $_GET['id'];

// 첨부파일 삭제
$sql = "SELECT filename FROM files WHERE post_id = $post_id";
$result = $pdo->query($sql);
$files = $result->fetchAll();

foreach ($files as $file) {
    if (file_exists('uploads/' . $file['filename'])) {
        unlink('uploads/' . $file['filename']);
    }
}

// 첨부파일 DB 기록 삭제
$sql = "DELETE FROM files WHERE post_id = $post_id";
$pdo->query($sql);

// 게시글 삭제
$sql = "DELETE FROM posts WHERE id = $post_id";
$pdo->query($sql);

header("Location: /board/board.php");
exit();
?>