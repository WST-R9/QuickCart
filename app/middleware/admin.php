<?php
session_start();
include_once(__DIR__ . "/../config/config.php");

if (!isset($_SESSION['authUser'])) {
    $_SESSION['message'] = "You must be logged in to access this page.";
    $_SESSION['code'] = "warning";
    header("Location: /WST-QuickCart/public/login");  // ✅
    exit();
} else {
    if ($_SESSION['userRole'] !== 'admin') {
        $_SESSION['message'] = "You do not have permission to access this page.";
        $_SESSION['code'] = "error";
        header("Location: /WST-QuickCart/public/login");  // ✅
        exit();
    }
}
?>