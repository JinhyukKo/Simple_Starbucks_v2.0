#! incomplete

<?php
    include '../auth/login_required.php';
    require_once '../config.php';

    $username = $_SESSION['username'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $pdo->query($sql);
    $profile = $result->fetch();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $user_id = $profile['id'];
        $filename = '';

        if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK){
            $filename = $_FILES['upload']['name'];
            $tmp_name = $_FILES['upload']['tmp_name'];
            move_uploaded_file($tmp_name, "profile/" . $filename);
        }

        $sql = "UPDATE users SET filename = '$filename' WHERE id = '$user_id' ";

        $pdo->query($sql);

        header("Location: profile.php");
        exit();
    }
?>


<!DOCTYPE html>
<html>
<form method="POST" enctype="multipart/form-data">
    <div>
        <?php if($profile['filename']): ?>
            <img src="profile/<?=$profile['filename']; ?>" alt="<?=$profile['filename'];?>">
            <input type="file" name="filename">
        <?php endif; ?>
        <input type="file" name="upload">
    </div>
    <div>
        <p>name:<?php echo $profile['username']; ?></p>
    </div>
    <div>
        <p>email:<?php echo $profile['email']; ?></p>
    </div>
    <input type="submit" value="수정">
</form>

</html>