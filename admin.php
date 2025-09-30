<?php
require 'config.php';


// 상품 추가 처리
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_product"])) {
    $name = $_POST["name"];
    $description = $_POST["description"];
    $price = $_POST["price"];
    $category = $_POST["category"];
    $image_url = $_POST["image_url"];
    
    $sql = "INSERT INTO products (name, description, price, category, image_url) 
            VALUES ('$name', '$description', $price, '$category', '$image_url')";
    
        try {
            $stmt = $pdo->query($sql);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
}

// 상품 삭제 처리
if (isset($_GET["delete_id"])) {
    $delete_id = $_GET["delete_id"];
    $sql = "DELETE FROM products WHERE id=$delete_id";
    
    if ($pdo->query($sql) === TRUE) {
        $message = "상품이 성공적으로 삭제되었습니다.";
    } else {
        $error = "오류: " . $sql . "<br>". $pdo->errorInfo();
    }
}

// 상품 목록 가져오기
$sql = "SELECT * FROM products ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$result = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상품 관리 어드민</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            padding: 30px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            border: none;
            border-radius: 5px;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .alert {
            border: none;
            border-radius: 10px;
        }
        .stats-card {
            text-align: center;
            padding: 20px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .form-control, .form-select {
            border-radius: 5px;
            padding: 10px 15px;
            border: 1px solid #e1e5eb;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(38, 117, 252, 0.25);
            border-color: #2575fc;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 사이드바 -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="d-flex flex-column p-3">
                    <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <i class="fas fa-store fa-2x me-2"></i>
                        <span class="fs-4">관리자 패널</span>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <i class="fas fa-box"></i>
                                상품 관리
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link">
                                <i class="fas fa-tags"></i>
                                카테고리
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link">
                                <i class="fas fa-chart-bar"></i>
                                통계
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link">
                                <i class="fas fa-cog"></i>
                                설정
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://github.com/mdo.png" alt="" width="32" height="32" class="rounded-circle me-2">
                            <strong>관리자</strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="#">프로필</a></li>
                            <li><a class="dropdown-item" href="#">설정</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">로그아웃</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 메인 콘텐츠 -->
            <div class="col-md-9 col-lg-10 ml-sm-auto main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">상품 관리</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus me-1"></i> 상품 추가
                    </button>
                </div>

                <!-- 알림 메시지 -->
                <?php if(isset($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- 통계 카드 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h3 class="stats-number text-primary"><?php echo count($result); ?></h3>
                                <p class="stats-label">전체 상품</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h3 class="stats-number text-success">24</h3>
                                <p class="stats-label">오늘 등록</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h3 class="stats-number text-warning">5</h3>
                                <p class="stats-label">재고 부족</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h3 class="stats-number text-info">1,234</h3>
                                <p class="stats-label">총 판매량</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 상품 테이블 -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list me-2"></i> 상품 목록
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>이미지</th>
                                        <th>상품명</th>
                                        <th>설명</th>
                                        <th>가격</th>
                                        <th>카테고리</th>
                                        <th>등록일</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($result)> 0): ?>
                                        <?php foreach($result as $row): ?>
                                            <tr>
                                                <td><?php echo $row["id"]; ?></td>
                                                <td>
                                                    <?php if($row["image_url"]): ?>
                                                        <img src="<?php echo $row["image_url"]; ?>" class="product-img" alt="상품 이미지">
                                                    <?php else: ?>
                                                        <div class="product-img bg-light d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $row["name"]; ?></td>
                                                <td><?php echo substr($row["description"], 0, 50) . '...'; ?></td>
                                                <td><?php echo number_format($row["price"]); ?>원</td>
                                                <td><span class="badge bg-secondary"><?php echo $row["category"]; ?></span></td>
                                                <td><?php echo date("Y-m-d", strtotime($row["created_at"])); ?></td>
                                                <td>
                                                    <a href="?delete_id=<?php echo $row["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('정말로 이 상품을 삭제하시겠습니까?')">
                                                        <i class="fas fa-trash"></i> 삭제
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">등록된 상품이 없습니다.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 상품 추가 모달 -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">상품 추가</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">상품명</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">가격</label>
                                <input type="number" class="form-control" id="price" name="price" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">상품 설명</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">카테고리</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" selected disabled>카테고리 선택</option>
                                    <option value="bakery">bakery</option>
                                    <option value="coffee">coffee</option>
                                    <option value="giftCard">giftCard</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="image_url" class="form-label">이미지 URL</label>
                                <input type="text" class="form-control" id="image_url" name="image_url">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" name="add_product" class="btn btn-primary">상품 추가</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
?>