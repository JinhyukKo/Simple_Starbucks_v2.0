<?php
include '../auth/login_required.php';
require_once '../config.php';
include '../header.php';

if (!function_exists('html_escape')) {
    function html_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}


$title = '';
$content = '';
$isSecret = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $isSecret = isset($_POST['is_secret']);

    if ($title === '' || strlen($title) > 200) {
        $errors[] = 'Title is required and must be 200 characters or fewer.';
    }

    if ($content === '') {
        $errors[] = 'Content is required.';
    }

    // file upload 시큐어 코딩(기존 파일 업로드 로직에서 검증 로직 추가)
    $filename = null;
    
    if (!empty($_FILES['upload']['name'])) {
        $file = $_FILES['upload'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed. ';
        } else {
            // 대소문자 구분없이 확장자 확인
            $extension = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));

            //화이트 리스트 확장자와 비교
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'];
            if ($extension && !in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'File type is not allowed.';
            } else {
                $uploadDir = __DIR__ . '/uploads';
                

                // 파일명 난수화
                $generatedName = bin2hex(random_bytes(12));
                if ($extension) {
                    $generatedName .= '.' . $extension;
                }
                $targetPath = $uploadDir . '/' . $generatedName;

                // Content-Type 검증
                $MimeType = [
                    'image/jpeg',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'application/pdf',
                    'text/plain'
                ];
                $isMimeAllowed = false;
                if (in_array($file['type'], $MimeType, true)){
                    $isMimeAllowed = true;
                }

                if (!$isMimeAllowed) {
                    $errors[] = 'Uploaded file type is not supported.';
                } elseif (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $errors[] = 'Failed to save the uploaded file. Please try again later.';
                } else {
                    $filename = 'uploads/' . $generatedName;
                }
            }
        }
    }

    if (!$errors) {
        // $stmt = $pdo->query("INSERT INTO posts (user_id, title, content, filename, is_secret) VALUES ({$_SESSION['user_id']}, '$title', '$content', '$filename', " . ($isSecret ? 1 : 0) . ")");
        
        // sql injection - prepared statement
        $stmt = $pdo->prepare(
            'INSERT INTO posts (user_id, title, content, filename, is_secret) VALUES (:user_id, :title, :content, :filename, :is_secret)'
        );
        $stmt->execute([
            ':user_id' => (int) $_SESSION['user_id'],
            ':title' => $title,
            ':content' => $content,
            ':filename' => $filename,
            ':is_secret' => $isSecret ? 1 : 0,
        ]);

        header('Location: board.php');
        exit();
    }
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

        .error-box {
            margin-bottom: 20px;
            padding: 16px;
            border: 1px solid #d9534f;
            background: rgba(217, 83, 79, 0.12);
            border-radius: 6px;
            color: #a94442;
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
        <h1>New Post</h1>

        <?php if ($errors): ?>
            <div class="error-box">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= html_escape($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" autocomplete="off">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?= html_escape($title) ?>" maxlength="200" required>
            </div>

            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" required><?= html_escape($content) ?></textarea>
            </div>

            <div class="form-group">
                <label for="upload">Attach File (Optional)</label>
                <div class="file-input-wrapper">
                    <input type="file" id="upload" name="upload" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt">
                </div>
            </div>

            <div class="checkbox-wrapper">
                <input type="checkbox" id="is_secret" name="is_secret" value="1" <?= $isSecret ? 'checked' : '' ?>>
                <label for="is_secret">Set as private (only the author and administrators can view)</label>
            </div>

            <button type="submit" class="submit-btn">Create Post</button>
        </form>
    </div>
</body>
</html>
