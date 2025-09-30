<?php
include './auth/login_required.php';
require_once './config.php';
include "./header.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Starbucks</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

    <div class="main-container">
        <div class="welcome-section">
            <h1 class="welcome-title">Welcome to Simple Starbucks</h1>
            <p class="welcome-subtitle">Your daily dose of premium coffee experience</p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-greeting">
                    Hello, <?php echo $_SESSION['username']; ?>!
                </div>
                <p class="welcome-subtitle">How's your day today?</p>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="nav-cards">
                <div class="nav-card">
                    <div class="card-icon">🛍️</div>
                    <h2 class="card-title">Store</h2>
                    <p class="card-description">Browse our premium selection of coffee, teas and merchandise</p>
                    <a href="/commerce/store.php" class="card-button">Visit Store</a>
                </div>
                
                <div class="nav-card">
                    <div class="card-icon">📋</div>
                    <h2 class="card-title">Board</h2>
                    <p class="card-description">Check announcements, promotions and community updates</p>
                    <a href="/board/board.php" class="card-button">View Board</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>&copy; <?php echo date("Y"); ?> Simple Starbucks. All rights reserved.</p>
    </div>
</body>
</html>