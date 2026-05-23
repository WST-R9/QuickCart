<?php
session_start();
include_once(__DIR__ . '/../config/config.php');

if (!isset($_SESSION['authUser'])) {
    header('Location: /WST-QuickCart/public/user/login.php');
    exit;
}

$userId = $_SESSION['authUser']['userId'] ?? 0;

// Helper: is this an AJAX request?
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
          !empty($_POST['ajax']);

if (isset($_POST['addToCart'])) {
    $productId = (int) $_POST['productId'];

    $check = $conn->prepare("SELECT cartId, quantity FROM cart WHERE userId = ? AND productId = ?");
    $check->bind_param("ii", $userId, $productId);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $row    = $result->fetch_assoc();
        $newQty = $row['quantity'] + 1;
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE cartId = ?");
        $update->bind_param("ii", $newQty, $row['cartId']);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO cart (userId, productId, quantity) VALUES (?, ?, 1)");
        $insert->bind_param("ii", $userId, $productId);
        $insert->execute();
    }

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item added to cart!'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

if (isset($_POST['removeItem']) || isset($_POST['removeFromCart'])) {
    $cartId = (int) ($_POST['cartId'] ?? 0);
    $delete = $conn->prepare("DELETE FROM cart WHERE cartId = ? AND userId = ?");
    $delete->bind_param("ii", $cartId, $userId);
    $delete->execute();

    if ($isAjax) {
        echo json_encode(['success' => true]);
        exit;
    }

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item removed from cart.'];
    header('Location: /WST-QuickCart/public/user/cart.php');
    exit;
}

if (isset($_POST['updateQty'])) {
    $cartId = (int) $_POST['cartId'];
    $qty    = (int) $_POST['quantity'];

    if ($qty <= 0) {
        $delete = $conn->prepare("DELETE FROM cart WHERE cartId = ? AND userId = ?");
        $delete->bind_param("ii", $cartId, $userId);
        $delete->execute();
    } else {
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE cartId = ? AND userId = ?");
        $update->bind_param("iii", $qty, $cartId, $userId);
        $update->execute();
    }

    if ($isAjax) {
        echo json_encode(['success' => true]);
        exit;
    }

    header('Location: /WST-QuickCart/public/user/cart.php');
    exit;
}

if (isset($_POST['clearCart'])) {
    $delete = $conn->prepare("DELETE FROM cart WHERE userId = ?");
    $delete->bind_param("i", $userId);
    $delete->execute();

    if ($isAjax) {
        echo json_encode(['success' => true]);
        exit;
    }

    header('Location: /WST-QuickCart/public/user/cart.php');
    exit;
}
?>