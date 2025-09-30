<?php
include '../auth/login_required.php';
require_once '../config.php';

if (!function_exists('html_escape')) {
    function html_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!defined('MAX_CART_QUANTITY')) {
    define('MAX_CART_QUANTITY', 99);
}

function normalizeQuantity($value): int
{
    $quantity = filter_var(
        $value,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
                'max_range' => MAX_CART_QUANTITY,
            ],
        ]
    );

    if ($quantity === false) {
        throw new InvalidArgumentException('Quantity must be an integer between 1 and ' . MAX_CART_QUANTITY . '.');
    }

    return $quantity;
}

$userId = (int) $_SESSION['user_id'];
$messages = [];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function fetchCart(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT c.id AS cart_id, c.product_id, c.quantity, p.name, p.price, p.image_url
         FROM cart c
         LEFT JOIN products p ON p.id = c.product_id
         WHERE c.user_id = :user_id
         ORDER BY c.id DESC'
    );
    $stmt->execute([':user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $total = 0.0;
    $count = 0;

    foreach ($rows as &$row) {
        $price = isset($row['price']) ? (float) $row['price'] : 0.0;
        $quantity = isset($row['quantity']) ? (int) $row['quantity'] : 0;
        $row['quantity'] = $quantity;
        $row['subtotal'] = $price * $quantity;
        $total += $row['subtotal'];
        $count += $quantity;
    }

    return [
        'items' => $rows,
        'total' => $total,
        'count' => $count,
    ];
}

function upsertCartItem(PDO $pdo, int $userId, int $productId, $qty): string
{
    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid product selection.');
    }

    $quantity = normalizeQuantity($qty);

    $productStmt = $pdo->prepare('SELECT id FROM products WHERE id = :product_id');
    $productStmt->execute([':product_id' => $productId]);
    if (!$productStmt->fetchColumn()) {
        throw new InvalidArgumentException('The requested product does not exist.');
    }

    $checkStmt = $pdo->prepare('SELECT quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id');
    $checkStmt->execute([
        ':user_id' => $userId,
        ':product_id' => $productId,
    ]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $existing = (int) $row['quantity'];
        $newQty = $existing + $quantity;
        if ($newQty > MAX_CART_QUANTITY) {
            $newQty = MAX_CART_QUANTITY;
        }
        $updateStmt = $pdo->prepare(
            'UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id'
        );
        $updateStmt->execute([
            ':quantity' => $newQty,
            ':user_id' => $userId,
            ':product_id' => $productId,
        ]);

        return $newQty === $existing
            ? 'Quantity already at maximum allowed.'
            : 'Quantity updated.';
    }

    $insertStmt = $pdo->prepare(
        'INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)'
    );
    $insertStmt->execute([
        ':user_id' => $userId,
        ':product_id' => $productId,
        ':quantity' => $quantity,
    ]);

    return 'Item added to cart.';
}

$isJson = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isJson) {
        $payload = json_decode(file_get_contents('php://input'), true);

        if (!is_array($payload)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
            exit;
        }

        $action = $payload['action'] ?? null;
        $productId = isset($payload['product_id'])
            ? filter_var($payload['product_id'], FILTER_VALIDATE_INT)
            : 0;
        $quantityParam = $payload['quantity'] ?? null;

        $response = ['success' => false, 'message' => 'Unsupported action'];

        if ($action === 'add' && $productId) {
            try {
                $response['message'] = upsertCartItem($pdo, $userId, $productId, $quantityParam);
                $response['success'] = true;
            } catch (Throwable $e) {
                $response['message'] = $e->getMessage();
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $submittedToken)) {
        http_response_code(400);
        exit('Invalid request token provided.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $cartId = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT) ?: 0;
        try {
            $quantity = normalizeQuantity($_POST['quantity'] ?? null);
            $updateStmt = $pdo->prepare(
                'UPDATE cart SET quantity = :quantity WHERE id = :cart_id AND user_id = :user_id'
            );
            $updateStmt->execute([
                ':quantity' => $quantity,
                ':cart_id' => $cartId,
                ':user_id' => $userId,
            ]);

            $messages[] = $updateStmt->rowCount() ? 'Quantity updated.' : 'No matching cart item found.';
        } catch (InvalidArgumentException $e) {
            $messages[] = 'Update failed: ' . $e->getMessage();
        } catch (Throwable $e) {
            $messages[] = 'Update failed: An unexpected error occurred.';
        }
    } elseif ($action === 'remove') {
        $cartId = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT) ?: 0;
        try {
            $deleteStmt = $pdo->prepare('DELETE FROM cart WHERE id = :cart_id AND user_id = :user_id');
            $deleteStmt->execute([
                ':cart_id' => $cartId,
                ':user_id' => $userId,
            ]);
            $messages[] = $deleteStmt->rowCount() ? 'Item removed.' : 'No matching cart item found.';
        } catch (Throwable $e) {
            $messages[] = 'Delete failed: An unexpected error occurred.';
        }
    } elseif ($action === 'clear') {
        try {
            $clearStmt = $pdo->prepare('DELETE FROM cart WHERE user_id = :user_id');
            $clearStmt->execute([':user_id' => $userId]);
            $messages[] = $clearStmt->rowCount() ? 'Cart cleared.' : 'Cart already empty.';
        } catch (Throwable $e) {
            $messages[] = 'Clear failed: An unexpected error occurred.';
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
        <div class="flash-box"><?= html_escape($message) ?></div>
    <?php endforeach; ?>

    <?php if (!$cart['items']): ?>
        <div class="empty-cart">
            <h2>Your cart is empty</h2>
            <p>Add something from the store to see it here.</p>
        </div>
    <?php else: ?>
        <div class="cart-items">
            <?php foreach ($cart['items'] as $item): ?>
                <?php
                    $imagePathRaw = $item['image_url'] ?? '';
                    $imagePath = '/assets/images/americano.jpg';
                    if (is_string($imagePathRaw) && $imagePathRaw !== '' && strpos($imagePathRaw, '/') === 0) {
                        $imagePath = $imagePathRaw;
                    }
                ?>
                <div class="cart-card">
                    <img src="<?= html_escape($imagePath) ?>" alt="<?= html_escape($item['name'] ?? 'Product image') ?>">
                    <div class="cart-info">
                        <h3><?= html_escape($item['name'] ?? 'Unnamed product') ?></h3>
                        <div class="cart-meta">
                            <div>Unit price: ₩<?= number_format((float) $item['price']) ?></div>
                            <div>Subtotal: ₩<?= number_format((float) $item['subtotal']) ?></div>
                        </div>
                        <div class="cart-actions">
                            <form method="post">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="cart_id" value="<?= (int) $item['cart_id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= html_escape($csrfToken) ?>">
                                <label>Qty
                                    <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" max="<?= MAX_CART_QUANTITY ?>">
                                </label>
                                <button type="submit" class="btn-secondary">Update</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="cart_id" value="<?= (int) $item['cart_id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= html_escape($csrfToken) ?>">
                                <button type="submit" class="btn-danger">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <div>
                <div><strong>Total items:</strong> <?= (int) $cart['count'] ?></div>
                <div><strong>Total amount:</strong> ₩<?= number_format((float) $cart['total']) ?></div>
            </div>
            <div class="cart-buttons">
                <form method="post">
                    <input type="hidden" name="action" value="clear">
                    <input type="hidden" name="csrf_token" value="<?= html_escape($csrfToken) ?>">
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
