<?php
include_once("../../app/middleware/admin.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/notifications.php");

// Mark all admin notifs as read
markAllRead($conn, null, 'admin');

$notifs = $conn->query("SELECT * FROM notifications WHERE role='admin' ORDER BY createdAt DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);

include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
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
          <div class="empty-state"><i class="bi bi-bell-slash"></i><h5>No notifications</h5><p>New orders, tickets, and refund requests will appear here.</p></div>
        <?php else: ?>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($notifs as $n):
              $icon = match($n['type']) {
                  'refund_submitted'=>'bi-cash-stack text-warning',
                  'return_requested'=>'bi-box-arrow-left text-info',
                  'ticket_created'  =>'bi-headset text-primary',
                  'order_placed'    =>'bi-bag-plus text-success',
                  default           =>'bi-bell text-muted',
              };
              $link = match($n['referenceType']??'') {
                  'order' =>"ordersView?id={$n['referenceId']}",
                  'ticket'=>"ticketsView?id={$n['referenceId']}",
                  'refund'=>"refundsView?id={$n['referenceId']}",
                  default =>"#",
              };
            ?>
              <a href="<?=$link?>" class="text-decoration-none">
                <div class="d-flex align-items-start gap-3 p-3 rounded-3 border" style="background:#fff;"
                     onmouseover="this.style.background='#f0faf3'" onmouseout="this.style.background='#fff'">
                  <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;background:#e8f5e9;">
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

<?php include('./includes/footer.php'); ?>
