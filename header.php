<div>
    <?php
        echo "Username : ".$_SESSION['username']."<br/>";
        echo "Role : ".$_SESSION['role'];
        
    ?>
    <br/>
                <a href="/board/profile.php">MyProfile</a> |
            <a href="/auth/logout.php">Logout</a>
</div>