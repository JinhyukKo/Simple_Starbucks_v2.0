<?php
    require './config.php';
    echo "<h1>Admin Page!</h1>";
    echo "flag_{h3ll0_adm1n}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="grid">
  <!-- Products -->
  <div class="card">
    <h2>Products</h2>
    <a href="products.php?action=add" class="btn primary">Add Product</a>
    <table>
      <thead>
        <tr><th>ID</th><th>Name</th><th>Price</th><th>Category</th><th>Action</th></tr>
      </thead>
      <tbody>
      <?php
      #$res = $conn->query("SELECT * FROM products ORDER BY id ASC");
      while ($row = $res->fetch_assoc()) {
          echo "<tr>
                  <td>{$row['id']}</td>
                  <td>".htmlspecialchars($row['name'])."</td>
                  <td>{$row['price']}</td>
                  <td>{$row['stock']}</td>
                  <td>
                    <a href='products.php?action=edit&id={$row['id']}' class='btn'>수정</a>
                    <a href='products.php?action=delete&id={$row['id']}' class='btn danger' onclick='return confirm(\"삭제하시겠습니까?\")'>삭제</a>
                  </td>
                </tr>";
      }
      ?>
      </tbody>
    </table>
  </div>
    <!-- Users -->
  <div class="card">
    <h2>Users</h2>
    <a href="users.php?action=add" class="btn primary">유저 추가</a>
    <table>
      <thead>
        <tr><th>ID</th><th>이름</th><th>이메일</th><th>역할</th><th>액션</th></tr>
      </thead>
      <tbody>
      <?php
      $res = $conn->query("SELECT * FROM users ORDER BY id ASC");
      while ($row = $res->fetch_assoc()) {
          echo "<tr>
                  <td>{$row['id']}</td>
                  <td>".htmlspecialchars($row['name'])."</td>
                  <td>".htmlspecialchars($row['email'])."</td>
                  <td>{$row['role']}</td>
                  <td>
                    <a href='users.php?action=edit&id={$row['id']}' class='btn'>수정</a>
                    <a href='users.php?action=delete&id={$row['id']}' class='btn danger' onclick='return confirm(\"삭제하시겠습니까?\")'>삭제</a>
                  </td>
                </tr>";
      }
      ?>
      </tbody>
    </table>
  </div>

    
</body>
</html>