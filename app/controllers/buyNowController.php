<?php
include_once("../middleware/user.php");
include_once("../config/config.php");

$userId    = $_SESSION['authUser']['userId'] ?? 0;
$productId = intval($_GET['buyNow'] ?? 0);

if (!$userId || !$productId) {
    header("Location: ../../public/user/index");
    exit;
}

// Validate product exists and is active
$stmt = $conn->prepare("SELECT productId FROM products WHERE productId = ? AND status = 'active' LIMIT 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Product not found or unavailable.'];
    header("Location: ../../public/user/index");
    exit;
}

// Insert into cart (quantity 1), or reset to 1 if already in cart
$stmt = $conn->prepare("
    INSERT INTO cart (userId, productId, quantity)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE quantity = 1
");
$stmt->bind_param("ii", $userId, $productId);
$stmt->execute();
$stmt->close();

header("Location: ../../public/user/checkout");
exit;