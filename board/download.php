<?php
include '../auth/login_required.php';
require_once '../config.php';

if (!function_exists('html_escape')) {
    function html_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// 파일 다운로드 시큐어 코딩(기존 코드에서 수정이 아닌 추가)
// 파일 다운로드 시 파일명이 아닌 해당 게시글의 id 키 값을 받아와 파라미터를 통한 경로 조작 방지
$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;


$sql = "SELECT p.*, u.role AS author_role
        FROM posts p JOIN users u ON p.user_id = u.id
        WHERE p.id = ?";

// id 키 값을 통한 SQLI 방지를 위한 prepare-statement 처리
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$post = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;

if (!$post) {
    http_response_code(404);
    exit('The post does not exist');
}


$myId   = $_SESSION['user_id'] ?? 0;
$myRole = $_SESSION['role'] ?? 'user';

$isSecret = (int) $post['is_secret'] === 1;
$isOwner  = ((int) $myId === (int) $post['user_id']);
$isAdmin  = ($myRole === 'admin');
$isAuthorAdmin = ($post['author_role'] === 'admin');

if ($isSecret) {
    if ($isAuthorAdmin) {
        if (!$isAdmin) {
            http_response_code(403);
            exit('You do not have permission to download this file');
        }
    } else {
        if (!($isOwner || $isAdmin)) {
            http_response_code(403);
            exit('You do not have permission to download this file');
        }
    }
}


if (empty($post['filename'])) {
    http_response_code(404);
    exit('No file attached to this post');
}

$filePath = __DIR__ . '/' . $post['filename'];

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found');
}


$fileName = basename($post['filename']);
$fileSize = filesize($filePath);
$mimeType = 'application/octet-stream';

if (class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detected = $finfo->file($filePath);
    if (is_string($detected)) {
        $mimeType = $detected;
    }
}

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($filePath);
exit;
