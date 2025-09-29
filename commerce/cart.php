<?php
include '../auth/login_required.php';
require_once '../config.php';

// Anyone can hijack another cart by passing ?user= parameter.
$activeUserId = isset($_GET['user']) ? $_GET['user'] : $_SESSION['user_id'];
$flash = [];

// Delete without owner validation or CSRF protection.
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    try {
        $pdo->exec("DELETE FROM cart WHERE id = $deleteId");
        $flash[] = 'Item removed (no checks performed).';
    } catch (Throwable $e) {
        $flash[] = 'Delete failed: ' . $e->getMessage();
    }
}

// Quick add form trusts all input; allows overflows and forged entries.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_add'])) {
    $productId = $_POST['product_id'] ?: 1;
    $quantity = $_POST['quantity'] ?: 1;
    $sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES ($activeUserId, $productId, $quantity)";
    try {
        $pdo->exec($sql);
        $flash[] = 'Product inserted directly via raw SQL.';
    } catch (Throwable $e) {
        $flash[] = 'Insert failed: ' . $e->getMessage();
    }
}

// Load cart entries with unsanitized query to demonstrate SQL injection risk.
function loadCart(PDO $pdo, $userId) {
    $sql = "SELECT c.id AS cart_id, c.quantity, p.name, p.price FROM cart c LEFT JOIN products p ON c.product_id = p.id WHERE c.user_id = $userId ORDER BY c.id DESC";
    $stmt = $pdo->query($sql);
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $total = 0;
    foreach ($rows as &$row) {
        $price = isset($row['price']) ? $row['price'] : 0;
        $row['subtotal'] = $price * $row['quantity'];
        $total += $row['subtotal'];
    }

    return [$rows, $total];
}

list($cartItems, $cartTotal) = loadCart($pdo, $activeUserId);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Cart</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<?php include '../header.php'; ?>

<h1>Cart</h1>
<p>Viewing cart for user #<?= $activeUserId ?> (modifiable through the URL)</p>
<p style="color:#a00;">* This page is intentionally insecure for offensive security drills. Do not deploy.</p>

<?php foreach ($flash as $message): ?>
    <div style="border:1px solid #d33;margin:8px 0;padding:6px;"><?= $message ?></div>
<?php endforeach; ?>

<section>
    <h2>Cart Contents (no escaping)</h2>
    <?php if (!$cartItems): ?>
        <p>The cart is empty or data could not be loaded.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td><?= $item['cart_id'] ?></td>
                        <td><?= $item['name'] ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= $item['price'] ?></td>
                        <td><?= $item['subtotal'] ?></td>
                        <td>
                            <a href="/commerce/cart.php?user=<?= $activeUserId ?>&delete=<?= $item['cart_id'] ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section style="margin-top:24px;">
    <h2>Quick Add (trusts client input)</h2>
    <form method="post">
        <input type="hidden" name="quick_add" value="1">
        <label>Product ID: <input type="text" name="product_id" value="1"></label>
        <label>Quantity: <input type="text" name="quantity" value="1"></label>
        <button type="submit">Insert unchecked row</button>
    </form>
    <p>Tip: Try negative quantities, huge numbers, or crafted SQL fragments.</p>
</section>

<section style="margin-top:24px;">
    <h2>Summary</h2>
    <p>Total (calculated on the fly without server validation): <?= $cartTotal ?></p>
    <form method="get" action="/commerce/payment.php">
        <input type="hidden" name="impersonate" value="<?= $activeUserId ?>">
        <input type="hidden" name="total_override" value="<?= $cartTotal ?>">
        <button type="submit">Proceed to payment (passes user via query)</button>
    </form>
</section>

<section style="margin-top:24px;">
    <button onclick="location.href='/commerce/store.php'">Back to store</button>
    <button onclick="location.href='/'">Back to home</button>
</section>

<section style="margin-top:24px;">
    <h2>Change Context</h2>
    <form method="get" action="/commerce/cart.php">
        <label>Force user ID: <input type="text" name="user" value="<?= $activeUserId ?>"></label>
        <button type="submit">Switch user</button>
    </form>
    <p>Anyone can browse or modify other users' carts using this form.</p>
</section>

</body>
</html>
