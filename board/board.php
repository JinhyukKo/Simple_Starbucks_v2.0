<?php
include '../auth/login_required.php';
require '../config.php';
include '../header.php';

if (!function_exists('html_escape')) {
    function html_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$q = (string) (filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW) ?? '');
$q = trim($q);
if (strlen($q) > 200) {
    $q = substr($q, 0, 200);
}

$field = (string) (filter_input(INPUT_GET, 'field', FILTER_UNSAFE_RAW) ?? 'title');
$role = (string) (filter_input(INPUT_GET, 'role', FILTER_UNSAFE_RAW) ?? '');

$validFields = ['title', 'content', 'author', 'all'];
if (!in_array($field, $validFields, true)) {
    $field = 'title';
}

$role = $role === 'admin' ? 'admin' : ($role === 'user' ? 'user' : '');

$where = [];
$params = [];

// $whereSql = '';

// if ($q !== '') {
//     $like = '%' . $q . '%';
//     switch ($field) {
//         case 'title':
//             $whereSql = "WHERE p.title LIKE '$like'";
//             break;
//         case 'content':
//             $whereSql = "WHERE p.content LIKE '$like'";
//             break;
//         case 'author':
//             $whereSql = "WHERE u.username LIKE '$like'";
//             break;
//         default:
//             $whereSql = "WHERE (p.title LIKE '$like' OR p.content LIKE '$like' OR u.username LIKE '$like')";
//             break;
//     }
// }

// if ($role !== '') {
//     if ($whereSql) {
//         $whereSql .= " AND COALESCE(p.role, u.role) = '$role'";
//     } else {
//         $whereSql = "WHERE COALESCE(p.role, u.role) = '$role'";
//     }
// }

// sqli - prepared statement
if ($q !== '') {
    $like = '%' . $q . '%';
    switch ($field) {
        case 'title':
            $where[] = 'p.title LIKE ?';
            $params[] = $like;
            break;
        case 'content':
            $where[] = 'p.content LIKE ?';
            $params[] = $like;
            break;
        case 'author':
            $where[] = 'u.username LIKE ?';
            $params[] = $like;
            break;
        default:
            $where[] = '(p.title LIKE ? OR p.content LIKE ? OR u.username LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            break;
    }
}

if ($role !== '') {
    $where[] = 'COALESCE(p.role, u.role) = ?';
    $params[] = $role;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
SELECT
    p.id,
    p.title,
    p.created_at,
    u.username AS author_name,
    COALESCE(p.role, u.role) AS role_name
FROM posts p
JOIN users u ON p.user_id = u.id
{$whereSql}
ORDER BY p.created_at DESC, p.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$preserveQs = http_build_query([
    'q' => $q,
    'field' => $field,
    'role' => $role,
]);
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>Board</title>
  <link rel="stylesheet" href="/style.css">
</head>
<body>
  <div class="container">
  <h1>Board</h1>

  <form class="search" method="get" action="">
    <label>
      Filter
      <select name="field">
        <option value="title"   <?= $field === 'title' ? 'selected' : '' ?>>Title</option>
        <option value="content" <?= $field === 'content' ? 'selected' : '' ?>>Contents</option>
        <option value="author"  <?= $field === 'author' ? 'selected' : '' ?>>Author</option>
        <option value="all"     <?= $field === 'all' ? 'selected' : '' ?>>All(Title+Contents+Author)</option>
      </select>
    </label>
    <label>
      Keyword
      <input type="text" name="q" value="<?= html_escape($q) ?>" placeholder="Search">
    </label>
    <label>
      Role
      <select name="role">
        <option value=""        <?= $role === '' ? 'selected' : '' ?>>All</option>
        <option value="user"    <?= $role === 'user' ? 'selected' : '' ?>>user</option>
        <option value="admin"   <?= $role === 'admin' ? 'selected' : '' ?>>admin</option>
      </select>
    </label>
    <button type="submit">Search</button>
    <a href="/board/write.php"> New Post</a>
    <?php if ($q !== '' || $role !== ''): ?>
      <a href="board.php" style="align-self:center">Reset</a>
    <?php endif; ?>
  </form>

  <?php if (!$rows && $q !== ''): ?>
    <p class="no-results">"<?= html_escape($q) ?>" No Result Found.</p>
  <?php elseif (!$rows): ?>
    <p class="muted">No Content Found.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th style="width:80px">Number</th>
          <th>Title</th>
          <th style="width:160px">Author</th>
          <th style="width:180px">Created_At</th>
          <th style="width:120px">Role</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <?php
            $id = (int) $r['id'];
            $title = html_escape($r['title'] ?? '');
            $author = html_escape($r['author_name'] ?? '');
            $created = html_escape($r['created_at'] ?? '');
            $roleName = html_escape($r['role_name'] ?? '');

            $link = 'view.php?id=' . $id;
            if ($preserveQs !== '') {
                $link .= '&' . $preserveQs;
            }
          ?>
          <tr>
            <td><?= $id ?></td>
            <td><a href="<?= html_escape($link) ?>"><?= $title ?></a></td>
            <td><?= $author ?></td>
            <td><?= $created ?></td>
            <td><?= $roleName ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  </div>

</body>
</html>
