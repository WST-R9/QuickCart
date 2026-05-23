<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to the correct dashboard
if (!empty($_SESSION['authUser'])) {
    $role = $_SESSION['userRole'] ?? 'customer';
    if ($role === 'admin') {
        header("Location: /WST-QuickCart/public/admin/index");
    } else {
        header("Location: /WST-QuickCart/public/user/index");
    }
    exit;
}
