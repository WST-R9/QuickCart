<?php
// ============================================================
// Notification helper for Admin Page
// ============================================================

function createNotification($conn, $userId, $role, $type, $title, $message, $referenceId = null, $referenceType = null)
{
    $stmt = $conn->prepare("
        INSERT INTO notifications (userId, role, type, title, message, referenceId, referenceType)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if ($stmt === false) {
        error_log("createNotification prepare failed: " . $conn->error);
        return;
    }
    $stmt->bind_param("issssis", $userId, $role, $type, $title, $message, $referenceId, $referenceType);
    $stmt->execute();
    $stmt->close();
}

// ── Shortcut helpers ─────────────────────────────────────────

function notifyAdminNewOrder($conn, $orderId, $orderNumber, $customerName, $total)
{
    createNotification(
        $conn, null, 'admin',
        'order_placed',
        'New Order Placed',
        "{$customerName} placed order {$orderNumber} totalling ₱" . number_format($total, 2) . ".",
        $orderId, 'order'
    );
}

function notifyAdminRefundRequest($conn, $refundId, $orderNumber, $customerName, $reason)
{
    $reasonLabel = ucfirst(str_replace('_', ' ', $reason));
    createNotification(
        $conn, null, 'admin',
        'refund_submitted',
        'Refund Request Submitted',
        "{$customerName} requested a refund for order {$orderNumber}. Reason: {$reasonLabel}.",
        $refundId, 'refund'
    );
}

function notifyAdminNewReview($conn, $reviewId, $productName, $customerName, $rating)
{
    createNotification(
        $conn, null, 'admin',
        'review_submitted',
        'New Product Review',
        "{$customerName} left a {$rating}-star review on \"{$productName}\".",
        $reviewId, 'review'
    );
}

function notifyAdminNewTicket($conn, $ticketId, $ticketNumber, $customerName, $subject)
{
    createNotification(
        $conn, null, 'admin',
        'ticket_created',
        'New Support Ticket',
        "{$customerName} opened ticket {$ticketNumber}: \"{$subject}\".",
        $ticketId, 'ticket'
    );
}

// ── Customer notifications (from admin actions) ──────────────

function notifyCustomerOrderStatus($conn, $userId, $orderId, $orderNumber, $newStatus)
{
    $messages = [
        'confirmed'  => "Your order {$orderNumber} has been confirmed.",
        'processing' => "Your order {$orderNumber} is now being processed.",
        'shipped'    => "Your order {$orderNumber} has been shipped and is on its way!",
        'delivered'  => "Your order {$orderNumber} has been delivered. Enjoy!",
        'cancelled'  => "Your order {$orderNumber} has been cancelled.",
        'refunded'   => "Your refund for order {$orderNumber} has been processed.",
    ];
    $titles = [
        'confirmed'  => 'Order Confirmed',
        'processing' => 'Order Processing',
        'shipped'    => 'Order Shipped',
        'delivered'  => 'Order Delivered',
        'cancelled'  => 'Order Cancelled',
        'refunded'   => 'Refund Processed',
    ];
    if (!isset($messages[$newStatus])) return;
    createNotification(
        $conn, $userId, 'customer',
        'order_status_updated',
        $titles[$newStatus],
        $messages[$newStatus],
        $orderId, 'order'
    );
}

function notifyCustomerRefundStatus($conn, $userId, $orderId, $orderNumber, $newStatus)
{
    $map = [
        'approved' => ['Refund Approved', "Your refund request for order {$orderNumber} has been approved."],
        'rejected' => ['Refund Rejected', "Your refund request for order {$orderNumber} was rejected. Please contact support."],
    ];
    if (!isset($map[$newStatus])) return;
    [$title, $message] = $map[$newStatus];
    createNotification(
        $conn, $userId, 'customer',
        'refund_' . $newStatus,
        $title, $message,
        $orderId, 'order'
    );
}

function notifyCustomerTicketReply($conn, $userId, $ticketId, $ticketNumber)
{
    createNotification(
        $conn, $userId, 'customer',
        'ticket_reply',
        'Support Team Replied',
        "The support team replied to your ticket {$ticketNumber}.",
        $ticketId, 'ticket'
    );
}

function notifyCustomerShippingUpdate($conn, $userId, $orderId, $orderNumber, $shippingStatus)
{
    $messages = [
        'shipped'          => "Your order {$orderNumber} has been picked up by the courier.",
        'out_for_delivery' => "Your order {$orderNumber} is out for delivery today!",
        'delivered'        => "Your order {$orderNumber} was successfully delivered.",
    ];
    if (!isset($messages[$shippingStatus])) return;
    createNotification(
        $conn, $userId, 'customer',
        'shipping_updated',
        'Shipping Update',
        $messages[$shippingStatus],
        $orderId, 'order'
    );
}

// ── Read helpers ─────────────────────────────────────────────

function getUnreadCount($conn, $userId = null, $role = 'customer')
{
    if ($role === 'admin') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM notifications WHERE role = 'admin' AND isRead = 0"
        );
    } else {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM notifications WHERE userId = ? AND role = 'customer' AND isRead = 0"
        );
    }
    if ($stmt === false) return 0;
    if ($role !== 'admin') $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($result['cnt'] ?? 0);
}

function markAllRead($conn, $userId = null, $role = 'customer')
{
    if ($role === 'admin') {
        $conn->query("UPDATE notifications SET isRead = 1 WHERE role = 'admin'");
    } else {
        $stmt = $conn->prepare("UPDATE notifications SET isRead = 1 WHERE userId = ? AND role = 'customer'");
        if ($stmt === false) return;
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
}

function markOneRead($conn, $notificationId)
{
    $stmt = $conn->prepare("UPDATE notifications SET isRead = 1 WHERE notificationId = ?");
    if ($stmt === false) return;
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $stmt->close();
}