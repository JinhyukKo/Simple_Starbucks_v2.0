<?php
    include '../auth/login_required.php';
    require_once '../config.php';
    include '../header.php';

    $username = $_SESSION['username'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $profile = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $user_id=$profile['id'];
        $email=$_POST['email'];
        if(!empty($_POST['password'])){
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);   
        }else{
            $password=$profile['password'];
        }
        $filename = '';

        if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK){
            $filename = $_FILES['upload']['name'];
            $tmp_name = $_FILES['upload']['tmp_name'];
            move_uploaded_file($tmp_name, "profile/" . $filename);
        }

        
        $sql = "UPDATE users SET email = ?, password = ?, profile = ? WHERE id = ?";
        $stmtUp = $pdo->prepare($sql);
        $stmtUp->execute([$email, $password, $filename, $user_id]);

        session_unset();
        header("Location: board.php");
        exit();
    }
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
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
            margin-bottom: 15px;
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
            margin-bottom: 15px;
        }

        .file-upload-wrapper {
            display: inline-block;
        }

        .file-upload-label {
            display: inline-block;
            padding: 8px 20px;
            background-color: var(--sb-light-green);
            color: var(--sb-dark);
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .file-upload-label:hover {
            background-color: #c0dfd5;
        }

        input[type="file"] {
            display: none;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: var(--sb-dark);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: var(--sb-green);
            box-shadow: 0 0 0 2px rgba(0, 98, 65, 0.1);
        }

        .readonly-field {
            background-color: var(--sb-light);
            color: #666;
            cursor: not-allowed;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1em;
            text-align: center;
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
            background-color: #e0e0e0;
            color: var(--sb-dark);
        }

        .btn-secondary:hover {
            background-color: #d0d0d0;
        }

        .hint {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✏️ Edit Profile</h1>

        <form method="post" enctype="multipart/form-data">
            <div class="profile-image-container">
                <?php if($profile['profile']): ?>
                    <img src="profile/<?=$profile['profile']; ?>" alt="Profile Picture" class="profile-image">
                <?php else: ?>
                    <div class="no-image">👤</div>
                <?php endif; ?>
                <br>
                <div class="file-upload-wrapper">
                    <label for="file-upload" class="file-upload-label">📷 Change Profile Picture</label>
                    <input type="file" id="file-upload" name="upload" accept="image/*">
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($profile['username']); ?>" class="readonly-field" readonly>
                <p class="hint">Username cannot be changed</p>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
                <p class="hint">Only fill this if you want to change your password</p>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="board.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>