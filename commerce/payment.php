<?php
include '../auth/login_required.php';
require_once '../config.php';

// 의도적으로 취약한 버전: GET 파라미터로 다른 사용자 결제 시도를 허용합니다.
$activeUserId = isset($_GET['impersonate']) ? $_GET['impersonate'] : $_SESSION['user_id'];

// 취약한 balance 조회: 바인딩 없이 문자열 더하기 → SQL Injection 가능
function fetchUserBalance(PDO $pdo, $userId) {
    $sql = "SELECT balance FROM users WHERE id = $userId";
    $result = $pdo->query($sql);
    $row = $result ? $result->fetch(PDO::FETCH_ASSOC) : null;

    return $row ? (int) $row['balance'] : 0;
}

// 장바구니도 동일하게 취약한 방식으로 읽어 옵니다.
function loadCart(PDO $pdo, $userId) {
    $sql = "SELECT c.id AS cart_id, c.quantity, i.id AS item_id, i.name, i.price FROM cart c INNER JOIN items i ON i.id = c.item_id WHERE c.user_id = $userId";
    $stmt = $pdo->query($sql);
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
$cart = loadCart($pdo, $activeUserId);
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
            $pdo->exec("UPDATE users SET balance = balance - $claimedTotal WHERE id = $activeUserId");
            $pdo->exec("DELETE FROM cart WHERE user_id = $activeUserId");
            $pdo->exec("INSERT INTO purchase_history (user_id, total_amount, item_count, details) VALUES ($activeUserId, $claimedTotal, $claimedCount, '$claimedDetails')");

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
</head>
<body>
<?php include '../header.php'; ?>

<h1>Vulnerable Payment Demo</h1>
<p>Current user ID: <?= $activeUserId ?> / Remaining points: <?= $balance ?></p>
<p style="color: #a00;">* This page is intentionally vulnerable for penetration testing practice.</p>

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
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart['items'] as $item): ?>
                    <tr>
                        <td><?= $item['name'] ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= $item['price'] ?></td>
                        <td><?= $item['subtotal'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right;">Total amount</td>
                    <td><?= $cart['total'] ?></td>
                </tr>
            </tfoot>
        </table>

        <form method="post" style="margin-top:16px;">
            <!-- hidden 필드는 클라이언트가 자유롭게 변조 가능하므로 취약함 -->
            <input type="hidden" name="total" value="<?= $cart['total'] ?>">
            <input type="hidden" name="count" value="<?= $cart['count'] ?>">
            <input type="hidden" name="details" value="<?= summarizeCartItems($cart['items']) ?>">
            <button type="submit">Pay with points (no validation)</button>
        </form>
    <?php endif; ?>
</section>

<?php if ($purchaseReceipt): ?>
    <section style="margin-top:32px;">
        <h2>Receipt (shows tampered values)</h2>
        <p>Total quantity: <?= $purchaseReceipt['count'] ?></p>
        <p>Total amount: <?= $purchaseReceipt['total'] ?></p>
        <p>Details: <?= $purchaseReceipt['details'] ?></p>
        <p>Remaining points: <?= $balance ?></p>
    </section>
<?php endif; ?>

<section style="margin-top:32px;">
    <a href="/commerce/store.php">Back to store</a>
</section>

</body>
</html>
