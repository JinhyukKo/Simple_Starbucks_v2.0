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

    <div>
        <?php if($profile['profile']): ?>
            <img src="profile/<?=$profile['profile']; ?>" alt="<?=$profile['profile'];?>">
        <?php else: ?>
            <img src="" alt="no image">
        <?php endif; ?>
    </div>
    <div>
        <p>name:<?php echo $profile['username']; ?></p>
    </div>
    <div>
        <p>email:<?php echo $profile['email']; ?></p>
    </div>
    <a href="profile_modify.php">수정</a>
    <a href="board.php">메인</a>

</html>