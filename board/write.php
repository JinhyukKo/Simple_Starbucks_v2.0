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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Posts</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        h1 {
            color: var(--sb-green);
            margin-bottom: 30px;
            font-size: 2.5em;
            text-align: center;
            font-weight: 700;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--sb-light-green);
        }

        .nav-links {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .nav-links a {
            color: var(--sb-green);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 20px;
            margin: 0 10px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: var(--sb-light-green);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: var(--sb-dark);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--sb-green);
            box-shadow: 0 0 0 2px rgba(0, 98, 65, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 200px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        .file-input-wrapper input[type="file"]:hover {
            border-color: var(--sb-green);
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            background: var(--sb-light-green);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: var(--sb-green);
        }

        .checkbox-wrapper label {
            color: var(--sb-dark);
            cursor: pointer;
            user-select: none;
            font-size: 0.95em;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background-color: var(--sb-green);
            color: var(--sb-white);
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .submit-btn:hover {
            background-color: var(--sb-dark);
            transform: translateY(-2px);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 1.5em;
            }

            .nav-links a {
                display: inline-block;
                margin: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✍️ New Post</h1>


        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" placeholder="Enter post title..." required>
            </div>

            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" placeholder="Write your content here..." required></textarea>
            </div>

            <div class="form-group">
                <label for="upload">Attach File (Optional)</label>
                <div class="file-input-wrapper">
                    <input type="file" id="upload" name="upload">
                </div>
            </div>

            <div class="checkbox-wrapper">
                <input type="checkbox" id="is_secret" name="is_secret" value="1">
                <label for="is_secret">🔒 Set as private (only the author and administrators can view)</label>
            </div>

            <button type="submit" class="submit-btn">📝 Create Post</button>
        </form>
    </div>
</body>
</html>