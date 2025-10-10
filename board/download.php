<?php
$filename = $_GET['file'] ?? '';

// 경로 탐색 공격 방지 검증
if (empty($filename)) {
    http_response_code(400);
    exit('파일명이 필요합니다');
}

// 경로 구분자 및 상위 디렉토리 참조 차단
if (strpos($filename, '..') !== false ||
    strpos($filename, '/') !== false ||
    strpos($filename, '\\') !== false ) {
    http_response_code(400);
    exit('잘못된 파일명입니다');
}


$filepath = __DIR__ . '/uploads/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    exit('존재하지 않는 파일');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;
