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
    <link rel="stylesheet" href="/style.css">

    </head>
    <body>
        
<form method="post" enctype="multipart/form-data">

    <div>
        <?php if($profile['profile']): ?>
            <img src="profile/<?=$profile['profile']; ?>" alt="<?=$profile['profile'];?>">
            <input type="file" name="upload">
        <?php else: ?>
            <img src="" alt="no image">
            <input type="file" name="upload">
        <?php endif; ?>
    </div>
    <div>
        <p>name:<?php echo $profile['username']; ?></p>
    </div><br/>
    <div>
        <p>email:<input type="text" name="email" value="<?php echo $profile['email']; ?>"></p>
    </div><br/>
    <div>
        <p>password:<input type="password" name="password" ></p>
    </div><br/>
    <input type="submit" value="저장">
    <a href="board.php">메인</a>
</form>
    </body>

</html>