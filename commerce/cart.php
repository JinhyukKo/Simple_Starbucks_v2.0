<?php
include '../auth/login_required.php';
require_once '../config.php';

$userId = $_SESSION['user_id'];
$messages = [];

function fetchCart(PDO $pdo, $userId) {
    $sql = "SELECT c.id AS cart_id, c.product_id, c.quantity, p.name, p.price, p.image_url FROM cart c LEFT JOIN products p ON p.id = c.product_id WHERE c.user_id = $userId ORDER BY c.id DESC";
    $stmt = $pdo->query($sql);
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $total = 0;
    $count = 0;

    foreach ($rows as &$row) {
        $price = isset($row['price']) ? $row['price'] : 0;
        $row['subtotal'] = $price * $row['quantity'];
        $total += $row['subtotal'];
        $count += $row['quantity'];
    }

    return [
        'items' => $rows,
        'total' => $total,
        'count' => $count,
    ];
}

function upsertCartItem(PDO $pdo, $userId, $productId, $qty) {
    // 취약: 검증 없이 수량을 그대로 사용하고 문자열 결합으로 쿼리 생성
    $checkSql = "SELECT quantity FROM cart WHERE user_id = $userId AND product_id = $productId";
    $existing = $pdo->query($checkSql);
    $row = $existing ? $existing->fetch(PDO::FETCH_ASSOC) : null;

    if ($row) {
        $newQty = $row['quantity'] + $qty;
        $pdo->exec("UPDATE cart SET quantity = $newQty WHERE user_id = $userId AND product_id = $productId");
        return 'Quantity updated (simply summed on the server).';
    }

    $pdo->exec("INSERT INTO cart (user_id, product_id, quantity) VALUES ($userId, $productId, $qty)");
    return 'Item added to cart without validation.';
}

$isJson = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isJson) {
        $payload = json_decode(file_get_contents('php://input'), true);
        $action = $payload['action'] ?? null;
        $productId = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $quantity = isset($payload['quantity']) ? (int) $payload['quantity'] : 1;

        $response = ['success' => false, 'message' => 'Unsupported action'];

        if ($action === 'add' && $productId > 0 && $quantity > 0) {
            try {
                $response['message'] = upsertCartItem($pdo, $userId, $productId, $quantity);
                $response['success'] = true;
            } catch (Throwable $e) {
                $response['message'] = $e->getMessage();
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $cartId = $_POST['cart_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;
        try {
            $pdo->exec("UPDATE cart SET quantity = $quantity WHERE id = $cartId AND user_id = $userId");
            $messages[] = 'Quantity updated without further checks.';
        } catch (Throwable $e) {
            $messages[] = 'Update failed: ' . $e->getMessage();
        }
    } elseif ($action === 'remove') {
        $cartId = $_POST['cart_id'] ?? 0;
        try {
            $pdo->exec("DELETE FROM cart WHERE id = $cartId AND user_id = $userId");
            $messages[] = 'Item removed.';
        } catch (Throwable $e) {
            $messages[] = 'Delete failed: ' . $e->getMessage();
        }
    } elseif ($action === 'clear') {
        try {
            $pdo->exec("DELETE FROM cart WHERE user_id = $userId");
            $messages[] = 'Cart cleared.';
        } catch (Throwable $e) {
            $messages[] = 'Clear failed: ' . $e->getMessage();
        }
    }
}

$cart = fetchCart($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Cart Overview</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/commerce/store.css">
    <style>
        .cart-wrapper { max-width: 960px; margin: 0 auto; padding: 24px; }
        .cart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .cart-items { display: flex; flex-direction: column; gap: 16px; }
        .cart-card { display: flex; gap: 16px; border: 1px solid #ddd; border-radius: 12px; padding: 16px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .cart-card img { width: 120px; height: 120px; object-fit: cover; border-radius: 8px; }
        .cart-info { flex: 1; }
        .cart-info h3 { margin: 0 0 8px; font-size: 20px; }
        .cart-meta { color: #555; margin-bottom: 12px; }
        .cart-actions { display: flex; gap: 12px; align-items: center; }
        .cart-actions form { display: flex; gap: 8px; align-items: center; }
        .cart-actions input[type=number] { width: 72px; padding: 6px 8px; }
        .cart-summary { margin-top: 32px; padding: 20px; border-radius: 12px; background: #f7f7f7; display: flex; justify-content: space-between; align-items: center; }
        .cart-buttons { display: flex; gap: 12px; }
        .cart-buttons button, .cart-actions button { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-primary { background: #00704A; color: #fff; }
        .btn-secondary { background: #f1f1f1; color: #333; }
        .btn-danger { background: #c0392b; color: #fff; }
        .flash-box { border: 1px solid #f39c12; background: rgba(243, 156, 18, 0.12); padding: 12px 16px; margin-bottom: 12px; border-radius: 8px; }
        .empty-cart { text-align: center; padding: 48px 24px; border: 2px dashed #ccc; border-radius: 12px; background: #fff; }
        @media (max-width: 680px) {
            .cart-card { flex-direction: column; align-items: center; text-align: center; }
            .cart-actions { flex-direction: column; }
            .cart-summary { flex-direction: column; gap: 16px; }
        }
    </style>
</head>
<body>
<?php include '../header.php'; ?>

<div class="cart-wrapper">
    <div class="cart-header">
        <h1>Shopping Cart</h1>
        <div>
            <button class="btn-secondary" onclick="location.href='/commerce/store.php'">Continue shopping</button>
        </div>
    </div>

    <?php foreach ($messages as $message): ?>
        <div class="flash-box"><?= $message ?></div>
    <?php endforeach; ?>

    <?php if (!$cart['items']): ?>
        <div class="empty-cart">
            <h2>Your cart is empty</h2>
            <p>Add something from the store to see it here.</p>
        </div>
    <?php else: ?>
        <div class="cart-items">
            <?php foreach ($cart['items'] as $item): ?>
                <?php $imagePath = $item['image_url'] ?: '/assets/images/americano.jpg'; ?>
                <div class="cart-card">
                    <img src="<?= $imagePath ?>" alt="<?= $item['name'] ?>">
                    <div class="cart-info">
                        <h3><?= $item['name'] ?></h3>
                        <div class="cart-meta">
                            <div>Unit price: ₩<?= number_format($item['price']) ?></div>
                            <div>Subtotal: ₩<?= number_format($item['subtotal']) ?></div>
                        </div>
                        <div class="cart-actions">
                            <form method="post">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                <label>Qty
                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1">
                                </label>
                                <button type="submit" class="btn-secondary">Update</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                <button type="submit" class="btn-danger">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <div>
                <div><strong>Total items:</strong> <?= $cart['count'] ?></div>
                <div><strong>Total amount:</strong> ₩<?= number_format($cart['total']) ?></div>
            </div>
            <div class="cart-buttons">
                <form method="post">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn-secondary">Clear cart</button>
                </form>
                <form method="get" action="/commerce/payment.php">
                    <button type="submit" class="btn-primary">Proceed to payment</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
