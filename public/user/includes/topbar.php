<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$userId = $_SESSION['authUser']['userId'] ?? 0;
$role   = $_SESSION['authUser']['role'] ?? $_SESSION['userRole'] ?? 'customer';
$authUser = $_SESSION['authUser'] ?? [];

if (!empty($authUser['fullName'])) {
    $fullName = $authUser['fullName'];
} elseif (!empty($authUser['firstName'])) {
    $fullName = trim(($authUser['firstName'] ?? '') . ' ' . ($authUser['lastName'] ?? ''));
} else {
    $fullName = 'User';
}
$fullName = htmlspecialchars($fullName);
$username = htmlspecialchars($authUser['username'] ?? '');

$nameParts = explode(' ', trim($fullName));
$initials  = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1)) ?: 'U';

// Cart count
$cartCount = 0;
if ($userId > 0 && isset($conn)) {
    $stmt = $conn->prepare("SELECT IFNULL(SUM(quantity),0) AS cartCount FROM cart WHERE userId = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $cartCount = (int) ($stmt->get_result()->fetch_assoc()['cartCount'] ?? 0);
        $stmt->close();
    }
}

// Notification count
$notifCount = 0;
if ($userId > 0 && isset($conn)) {
    if (!function_exists('getUnreadCount')) {
        include_once(__DIR__ . '/../../../app/helpers/notifications.php');
    }
    $notifCount = getUnreadCount($conn, $userId, 'customer');
}

// Recent notifications (max 6)
$recentNotifs = [];
if ($userId > 0 && isset($conn)) {
    $stmt = $conn->prepare("
        SELECT notificationId, type, title, message, referenceId, referenceType, isRead, createdAt
        FROM notifications
        WHERE userId = ? AND role = 'customer'
        ORDER BY createdAt DESC
        LIMIT 6
    ");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $recentNotifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        error_log("recentNotifs prepare failed: " . $conn->error);
    }
}
?>

<!-- ======= Header/Topbar ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="index" class="logo d-flex align-items-center">
      <img src="assets/img/qc-logo.png" alt="QuickCart Logo">
      <span class="d-none d-lg-block">QuickCart</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div>

  <div class="search-bar">
    <form class="search-form d-flex align-items-center" method="GET" action="search">
      <input type="text" name="search" placeholder="Search products…" title="Enter search keyword"
             autocomplete="off" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      <button type="submit" title="Search"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      <!-- Cart -->
      <li class="nav-item">
        <a class="nav-link nav-icon position-relative" href="cart" title="My Cart">
          <i class="bi bi-cart3"></i>
          <span class="cart-badge cart-count-badge" id="cartCountBadge"
                <?= $cartCount === 0 ? 'style="display:none;"' : '' ?>>
            <?= $cartCount ?>
          </span>
        </a>
      </li>

      <!-- Notification Bell -->
      <li class="nav-item dropdown">
        <a class="nav-link nav-icon position-relative" href="#"
           data-bs-toggle="dropdown" title="Notifications" id="notifBell">
          <i class="bi bi-bell"></i>
          <?php if ($notifCount > 0): ?>
            <span class="badge bg-danger position-absolute"
                  id="notifBadge"
                  style="top:2px;right:2px;font-size:9px;min-width:16px;height:16px;
                         padding:2px 4px;border-radius:8px;line-height:1.2;">
              <?= $notifCount > 99 ? '99+' : $notifCount ?>
            </span>
          <?php else: ?>
            <span class="badge bg-danger position-absolute d-none"
                  id="notifBadge"
                  style="top:2px;right:2px;font-size:9px;min-width:16px;height:16px;
                         padding:2px 4px;border-radius:8px;line-height:1.2;">0</span>
          <?php endif; ?>
        </a>

        <ul class="dropdown-menu dropdown-menu-end"
            style="width:340px; max-height:420px; overflow-y:auto; padding:0;">
          <!-- Header -->
          <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom"
              style="background:#018c42; border-radius:4px 4px 0 0;">
            <span class="fw-bold text-white small">
              <i class="bi bi-bell me-1"></i> Notifications
              <?php if ($notifCount > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $notifCount ?> new</span>
              <?php endif; ?>
            </span>
            <?php if (!empty($recentNotifs)): ?>
              <a href="notifications" class="text-white-50 small text-decoration-none">Mark all read</a>
            <?php endif; ?>
          </li>

          <?php if (empty($recentNotifs)): ?>
            <li class="text-center text-muted small py-4">
              <i class="bi bi-bell-slash d-block mb-1" style="font-size:24px;"></i>
              No notifications yet
            </li>
          <?php else: ?>
            <?php foreach ($recentNotifs as $n):
              $icon = match($n['type']) {
                  'order_status'   => 'bi-bag-check text-success',
                  'ticket_reply'   => 'bi-headset text-primary',
                  'refund_update'  => 'bi-cash-stack text-warning',
                  'return_update'  => 'bi-box-arrow-left text-info',
                  default          => 'bi-bell text-muted',
              };
              $link = match($n['referenceType'] ?? '') {
                  'order'  => "orderView?id={$n['referenceId']}",
                  'ticket' => "ticketView?id={$n['referenceId']}",
                  'refund' => "refundOrder?id={$n['referenceId']}",
                  default  => "notifications",
              };
            ?>
              <li>
                <a href="<?= $link ?>"
                   class="dropdown-item d-flex align-items-start gap-2 py-2 px-3
                          <?= !$n['isRead'] ? 'fw-semibold' : '' ?>"
                   style="<?= !$n['isRead'] ? 'background:#f0faf3;' : '' ?> white-space:normal; border-bottom:1px solid #f0f0f0;">
                  <i class="bi <?= $icon ?> mt-1 flex-shrink-0"></i>
                  <div class="flex-grow-1">
                    <div class="small"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="text-muted" style="font-size:11px;">
                      <?= htmlspecialchars(mb_strimwidth($n['message'], 0, 60, '…')) ?>
                    </div>
                    <div class="text-muted" style="font-size:10px;">
                      <?= date('M d, g:i A', strtotime($n['createdAt'])) ?>
                    </div>
                  </div>
                  <?php if (!$n['isRead']): ?>
                    <span class="flex-shrink-0" style="width:8px;height:8px;border-radius:50%;
                          background:#005d21;margin-top:6px;"></span>
                  <?php endif; ?>
                </a>
              </li>
            <?php endforeach; ?>
            <li class="text-center py-2 border-top">
              <a href="notifications" class="small text-success text-decoration-none fw-semibold">
                View all notifications
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </li><!-- End Notifications -->

      <!-- Profile Dropdown -->
      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <div class="rounded-circle d-flex align-items-center justify-content-center"
               style="width:36px;height:36px;background-color:#fff;flex-shrink:0;">
            <span style="font-size:13px;font-weight:700;color:#005d21;
                         font-family:'Nunito',sans-serif;line-height:1;">
              <?= $initials ?>
            </span>
          </div>
          <span class="d-none d-md-block dropdown-toggle ps-2"><?= $fullName ?></span>
        </a>

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6><?= $fullName ?></h6>
            <span>@<?= $username ?></span>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item d-flex align-items-center" href="accounts">
            <i class="bi bi-person"></i><span>My Account</span></a></li>
          <li><a class="dropdown-item d-flex align-items-center" href="orders">
            <i class="bi bi-bag-check"></i><span>My Orders</span></a></li>
          <li><a class="dropdown-item d-flex align-items-center" href="wishlist">
            <i class="bi bi-heart"></i><span>Wishlist</span></a></li>
          <li><a class="dropdown-item d-flex align-items-center" href="track">
            <i class="bi bi-truck"></i><span>Track Order</span></a></li>
          <li><a class="dropdown-item d-flex align-items-center" href="reviews">
            <i class="bi bi-star"></i><span>My Reviews</span></a></li>
          <li><a class="dropdown-item d-flex align-items-center" href="tickets">
            <i class="bi bi-headset"></i><span>Support Tickets</span></a></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form action="../../app/controllers/userController.php" method="post">
              <button type="submit" name="logoutButton"
                      class="dropdown-item d-flex align-items-center" style="color:red;">
                <i class="bi bi-box-arrow-right"></i><span>Sign Out</span>
              </button>
            </form>
          </li>
        </ul>
      </li>

    </ul>
  </nav>

</header>