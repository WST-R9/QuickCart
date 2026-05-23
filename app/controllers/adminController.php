<?php
session_start();
include_once(__DIR__ . "/../config/config.php");


if (isset($_POST['logoutButton'])) {
    unset($_SESSION['user_id']);
    unset($_SESSION['userRole']);
    unset($_SESSION['authUser']);
    session_destroy();

    header("Location: /WST-QuickCart/public/login");
    exit(0);
}