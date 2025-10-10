<?php
$filename = $_GET['file'] ?? '';

// 파일명 검증
if (empty($filename)) {
    http_response_code(400);
    exit('파일명이 없습니다.');
}

// 경로 조작 공격 방지 (../, null byte 등)
if (strpos($filename, '..') !== false || strpos($filename, "\0") !== false) {
    http_response_code(403);
    exit('잘못된 파일명입니다.');
}

// 실제 경로 생성 및 검증
$uploadDir = realpath(__DIR__);
if ($uploadDir === false) {
    http_response_code(500);
    exit('Upload directory not found.');
}

// filename이 'uploads/xxx.ext' 형태로 들어오므로 슬래시를 DIRECTORY_SEPARATOR로 변환
$normalizedFilename = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filename);
$filepath = $uploadDir . DIRECTORY_SEPARATOR . $normalizedFilename;
$realFilepath = realpath($filepath);

// board 디렉토리 밖으로 벗어나는지 검증 (uploads 폴더는 board 안에 있어야 함)
if ($realFilepath === false || strpos($realFilepath, $uploadDir) !== 0) {
    http_response_code(403);
    exit('잘못된 경로입니다.');
}

// uploads 폴더 내부인지 확인 (추가 보안)
$uploadsDir = $uploadDir . DIRECTORY_SEPARATOR . 'uploads';
if (strpos($realFilepath, $uploadsDir) !== 0) {
    http_response_code(403);
    exit('접근 권한이 없습니다.');
}

// 파일 존재 여부 확인
if (!file_exists($realFilepath) || !is_file($realFilepath)) {
    http_response_code(404);
    exit('존재하지 않는 파일');
}

// 다운로드 파일명은 원본 파일명 유지 (경로만 제거)
$safeName = basename($filename);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addslashes($safeName) . '"');
header('Content-Length: ' . filesize($realFilepath));

readfile($realFilepath);
exit;
