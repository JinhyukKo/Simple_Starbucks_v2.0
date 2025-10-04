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

const EDIT_MAX_UPLOAD_BYTES = 2097152; // 2 MB
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'];

$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$sql = "SELECT p.*, u.role AS author_role FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$post = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;

if (!$post) {
    http_response_code(404);
    exit('The post does not exist');
}

$myId   = $_SESSION['user_id'] ?? 0;
$myRole = $_SESSION['role'] ?? 'user';

$isOwner  = ((int) $myId === (int) $post['user_id']);
$isAdmin  = ($myRole === 'admin');

if (!($isOwner || $isAdmin)) {
    http_response_code(403);
    exit('You do not have permission to edit this post');
}

$title = $post['title'] ?? '';
$content = $post['content'] ?? '';
$isSecret = (int) ($post['is_secret'] ?? 0) === 1;
$currentFilename = $post['filename'] ?? null;
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $isSecret = isset($_POST['is_secret']);

    if ($title === '' || mb_strlen($title) > 200) {
        $errors[] = 'Title is required and must be 200 characters or fewer.';
    }

    if ($content === '') {
        $errors[] = 'Content is required.';
    }

    $newFilename = $currentFilename;

    if (!empty($_FILES['upload']['name'])) {
        $file = $_FILES['upload'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed. Please try again.';
        } elseif ($file['size'] > EDIT_MAX_UPLOAD_BYTES) {
            $errors[] = 'Uploaded file exceeds the 2 MB size limit.';
        } else {
            $extension = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($extension && !in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'File type is not allowed.';
            } else {
                $uploadDir = __DIR__ . '/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $generatedName = bin2hex(random_bytes(12));
                if ($extension) {
                    $generatedName .= '.' . $extension;
                }

                $targetPath = $uploadDir . '/' . $generatedName;

                $mimeType = 'application/octet-stream';
                if (class_exists('finfo')) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $detected = $finfo->file($file['tmp_name']);
                    if (is_string($detected)) {
                        $mimeType = $detected;
                    }
                } elseif (function_exists('mime_content_type')) {
                    $detected = mime_content_type($file['tmp_name']);
                    if (is_string($detected)) {
                        $mimeType = $detected;
                    }
                }

                $allowedMimePrefixes = ['image/', 'text/plain', 'application/pdf'];
                $isMimeAllowed = false;
                foreach ($allowedMimePrefixes as $prefix) {
                    if (strpos($mimeType, $prefix) === 0) {
                        $isMimeAllowed = true;
                        break;
                    }
                }

                if (!$isMimeAllowed) {
                    $errors[] = 'Uploaded file type is not supported.';
                } elseif (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $errors[] = 'Failed to save the uploaded file. Please try again later.';
                } else {
                    $newFilename = 'uploads/' . $generatedName;
                }
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE posts SET title = :title, content = :content, filename = :filename, is_secret = :is_secret WHERE id = :id'
        );
        $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':filename' => $newFilename,
            ':is_secret' => $isSecret ? 1 : 0,
            ':id' => $post_id,
        ]);

        $successMessage = 'Post updated successfully.';
        $currentFilename = $newFilename;
        $redirectToPost = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
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

        .file-input-wrapper input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: border-color 0.3s ease;
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

        .cancel-btn {
            width: 100%;
            padding: 15px;
            background-color: #6c757d;
            color: var(--sb-white);
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-top: 10px;
        }

        .cancel-btn:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .feedback {
            margin-bottom: 16px;
            padding: 12px 16px;
            border-radius: 6px;
        }

        .feedback.error {
            border: 1px solid #d9534f;
            background: rgba(217, 83, 79, 0.12);
            color: #a94442;
        }

        .feedback.success {
            border: 1px solid #28a745;
            background: rgba(40, 167, 69, 0.12);
            color: #1d7a31;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Post</h1>

        <?php if ($errors): ?>
            <div class="feedback error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= html_escape($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif ($successMessage): ?>
            <div class="feedback success">
                <?= html_escape($successMessage) ?>
            </div>
            <?php if (isset($redirectToPost) && $redirectToPost): ?>
                <script>
                    setTimeout(function() {
                        window.location.href = 'view.php?id=<?= (int) $post_id ?>';
                    }, 1500); 
                </script>
            <?php endif; ?>
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
                <?php if ($currentFilename): ?>
                    <div class="current-file">
                        <strong>Current file:</strong> <?= html_escape(basename($currentFilename)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="checkbox-wrapper">
                <input type="checkbox" id="is_secret" name="is_secret" value="1" <?= $isSecret ? 'checked' : '' ?>>
                <label for="is_secret">Set as private (only the author and administrators can view)</label>
            </div>

            <button type="submit" class="submit-btn">Save Changes</button>
            <a href="view.php?id=<?= (int) $post_id ?>" class="cancel-btn">Cancel</a>
        </form>
    </div>

    
</body>
</html>
