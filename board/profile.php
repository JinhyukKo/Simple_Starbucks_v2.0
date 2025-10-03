<?php
    include '../auth/login_required.php';
    require_once '../config.php';

    $username = $_SESSION['username'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $profile = $stmt->fetch();
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        :root {
            --sb-green: #006241;
            --sb-light-green: #d4e9e2;
            --sb-gold: #cba258;
            --sb-dark: #1e3932;
            --sb-light: #f9f9f9;
            --sb-white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--sb-light);
            background-image: linear-gradient(to bottom, var(--sb-light-green) 0%, var(--sb-light) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--sb-white);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 40px;
        }

        .nav-link {
            display: inline-block;
            color: var(--sb-green);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 20px;
            margin-bottom: 30px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: var(--sb-light-green);
        }

        h1 {
            color: var(--sb-green);
            margin-bottom: 30px;
            font-size: 2em;
            text-align: center;
            font-weight: 700;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--sb-light-green);
        }

        .profile-image-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--sb-green);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .no-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--sb-light-green);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--sb-green);
            border: 4px solid var(--sb-green);
        }

        .profile-info {
            background: var(--sb-light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--sb-dark);
            min-width: 100px;
            text-transform: capitalize;
        }

        .info-value {
            color: #555;
            flex: 1;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-primary {
            background-color: var(--sb-green);
            color: var(--sb-white);
        }

        .btn-primary:hover {
            background-color: var(--sb-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--sb-gold);
            color: var(--sb-white);
        }

        .btn-secondary:hover {
            background-color: #b89248;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../index.php" class="nav-link">← Main</a>

        <h1>👤 My Profile</h1>

        <div class="profile-image-container">
            <?php if($profile['profile']): ?>
                <img src="profile/<?=$profile['profile']; ?>" alt="Profile Picture" class="profile-image">
            <?php else: ?>
                <div class="no-image">👤</div>
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <div class="info-item">
                <span class="info-label">Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($profile['username']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($profile['email']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Point:</span>
                <span class="info-value"><?php echo htmlspecialchars($profile['balance']); ?></span>
            </div>
        </div>

        <div class="action-buttons">
            <a href="profile_modify.php" class="btn btn-primary">✏️ Edit Profile</a>
            <a href="/auth/reset_password.php" class="btn btn-secondary">🔑 Reset Password</a>
        </div>
    </div>
</body>
</html>