<?php
function logActivity($conn, $userId, $action, $module, $targetId = null, $targetLabel = null, $details = null) {
    $userId      = intval($userId);
    $action      = mysqli_real_escape_string($conn, $action);
    $module      = mysqli_real_escape_string($conn, $module);
    $targetId    = $targetId ? intval($targetId) : "NULL";
    $targetLabel = $targetLabel ? "'" . mysqli_real_escape_string($conn, $targetLabel) . "'" : "NULL";
    $details     = $details ? "'" . mysqli_real_escape_string($conn, $details) . "'" : "NULL";

    mysqli_query($conn, "
        INSERT INTO activity_logs (userId, action, module, targetId, targetLabel, details)
        VALUES ($userId, '$action', '$module', $targetId, $targetLabel, $details)
    ");
}