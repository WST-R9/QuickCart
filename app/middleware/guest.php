<?php
session_start();

if (isset($_SESSION['authUser'])) {

    // User is already authenticated, redirect to their respective dashboard
    $_SESSION['message'] = "You are already signed in! Redirecting to your dashboard...";
    $_SESSION['code'] = "info";

    if ($_SESSION['userRole'] === 'customer') {
        header("Location: /WST-QuickCart/public/user/index.php");
        exit();
    } elseif ($_SESSION['userRole'] === 'admin') {
        header("Location: /WST-QuickCart/public/admin/index.php");
        exit();
    }
}

?>