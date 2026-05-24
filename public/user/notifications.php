<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/notifications.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;

// Mark all as read when page loads
markAllRead($conn, $userId, 'customer');

// Fetch all notifications
$stmt = $conn->prepare("
    SELECT * FROM notifications
    WHERE userId = ? AND role = 'customer'
    ORDER BY createdAt DESC
    LIMIT 50
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
  <h1>Notifications</h1>
  <nav><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index">Home</a></li>
    <li class="breadcrumb-item active">Notifications</li>
  </ol></nav>
</div>

<section class="section">
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">All Notifications</h5>

        <?php if (empty($notifs)): ?>
          <div class="empty-state">
            <i class="bi bi-bell-slash"></i>
            <h5>No notifications yet</h5>
            <p>We'll notify you about your orders, tickets, and refunds here.</p>
          </div>
        <?php else: ?>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($notifs as $n):
              $icon = match($n['type']) {
                  'order_status'  => 'bi-bag-check text-success',
                  'ticket_reply'  => 'bi-headset text-primary',
                  'refund_update' => 'bi-cash-stack text-warning',
                  'return_update' => 'bi-box-arrow-left text-info',
                  default         => 'bi-bell text-muted',
              };
              $link = match($n['referenceType'] ?? '') {
                  'order'  => "orderView?id={$n['referenceId']}",
                  'ticket' => "ticketView?id={$n['referenceId']}",
                  'refund' => "refundOrder?id={$n['referenceId']}",
                  default  => "#",
              };
            ?>
              <a href="<?=$link?>" class="text-decoration-none">
                <div class="d-flex align-items-start gap-3 p-3 rounded-3 border"
                     style="background:#fff; transition:background 0.15s;"
                     onmouseover="this.style.background='#f0faf3'" onmouseout="this.style.background='#fff'">
                  <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                       style="width:40px;height:40px;background:#e8f5e9;">
                    <i class="bi <?=$icon?>" style="font-size:16px;"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold small"><?=htmlspecialchars($n['title'])?></div>
                    <div class="text-muted small"><?=htmlspecialchars($n['message'])?></div>
                    <div class="text-muted" style="font-size:11px;"><?=date('M d, Y h:i A',strtotime($n['createdAt']))?></div>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</section>

<?php include('includes/footer.php'); ?>
