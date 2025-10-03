<?php
include '../auth/login_required.php';
require_once '../config.php';

// 의도적으로 취약한 버전: GET 파라미터로 다른 사용자 결제 시도를 허용합니다.
$activeUserId = isset($_GET['impersonate']) ? (int)$_GET['impersonate'] : $_SESSION['user_id'];

// 취약한 balance 조회: 바인딩 없이 문자열 더하기 → SQL Injection 가능
function fetchUserBalance(PDO $pdo, $userId) {
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

    return $row ? (int) $row['balance'] : 0;
}

// 장바구니도 동일하게 취약한 방식으로 읽어 옵니다.
function loadCart(PDO $pdo, $userId) {
    $stmt = $pdo->prepare("SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image_url FROM cart c INNER JOIN products p ON p.id = c.product_id WHERE c.user_id = ?");
    $stmt->execute([$userId]);
    $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $total = 0;
    $itemCount = 0;

    foreach ($items as &$item) {
        $item['subtotal'] = $item['price'] * $item['quantity'];
        $total += $item['subtotal'];
        $itemCount += $item['quantity'];
    }

    return [
        'items' => $items,
        'total' => $total,
        'count' => $itemCount,
    ];
}

// 영수증 텍스트를 만듭니다. (출력 인코딩 없이 그대로 사용)
function summarizeCartItems(array $items) {
    if (!$items) {
        return 'empty cart';
    }

    $chunks = [];
    foreach ($items as $item) {
        $chunks[] = $item['name'] . ' x' . $item['quantity'] . ' = ' . $item['subtotal'];
    }

    return implode(', ', $chunks);
}

$balance = fetchUserBalance($pdo, $activeUserId);

// store.php에서 직접 구매 시 URL 파라미터로 상품 정보를 받음
if (isset($_GET['product_id']) && isset($_GET['quantity'])) {
    $productId = (int)$_GET['product_id'];
    $quantity = (int)$_GET['quantity'];

    // 상품 정보 조회
    $stmt = $pdo->prepare("SELECT id AS product_id, name, price, image_url FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

    if ($product) {
        $product['quantity'] = $quantity;
        $product['subtotal'] = $product['price'] * $quantity;

        $cart = [
            'items' => [$product],
            'total' => $product['subtotal'],
            'count' => $quantity,
        ];
    } else {
        $cart = loadCart($pdo, $activeUserId);
    }
} else {
    $cart = loadCart($pdo, $activeUserId);
}

$errors = [];
$successMessage = null;
$purchaseReceipt = null;

// 모의해킹 시나리오: 결과를 DB에 남기되 안전장치 없이 만듭니다.
$pdo->exec("CREATE TABLE IF NOT EXISTS purchase_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount INT NOT NULL,
    item_count INT NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hidden input을 그대로 신뢰하여 총액/수량 위조가 가능합니다.
    $claimedTotal = isset($_POST['total']) ? $_POST['total'] : $cart['total'];
    $claimedCount = isset($_POST['count']) ? $_POST['count'] : $cart['count'];
    $claimedDetails = isset($_POST['details']) ? $_POST['details'] : summarizeCartItems($cart['items']);

    if ($claimedTotal <= 0) {
        $errors[] = 'Total amount must be greater than zero. Please check your input.';
    } elseif ($balance < $claimedTotal) {
        // 부족 여부만 체크하므로 total 값을 낮추면 결제가 성사됩니다.
        $errors[] = 'Not enough points. (This message can also be tampered with.)';
    } else {
        try {
            // 트랜잭션 없이 순차 실행 → 중간 실패 시 데이터 불일치 발생 가능
            $stmtUpd = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmtUpd->execute([$claimedTotal, $activeUserId]);
            $stmtClr = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmtClr->execute([$activeUserId]);
            $stmtIns = $pdo->prepare("INSERT INTO purchase_history (user_id, total_amount, item_count, details) VALUES (?, ?, ?, ?)");
            $stmtIns->execute([$activeUserId, $claimedTotal, $claimedCount, $claimedDetails]);

            $successMessage = 'Payment completed. (Processed without verification)';
            $purchaseReceipt = [
                'items' => $cart['items'],
                'total' => $claimedTotal,
                'count' => $claimedCount,
                'details' => $claimedDetails,
            ];

            // 실제 잔액은 위조된 total 로 계산되므로 재조회 시 음수 가능
            $balance = fetchUserBalance($pdo, $activeUserId);
            $cart = loadCart($pdo, $activeUserId);
        } catch (Throwable $e) {
            $errors[] = 'Payment failed: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Vulnerable Payment Page</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/commerce/store.css">
    <style>
        .payment-wrapper { max-width: 960px; margin: 0 auto; padding: 24px; }
        .payment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .payment-grid { display: flex; flex-direction: column; gap: 16px; }
        .payment-card { display: flex; gap: 16px; border: 1px solid #ddd; border-radius: 12px; padding: 16px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .payment-card img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; }
        .payment-info { flex: 1; }
        .payment-info h3 { margin: 0 0 8px; font-size: 18px; }
        .payment-meta { color: #555; margin-bottom: 8px; }
        .payment-summary { margin-top: 24px; padding: 20px; border-radius: 12px; background: #f7f7f7; display: flex; justify-content: space-between; align-items: center; }
        .btn-primary { background: #00704A; color: #fff; padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-secondary { background: #f1f1f1; color: #333; padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; }
        @media (max-width: 680px) {
            .payment-card { flex-direction: column; align-items: center; text-align: center; }
            .payment-summary { flex-direction: column; gap: 16px; }
        }
    </style>
</head>
<body>
<?php include '../header.php'; ?>

<div class="payment-wrapper">
    <div class="payment-header">
        <h1>Checkout</h1>
        <div>
            <button class="btn-secondary" onclick="location.href='/commerce/cart.php'">Back to cart</button>
        </div>
    </div>
    <p>Current user ID: <?= $activeUserId ?> / Remaining points: <?= $balance ?></p>
<?php if ($errors): ?>
    <div style="color:red;">
        <?php foreach ($errors as $error): ?>
            <p><?= $error ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($successMessage): ?>
    <div style="color:green;">
        <p><?= $successMessage ?></p>
    </div>
<?php endif; ?>

<section>
    <h2>Order Summary (no output encoding)</h2>
    <?php if (!$cart['items']): ?>
        <p>The cart is empty.</p>
    <?php else: ?>
        <div class="payment-grid">
            <?php foreach ($cart['items'] as $item): ?>
                <?php $imagePath = $item['image_url'] ?: '/assets/images/americano.jpg'; ?>
                <div class="payment-card">
                    <img src="<?= $imagePath ?>" alt="<?= $item['name'] ?>">
                    <div class="payment-info">
                        <h3><?= $item['name'] ?></h3>
                        <div class="payment-meta">
                            <div>Quantity: <?= $item['quantity'] ?></div>
                            <div>Unit price: ₩<?= number_format($item['price']) ?></div>
                        </div>
                        <div><strong>Subtotal:</strong> ₩<?= number_format($item['subtotal']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="payment-summary">
            <div>
                <div><strong>Total quantity:</strong> <?= $cart['count'] ?></div>
                <div><strong>Total amount:</strong> ₩<?= number_format($cart['total']) ?></div>
            </div>
            <form method="post">
                <!-- hidden 필드는 클라이언트가 자유롭게 변조 가능하므로 취약함 -->
                <input type="hidden" name="total" value="<?= $cart['total'] ?>">
                <input type="hidden" name="count" value="<?= $cart['count'] ?>">
                <input type="hidden" name="details" value="<?= summarizeCartItems($cart['items']) ?>">
                <button type="submit" class="btn-primary">Pay with points (no validation)</button>
            </form>
        </div>
    <?php endif; ?>
</section>

<?php if ($purchaseReceipt): ?>
    <section style="margin-top:32px;">
        <h2>Receipt</h2>
        <p>Total quantity: <?= $purchaseReceipt['count'] ?></p>
        <p>Total amount: <?= $purchaseReceipt['total'] ?></p>
        <p>Details: <?= $purchaseReceipt['details'] ?></p>
        <p>Remaining points: <?= $balance ?></p>
    </section>
<?php endif; ?>

    <section style="margin-top:32px;">
        <a href="/commerce/store.php">Back to store</a>
    </section>
</div>
</body>
</html>
