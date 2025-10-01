<?php
    include '../auth/login_required.php';
    require_once '../config.php';

    $username = $_SESSION['username'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $pdo->query($sql);
    $profile = $result->fetch();
?>


<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="/style.css">

    </head>
        <a href="../index.php">Main</a>
    <div>
        <?php if($profile['profile']): ?>
            <img src="profile/<?=$profile['profile']; ?>" alt="<?=$profile['profile'];?>">
        <?php else: ?>
            <img src="" alt="no image">
        <?php endif; ?>
    </div>
    <div>
        <p>name : <?php echo $profile['username']; ?></p>
    </div>
    <div>
        <p>email : <?php echo $profile['email']; ?></p>
    </div>
    <div>
        <p>point : <?php echo $profile['balance']; ?></p>
    </div>
    <a href="profile_modify.php">Edit</a>
    <a href="/auth/reset_password.php">Reset Password</a>

</html>