<!-- ======= Header/Topbar ======= -->
<?php
include_once(__DIR__ . '/../../../app/helpers/notifications.php');
$adminNotifCount = getUnreadCount($conn, null, 'admin');
$nr = $conn->query("SELECT * FROM notifications WHERE role='admin' AND isRead=0 ORDER BY createdAt DESC LIMIT 6");
$adminNotifs = $nr ? $nr->fetch_all(MYSQLI_ASSOC) : [];
$role = $_SESSION['userRole'] ?? 'customer';

$roleBadge = match (strtolower($role)) {
  'admin' => ['bg-danger', 'bi-shield-lock', 'Admin'],
  default => ['bg-success', 'bi-shield-check', 'Customer'],
};
[$badgeClass, $badgeIcon, $badgeLabel] = $roleBadge;
?>
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="index" class="logo d-flex align-items-center">
      <img src="assets/img/qc-logo.png" alt="">
      <span class="d-none d-lg-block">QuickCart</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div><!-- End Logo -->

  <div class="search-bar">
    <form class="search-form d-flex align-items-center" method="GET" action="search">
      <input type="text" name="query" placeholder="Search" title="Enter search keyword" autocomplete="off">
      <button type="submit" title="Search"><i class="bi bi-search"></i></button>
    </form>
  </div><!-- End Search Bar -->

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      <!-- Profile Dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link nav-icon position-relative" href="notifications" data-bs-toggle="dropdown"
          title="Notifications">
          <i class="bi bi-bell"></i>
          <?php if (($adminNotifCount ?? 0) > 0): ?>
            <span class="badge bg-danger position-absolute" id="adminNotifBadge" style="top:2px;right:2px;font-size:9px;min-width:16px;height:16px;
                   padding:2px 4px;border-radius:8px;line-height:1.2;">
              <?= ($adminNotifCount ?? 0) > 99 ? '99+' : ($adminNotifCount ?? 0) ?>
            </span>
          <?php endif; ?>
        </a>

        <ul class="dropdown-menu dropdown-menu-end" style="width:340px;max-height:420px;overflow-y:auto;padding:0;">
          <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom"
            style="background:#018c42;border-radius:4px 4px 0 0;">
            <span class="fw-bold text-white small">
              <i class="bi bi-bell me-1"></i> Notifications
              <?php if (($adminNotifCount ?? 0) > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $adminNotifCount ?? 0 ?> new</span>
              <?php endif; ?>
            </span>
            <a href="notifications" class="text-white-50 small text-decoration-none">View all</a>
          </li>

          <?php if (empty($adminNotifs ?? [])): ?>
            <li class="text-center text-muted small py-4">
              <i class="bi bi-bell-slash d-block mb-1" style="font-size:24px;"></i>
              No new notifications
            </li>
          <?php else: ?>
            <?php foreach (($adminNotifs ?? []) as $n):
              $icon = match ($n['type'] ?? '') {
                'refund_submitted' => 'bi-cash-stack text-warning',
                'return_requested' => 'bi-box-arrow-left text-info',
                'order_placed' => 'bi-bag-plus text-success',
                'ticket_created' => 'bi-headset text-primary',
                'review_submitted' => 'bi-star-fill text-warning',
                default => 'bi-bell text-muted',
              };
              $link = match ($n['referenceType'] ?? '') {
                'order' => "ordersView?id={$n['referenceId']}",
                'ticket' => "ticketsView?id={$n['referenceId']}",
                'refund' => "refundsView?id={$n['referenceId']}",
                'review' => "reviewsView?id={$n['referenceId']}",
                default => "notifications",
              };
              ?>
              <li>
                <a href="<?= $link ?>" class="dropdown-item d-flex align-items-start gap-2 py-2 px-3"
                  style="background:#f0faf3;white-space:normal;border-bottom:1px solid #f0f0f0;">
                  <i class="bi <?= $icon ?> mt-1 flex-shrink-0"></i>
                  <div class="flex-grow-1">
                    <div class="small fw-semibold"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="text-muted" style="font-size:11px;">
                      <?= htmlspecialchars(mb_strimwidth($n['message'], 0, 60, '…')) ?>
                    </div>
                    <div class="text-muted" style="font-size:10px;">
                      <?= date('M d, g:i A', strtotime($n['createdAt'])) ?>
                    </div>
                  </div>
                  <span
                    style="width:8px;height:8px;border-radius:50%;background:#005d21;margin-top:6px;flex-shrink:0;"></span>
                </a>
              </li>
            <?php endforeach; ?>
            <li class="text-center py-2 border-top">
              <a href="notifications" class="small text-success text-decoration-none fw-semibold">View all
                notifications</a>
            </li>
          <?php endif; ?>
        </ul>
      </li>
      <!-- END NOTIFICATION BELL SNIPPET -->

      <li class="nav-item dropdown pe-3">

        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <!-- Avatar initials circle -->
          <div class="rounded-circle d-flex align-items-center justify-content-center"
            style="width:36px;height:36px;background-color:#fff;flex-shrink:0;">
            <span style="font-size:14px;font-weight:700;color:#005d21;font-family:'Nunito',sans-serif;line-height:1;">
              <?php
              $fullName = $_SESSION['authUser']['fullName'] ?? 'Admin';
              $parts = explode(' ', trim($fullName));
              $initials = strtoupper(substr($parts[0], 0, 1));
              if (count($parts) > 1) {
                $initials .= strtoupper(substr(end($parts), 0, 1));
              }
              echo $initials;
              ?>
            </span>
          </div>
          <span class="d-none d-md-block dropdown-toggle ps-2">
            <?= htmlspecialchars($_SESSION['authUser']['fullName'] ?? 'Admin') ?>
          </span>
        </a><!-- End Profile Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">

          <li class="dropdown-header">
            <h6><?= htmlspecialchars($_SESSION['authUser']['fullName'] ?? 'Admin') ?></h6>
            <span>@<?= htmlspecialchars($_SESSION['authUser']['username'] ?? '') ?></span>
          </li>

          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="accounts">
              <i class="bi bi-person"></i>
              <span>My Account</span>
            </a>
          </li>

          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <form action="../../app/controllers/adminController.php" method="post">
              <button type="submit" name="logoutButton" class="dropdown-item d-flex align-items-center"
                style="color: red;">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </button>
            </form>
          </li>

        </ul><!-- End Profile Dropdown Items -->
      </li><!-- End Profile Nav -->

    </ul>
  </nav><!-- End Icons Navigation -->

</header><!-- End Header/Topbar -->