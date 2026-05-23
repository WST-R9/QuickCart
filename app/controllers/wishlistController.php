<?php
session_start();
include_once(__DIR__ . '/../config/config.php');

if (!isset($_SESSION['authUser'])) {
    header('Location: /WST-QuickCart/public/user/login.php');
    exit;
}

$userId = $_SESSION['authUser']['userId'] ?? 0;

if (isset($_POST['addToWishlist'])) {
    $productId = (int) $_POST['productId'];
    $stmt = $conn->prepare("INSERT IGNORE INTO wishlist (userId, productId) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Added to wishlist!'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

if (isset($_POST['removeFromWishlist'])) {
    $productId = (int) $_POST['productId'];
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE userId = ? AND productId = ?");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Removed from wishlist.'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
?>