<?php
include_once("../../app/middleware/admin.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/badges.php");

include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');

$filter = $_GET['status'] ?? '';
$where  = "1=1";
$params = [];
$types  = '';

if ($filter !== '') {
    $where   .= " AND rr.status = ?";
    $params[] = $filter;
    $types   .= 's';
}

$sql = "
    SELECT rr.*,
           o.orderNumber,
           CONCAT(u.firstName,' ',u.lastName) AS customerName,
           u.emailAddress
    FROM refund_requests rr
    JOIN orders o ON rr.orderId = o.orderId
    JOIN users  u ON rr.userId  = u.userId
    WHERE $where
    ORDER BY rr.createdAt DESC
";

if ($types !== '') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $refunds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $refunds = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
}

// Count per status
$counts = [];
$cr = mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM refund_requests GROUP BY status");
while ($row = mysqli_fetch_assoc($cr)) $counts[$row['status']] = $row['cnt'];
$counts['all'] = array_sum($counts);
?>

<div class="pagetitle">
  <h1>Refund Requests</h1>
  <nav><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index">Home</a></li>
    <li class="breadcrumb-item active">Refund Requests</li>
  </ol></nav>
</div>

<section class="section">
  <!-- Stat Cards -->
  <div class="row g-3 mb-3">
    <?php foreach ([
      ['all','Total','bi-arrow-repeat','#005d21'],
      ['pending','Pending','bi-hourglass-split','#f59e0b'],
      ['approved','Approved','bi-check-circle','#198754'],
      ['rejected','Rejected','bi-x-circle','#dc3545'],
    ] as [$key,$label,$icon,$color]): ?>
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body py-3">
            <i class="bi <?=$icon?>" style="font-size:24px;color:<?=$color?>;"></i>
            <div class="fw-bold fs-5 mt-1"><?=$counts[$key]??0?></div>
            <div class="text-muted small"><?=$label?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-body">
      <!-- Filter Pills -->
      <div class="d-flex gap-2 flex-wrap mb-3">
        <?php foreach([''=> 'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $val=>$label): ?>
          <a href="refunds<?=$val?'?status='.$val:''?>" class="pill <?=$filter===$val?'active':''?>">
            <?=$label?> <?php if(isset($counts[$val===''?'all':$val])): ?><span class="ms-1">(<?=$counts[$val===''?'all':$val]?>)</span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if (empty($refunds)): ?>
        <div class="empty-state"><i class="bi bi-arrow-repeat"></i><h5>No refund requests found</h5></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-borderless table-hover align-middle datatable">
            <thead style="background:#e6f4ea;">
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Reason</th>
                <th>Method</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($refunds as $r):
                $badge = match($r['status']) {
                    'approved' => 'bg-success',
                    'rejected' => 'bg-danger',
                    default    => 'bg-warning text-dark',
                };
              ?>
                <tr>
                  <td class="fw-bold"><?=htmlspecialchars($r['orderNumber'])?></td>
                  <td><?=htmlspecialchars($r['customerName'])?></td>
                  <td><?=htmlspecialchars($r['reason'])?></td>
                  <td><?=ucfirst(str_replace('_',' ',$r['refundMethod']))?></td>
                  <td><span class="badge <?=$badge?>"><?=ucfirst($r['status'])?></span></td>
                  <td class="text-muted small"><?=date('M d, Y',strtotime($r['createdAt']))?></td>
                  <td>
                    <a href="refundsView?id=<?=$r['refundId']?>" class="btn btn-sm btn-primary">
                      <i class="bi bi-eye me-1"></i>View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include('./includes/footer.php'); ?>
