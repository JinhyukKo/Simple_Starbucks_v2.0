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

$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$sql = "SELECT p.*, u.username, u.role AS author_role
        FROM posts p JOIN users u ON p.user_id = u.id
        WHERE p.id = $post_id";
$result = $pdo->query($sql);
$post = $result ? $result->fetch() : false;

if (!$post) {
    http_response_code(404);
    exit('The post does not exist ');
}

$myId   = $_SESSION['user_id'] ?? 0;
$myRole = $_SESSION['role'] ?? 'user';

$isSecret = (int)$post['is_secret'] === 1;
$isOwner  = ($myId == (int)$post['user_id']);
$isAdmin  = ($myRole === 'admin');
$isAuthorAdmin = ($post['author_role'] === 'admin');

// ✅ 수정된 비밀글 접근 제어: 
// - 관리자가 작성한 비밀글은 관리자만 접근 가능
// - 일반 사용자가 작성한 비밀글은 작성자와 관리자만 접근 가능
if ($isSecret) {
    if ($isAuthorAdmin) {
        // 관리자가 작성한 비밀글: 관리자만 접근 가능
        if (!$isAdmin) {
            http_response_code(403);
            echo "<h2>Secret Post.</h2><p>This is an admin's secret post. Only administrators are allowed to read this content.</p>";
            echo '<p><a href="board.php">List</a></p>';
            exit;
        }
    } else {
        // 일반 사용자가 작성한 비밀글: 작성자 또는 관리자만 접근 가능
        if (!($isOwner || $isAdmin)) {
            http_response_code(403);
            echo "<h2>Secret Post.</h2><p>The author and admin are allowed to read this content.</p>";
            echo '<p><a href="board.php">List</a></p>';
            exit;
        }
    }
}

/* ======================
   댓글 작성/삭제 처리
   ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 댓글 작성
    if (isset($_POST['comment_content'])) {
        $cmt = $_POST['comment_content'] ?? '';
        $uid = $_SESSION['user_id'];
        if ($uid && trim($cmt) !== '') {
            $sqlIns = "INSERT INTO comments (post_id, user_id, content, created_at)
                       VALUES ($post_id, $uid, '$cmt', NOW())";
            $pdo->query($sqlIns);
        }
        header("Location: view.php?id=" . $post_id);
        exit;
    }

    // 댓글 삭제 (본인 또는 admin만)
    if (isset($_POST['delete_comment_id'])) {
        $cid = (int)$_POST['delete_comment_id'];
        // 해당 댓글 소유자 확인
        $row = $pdo->query("SELECT user_id FROM comments WHERE id = $cid")->fetch();
        if ($row && ($isAdmin || (int)$row['user_id'] === (int)$_SESSION['user_id'])) {
            $pdo->query("DELETE FROM comments WHERE id = $cid");
        }
        header("Location: view.php?id=" . $post_id);
        exit;
    }
}

// 댓글 목록
$comments = $pdo->query("
    SELECT c.id, c.content, c.created_at, c.user_id, u.username
    FROM comments c JOIN users u ON c.user_id = u.id
    WHERE c.post_id = $post_id
    ORDER BY c.created_at ASC
")->fetchAll();

$safeTitle = html_escape($post['title']);
$safeAuthor = html_escape($post['username']);
$safeCreatedAt = html_escape($post['created_at']);
$contentHtml = nl2br(html_escape($post['content']));

$hasAttachment = !empty($post['filename']);
if ($hasAttachment) {
    $downloadName = basename($post['filename']);
    $downloadUrl = 'uploads/' . rawurlencode($downloadName);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $safeTitle ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
  <div class="container">
        <h1><?= $safeTitle ?></h1>

    <p>
        <a href="../index.php">main</a> |
        <a href="board.php">borad</a>
    </p>

    <p>작성자: <?= $safeAuthor ?>
       | 작성일: <?= $safeCreatedAt ?>
       <?php if ($isSecret): ?>
         | <strong>🔒 비밀글</strong>
         <?php if ($isAuthorAdmin): ?>
           | <em>(Admin's Secret Post)</em>
         <?php endif; ?>
       <?php endif; ?>
    </p>

    <div>
        <?= $contentHtml ?>
    </div>

    <?php if ($hasAttachment): ?>
        <p><a href="<?= html_escape($downloadUrl) ?>" download>
            <?= html_escape($downloadName) ?>
        </a></p>
    <?php endif; ?>

    <p>
        <a href="board.php">List</a> |
        <?php if ($isOwner || $isAdmin): ?>
          <a href="delete.php?id=<?= (int)$post['id']; ?>" onclick="return confirm('Confirm to Delete')">Delete</a>
        <?php endif; ?>
    </p>

    <!-- ======================
         댓글 영역
         ====================== -->
    <h2>댓글</h2>

    <?php if ($comments): ?>
      <?php foreach ($comments as $c): ?>
        <div class="comment">
          <div class="meta">
            <?= html_escape($c['username']) ?>
            (<?= html_escape($c['created_at']) ?>)
          </div>
          <div class="body">
            <?= nl2br(html_escape($c['content'])) ?>
            <?php if ($isAdmin || (int)$c['user_id'] === (int)$_SESSION['user_id']): ?>
              <span class="comment-actions">
                <form method="post" style="display:inline">
                  <input type="hidden" name="delete_comment_id" value="<?= (int)$c['id'] ?>">
                  <button type="submit" onclick="return confirm('Will you delete this comment?')">Delete</button>
                </form>
              </span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="muted">No comments yet</p>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
      <h3>Write Comments
      </h3>
      <form method="post">
        <p>
          <textarea name="comment_content" rows="4" placeholder="put your comment here"></textarea>
        </p>
        <p><button type="submit">Write</button></p>
      </form>
    <?php else: ?>
      <p class="muted">Only Authenticated users can comment</p>
    <?php endif; ?>
  </div>

</body>
</html>
