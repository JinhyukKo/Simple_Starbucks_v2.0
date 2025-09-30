<?php
    if($_SESSION['role'] !== 'admin'){
        echo "you are not an admin";
        exit();
    }
