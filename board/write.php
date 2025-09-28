<?php
include '../auth/login_required.php';
require_once '../config.php';
include '../header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title   = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $user_id = $_SESSION['user_id'];
    $filename = '';

    // ✅ 비밀글 체크박스
    $is_secret = isset($_POST['is_secret']) ? 1 : 0;

    if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK){
        $filename = $_FILES['upload']['name'];
        $tmp_name = $_FILES['upload']['tmp_name'];
        @mkdir(__DIR__ . "/uploads", 0777, true);
        move_uploaded_file($tmp_name, __DIR__ . "/uploads/" . $filename);
    }

    // ✅ isSecret 저장 (기존 스타일 유지: 단순 쿼리)
    $sql = "INSERT INTO posts (user_id, title, content, filename, isSecret)
            VALUES ($user_id, '$title', '$content', '$filename', $is_secret)";
    $pdo->query($sql);

    header("Location: board.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Write Posts</title>
</head>
<body>
    <h1>Write Posts</h1>

    <p>
        <a href="../index.php">Main</a> |
        <a href="board.php">Board</a>
    </p>

    <form method="POST" enctype="multipart/form-data">
        <p>
            Title: <br>
            <input type="text" name="title" size="50">
        </p>

        <p>
            Content : <br>
            <textarea name="content" rows="10" cols="60"></textarea>
        </p>

        <p>
            File : <br>
            <input type="file" name="upload">
        </p>

        <!-- ✅ 비밀글 토글 -->
        <p>
            <label>
                <input type="checkbox" name="is_secret" value="1">
                Set as private (only the author and administrators can view)
            </label>
        </p>

        <p>
            <input type="submit" value="Write">
        </p>
    </form>
</body>
</html>