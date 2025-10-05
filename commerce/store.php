<?php
require_once '../auth/login_required.php';
require_once '../config.php';
include "../header.php";
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>simple starbucks store</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="store.css">
</head>
<body>
    <div class="store-container">
        <h1>Simple Starbucks Store</h1>

        <?php
        // 카테고리 순서 정의
        $categories = [
            'coffee' => 'Coffee & Beverage',
            'bakery' => 'Bakery',
            'giftCard' => 'Gift Card'
        ];

        foreach ($categories as $category_key => $category_name) {
            try {
                // $stmt = $pdo->query("SELECT * FROM products WHERE category = '$category_key' ORDER BY id");
                // $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // sql injection - prepared statement
                $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY id");
                $stmt->execute([$category_key]);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo "<div class='category-section'>";
                echo "<h2 class='category-title'>" . $category_name . "</h2>";
                
                if (empty($products)) {
                    echo "<div class='no-product'>해당 카테고리에 상품이 없습니다.</div>";
                } else {
                    echo "<div class='product-grid'>";
                    
                    foreach ($products as $product) {
                        $image_path = $product['image_url'];
                        
                        echo "<div class='product-card'"
                            . " data-id='" . (int)$product['id'] . "'"
                            . " data-name=\"" . htmlspecialchars($product['name'], ENT_QUOTES) . "\""
                            . " data-desc=\"" . htmlspecialchars($product['description'] ?? '', ENT_QUOTES) . "\""
                            . " data-price='" . (int)$product['price'] . "'"
                            . " data-image=\"" . htmlspecialchars($image_path, ENT_QUOTES) . "\">";
                        
                        // 상품 이미지
                        echo "<img src='" . $image_path . "' alt='" . htmlspecialchars($product['name']) . "' class='product-image'>";
                        
                        // 상품 정보
                        echo "<h3 class='product-name'>" . htmlspecialchars($product['name']) . "</h3>";
                        
                        if (!empty($product['description'])) {
                            echo "<p class='product-description'>" . htmlspecialchars($product['description']) . "</p>";
                        }
                        
                        echo "<div class='product-price'>₩" . number_format($product['price']) . "</div>";
                        
                        echo "</div>";
                    }
                    
                    echo "</div>";
                }
                
                echo "</div>";
            } catch (PDOException $e) {
                echo "<div class='category-section'>";
                echo "<h2 class='category-title'>" . $category_name . "</h2>";
                echo "<div class='no-product'>오류가 발생했습니다: " . $e->getMessage() . "</div>";
                echo "</div>";
            }
        }
        ?>
        <div class="toast-container" id="toastContainer"></div>
        
        <!-- 상품 선택 모달 -->
        <div id="productModal" class="product-modal">
            <div class='modal-content'>
                <span class="close" onclick="closeModal()">&times;</span>
                <h2 class="modal-title" id="modalProductName"></h2>
                <img id="modalProductImage" class="modal-image" src="" alt="">
                <p id="modalProductDescription"></p>
                <div class="quantity-selector">
                    <label class="quantity-label" for="quantity">quantity:</label>
                    <input type="number" id="quantity" class="quantity-input" min="1" max="10" value="1">
                </div>
                <div class="btn-group">
                    <button class="btn btn-cart" onclick="addToCart()">add cart</button>
                    <button class="btn btn-payment" onclick="goToPayment()">proceed to purchase</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedProductData = null;
        // 카드 클릭 핸들러 등록
        document.addEventListener('DOMContentLoaded', function() {
            var cards = document.querySelectorAll('.product-card');
            cards.forEach(function(card) {
                card.addEventListener('click', function() {
                    var id = parseInt(card.getAttribute('data-id'));
                    var name = card.getAttribute('data-name') || '';
                    var desc = card.getAttribute('data-desc') || '';
                    var price = parseInt(card.getAttribute('data-price')) || 0;
                    var image = card.getAttribute('data-image') || '';
                    selectedProduct(id, name, desc, price, image);
                });
            });
        });

        function selectedProduct(id, name, description, price, image) {
            selectedProductData = {
                id: id,
                name: name,
                description: description,
                price: price,
                image: image
            };
            
            document.getElementById('modalProductName').textContent = name;
            document.getElementById('modalProductImage').src = image;
            document.getElementById('modalProductDescription').textContent = description;
            document.getElementById('quantity').value = 1;
            
            document.getElementById('productModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
            document.body.style.overflow = '';
            selectedProductData = null;
        }

        function showToast(message, type) {
            var container = document.getElementById('toastContainer');
            var el = document.createElement('div');
            el.className = 'toast ' + (type || 'success');
            el.textContent = message;
            container.appendChild(el);
            requestAnimationFrame(function(){ el.classList.add('show'); });
            setTimeout(function(){
                el.classList.remove('show');
                setTimeout(function(){ container.removeChild(el); }, 200);
            }, 2200);
        }

        function addToCart() {
            if (!selectedProductData) return;
            
            const quantity = parseInt(document.getElementById('quantity').value);
            if (quantity < 1) {
                alert('수량은 1개 이상이어야 합니다.');
                return;
            }

            fetch('cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    product_id: selectedProductData.id,
                    quantity
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(selectedProductData.name + ' ' + quantity + ' added to cart', 'success');
                    closeModal();
                } else {
                    showToast('error: ' + (data.message || 'unknown'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('wrong connection', 'error');
            });
        }

        function goToPayment() {
            if (!selectedProductData) return;
            
            const quantity = parseInt(document.getElementById('quantity').value);
            if (quantity < 1) {
                alert('more than one');
                return;
            }

            // 바로 결제로 이동 (결제 페이지에 상품 정보 전달)
            const params = new URLSearchParams();
            params.append('product_id', selectedProductData.id);
            params.append('quantity', quantity);
            
            window.location.href = 'payment.php?' + params.toString();
        }

        // 모달 외부 클릭 시 닫기
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('productModal');
                if (modal && modal.style.display === 'block') {
                    closeModal();
                }
            }
        });
    </script>
        <div class="bottom-actions">
            <a href="cart.php" style="background: #00a040; color: white; padding: 10px 20px; text-decoration: none; border-radius: 20px;">
                 CART
            </a>
        </div>
</body>
</html>