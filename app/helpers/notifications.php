<?php
// ============================================================
// app/helpers/notifications.php
// Shared notification helper for user and admin
// ============================================================

function createNotification($conn, $userId, $role, $type, $title, $message, $referenceId = null, $referenceType = null) {
    $stmt = $conn->prepare("
        INSERT INTO notifications (userId, role, type, title, message, referenceId, referenceType)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if ($stmt === false) {
        error_log("createNotification prepare failed: " . $conn->error);
        return;
    }
    $stmt->bind_param("issssIs", $userId, $role, $type, $title, $message, $referenceId, $referenceType);
    $stmt->execute();
    $stmt->close();
}

function getUnreadCount($conn, $userId = null, $role = 'customer') {
    if ($role === 'admin') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM notifications WHERE role = 'admin' AND isRead = 0"
        );
    } else {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM notifications WHERE userId = ? AND role = 'customer' AND isRead = 0"
        );
    }

    if ($stmt === false) {
        error_log("getUnreadCount prepare failed: " . $conn->error);
        return 0;
    }

    if ($role !== 'admin') {
        $stmt->bind_param("i", $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($result['cnt'] ?? 0);
}

function markAllRead($conn, $userId = null, $role = 'customer') {
    if ($role === 'admin') {
        $conn->query("UPDATE notifications SET isRead = 1 WHERE role = 'admin'");
    } else {
        $stmt = $conn->prepare("UPDATE notifications SET isRead = 1 WHERE userId = ? AND role = 'customer'");
        if ($stmt === false) {
            error_log("markAllRead prepare failed: " . $conn->error);
            return;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
}

function markOneRead($conn, $notificationId) {
    $stmt = $conn->prepare("UPDATE notifications SET isRead = 1 WHERE notificationId = ?");
    if ($stmt === false) {
        error_log("markOneRead prepare failed: " . $conn->error);
        return;
    }
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $stmt->close();
}