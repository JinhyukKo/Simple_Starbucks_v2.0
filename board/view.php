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
        WHERE p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$post = $stmt ? $stmt->fetch() : false;

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
                       VALUES (?, ?, ?, NOW())";
            $stmtIns = $pdo->prepare($sqlIns);
            $stmtIns->execute([$post_id, $uid, $cmt]);
        }
        header("Location: view.php?id=" . $post_id);
        exit;
    }

    // 댓글 삭제 (본인 또는 admin만)
    if (isset($_POST['delete_comment_id'])) {
        $cid = (int)$_POST['delete_comment_id'];
        // 해당 댓글 소유자 확인
        $stmtRow = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmtRow->execute([$cid]);
        $row = $stmtRow->fetch();
        if ($row && ($isAdmin || (int)$row['user_id'] === (int)$_SESSION['user_id'])) {
            $stmtDel = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmtDel->execute([$cid]);
        }
        header("Location: view.php?id=" . $post_id);
        exit;
    }
}

// 댓글 목록
$stmtComments = $pdo->prepare("
    SELECT c.id, c.content, c.created_at, c.user_id, u.username
    FROM comments c JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
$stmtComments->execute([$post_id]);
$comments = $stmtComments->fetchAll();

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
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title><?= $safeTitle ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
  <div class="container">
    <h1><?= $safeTitle ?></h1>

    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <strong>Author:</strong> <?= $safeAuthor ?>
        | <strong>Date:</strong> <?= $safeCreatedAt ?>
        <?php if ($isSecret): ?>
          | <strong style="color: #d9534f;">🔒 Secret Post</strong>
          <?php if ($isAuthorAdmin): ?>
            | <em>(Admin's Secret Post)</em>
          <?php endif; ?>
        <?php endif; ?>
    </div>

    <div style="background-color: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; min-height: 200px;">
        <?= $contentHtml ?>
    </div>

    <?php if ($hasAttachment): ?>
        <div style="margin-bottom: 20px; padding: 10px; background-color: #f9f9f9; border-left: 3px solid #5bc0de;">
            <strong>Attachment:</strong>
            <a href="<?= html_escape($downloadUrl) ?>" download>
                <?= html_escape($downloadName) ?>
            </a>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 30px;">
        <a href="board.php" style="padding: 8px 16px; background-color: #5bc0de; color: white; text-decoration: none; border-radius: 3px; display: inline-block;">List</a>
        <?php if ($isOwner || $isAdmin): ?>
          <a href="delete.php?id=<?= (int)$post['id']; ?>" onclick="return confirm('Confirm to Delete')" style="padding: 8px 16px; background-color: #d9534f; color: white; text-decoration: none; border-radius: 3px; display: inline-block;">Delete</a>
        <?php endif; ?>
    </div>

    <!-- ======================
         댓글 영역
         ====================== -->
    <h2 style="border-bottom: 2px solid #333; padding-bottom: 10px;">Comments</h2>

    <?php if ($comments): ?>
      <?php foreach ($comments as $c): ?>
        <div style="background-color: #f9f9f9; padding: 15px; margin-bottom: 10px; border-radius: 5px; border-left: 3px solid #5bc0de;">
          <div style="color: #666; font-size: 0.9em; margin-bottom: 8px;">
            <strong><?= html_escape($c['username']) ?></strong>
            <span style="margin-left: 10px;">(<?= html_escape($c['created_at']) ?>)</span>
          </div>
          <div style="line-height: 1.6;">
            <?= nl2br(html_escape($c['content'])) ?>
            <?php if ($isAdmin || (int)$c['user_id'] === (int)$_SESSION['user_id']): ?>
              <span style="margin-left: 15px;">
                <form method="post" style="display:inline">
                  <input type="hidden" name="delete_comment_id" value="<?= (int)$c['id'] ?>">
                  <button type="submit" onclick="return confirm('Will you delete this comment?')" style="padding: 4px 8px; background-color: #d9534f; color: white; border: none; border-radius: 3px; cursor: pointer;">Delete</button>
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
      <h3 style="margin-top: 30px;">Write Comments</h3>
      <form method="post">
        <div style="margin-bottom: 10px;">
          <textarea name="comment_content" rows="4" placeholder="Put your comment here" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;"></textarea>
        </div>
        <div>
          <button type="submit" style="padding: 10px 20px; background-color: #5cb85c; color: white; border: none; border-radius: 5px; cursor: pointer;">Write</button>
        </div>
      </form>
    <?php else: ?>
      <p class="muted">Only authenticated users can comment</p>
    <?php endif; ?>
  </div>

</body>
</html>
