<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$userId  = $_SESSION['authUser']['userId'] ?? 0;
$orderId = (int) ($_GET['id'] ?? 0);
$shipping = null;
$order    = null;

if ($orderId > 0) {
    $stmt = $conn->prepare("
        SELECT s.*, o.orderNumber, o.status AS orderStatus, o.totalAmount, o.orderedAt
        FROM shipping s
        JOIN orders o ON s.orderId = o.orderId
        WHERE s.orderId = ? AND o.userId = ?
    ");
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $shipping = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Recent shipped orders for quick pick
$stmt = $conn->prepare("
    SELECT o.orderId, o.orderNumber, o.orderedAt, s.status AS shippingStatus
    FROM orders o
    JOIN shipping s ON o.orderId = s.orderId
    WHERE o.userId = ?
    ORDER BY o.orderedAt DESC
    LIMIT 10
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$steps = ['preparing', 'shipped', 'out_for_delivery', 'delivered'];
$stepLabels = [
    'preparing'       => 'Preparing',
    'shipped'         => 'Shipped',
    'out_for_delivery'=> 'Out for Delivery',
    'delivered'       => 'Delivered',
];
?>

<div class="pagetitle">
  <h1>Track Order</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Track Order</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row g-4">

    <!-- Order picker -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Select Order</h5>
          <?php if (empty($recentOrders)): ?>
            <p class="text-muted small">No shipped orders yet.</p>
          <?php else: ?>
            <div class="d-flex flex-column gap-2">
              <?php foreach ($recentOrders as $ro):
                $badge = match($ro['shippingStatus']) {
                    'preparing'        => 'bg-warning text-dark',
                    'shipped'          => 'bg-primary',
                    'out_for_delivery' => 'bg-info text-dark',
                    'delivered'        => 'bg-success',
                    default            => 'bg-secondary',
                };
              ?>
                <a href="track?id=<?= $ro['orderId'] ?>"
                   class="d-flex justify-content-between align-items-center p-2 rounded border text-decoration-none
                          <?= $orderId === $ro['orderId'] ? 'border-success bg-light' : '' ?>"
                   style="border-color:#d4e8da !important;">
                  <div>
                    <div class="fw-bold text-dark" style="font-size:13px;">
                      <?= htmlspecialchars($ro['orderNumber']) ?>
                    </div>
                    <div class="text-muted" style="font-size:11px;">
                      <?= date('M d, Y', strtotime($ro['orderedAt'])) ?>
                    </div>
                  </div>
                  <span class="badge <?= $badge ?>" style="font-size:10px;">
                    <?= ucfirst(str_replace('_', ' ', $ro['shippingStatus'])) ?>
                  </span>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Tracking details -->
    <div class="col-lg-8">
      <?php if (!$shipping): ?>
        <div class="card">
          <div class="card-body">
            <div class="empty-state">
              <i class="bi bi-truck"></i>
              <h5>No order selected</h5>
              <p>Select an order on the left to view tracking details.</p>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">
              <?= htmlspecialchars($shipping['orderNumber']) ?>
              <span><?= date('M d, Y', strtotime($shipping['orderedAt'])) ?></span>
            </h5>

            <!-- Progress steps -->
            <div class="d-flex justify-content-between align-items-center mb-4 mt-3 position-relative">
              <div style="position:absolute; top:18px; left:0; right:0; height:3px; background:#d4e8da; z-index:0;"></div>
              <?php
              $currentIdx = array_search($shipping['status'], $steps);
              if ($currentIdx === false) $currentIdx = -1;
              foreach ($steps as $i => $step):
                $done    = $i <= $currentIdx;
                $current = $i === $currentIdx;
              ?>
                <div class="d-flex flex-column align-items-center position-relative" style="z-index:1; flex:1;">
                  <div class="rounded-circle d-flex align-items-center justify-content-center mb-2"
                       style="width:36px; height:36px;
                              background:<?= $done ? '#005d21' : '#fff' ?>;
                              border: 3px solid <?= $done ? '#005d21' : '#d4e8da' ?>;
                              <?= $current ? 'box-shadow:0 0 0 4px rgba(0,93,33,0.15);' : '' ?>">
                    <?php if ($done): ?>
                      <i class="bi bi-check-lg text-white" style="font-size:14px;"></i>
                    <?php else: ?>
                      <span style="width:8px;height:8px;background:#d4e8da;border-radius:50%;display:block;"></span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:11px; font-weight:600; color:<?= $done ? '#005d21' : '#89ad94' ?>; text-align:center;">
                    <?= $stepLabels[$step] ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Shipping info -->
            <div class="row g-3 mt-2">
              <div class="col-sm-6">
                <div class="text-muted small fw-semibold mb-1">Courier</div>
                <div class="fw-bold"><?= htmlspecialchars($shipping['courier'] ?? 'Not assigned') ?></div>
              </div>
              <div class="col-sm-6">
                <div class="text-muted small fw-semibold mb-1">Tracking Number</div>
                <div class="fw-bold"><?= htmlspecialchars($shipping['trackingNumber'] ?? 'Not available') ?></div>
              </div>
              <div class="col-12">
                <div class="text-muted small fw-semibold mb-1">Delivery Address</div>
                <div class="fw-bold">
                  <?= htmlspecialchars($shipping['recipientName']) ?> · <?= htmlspecialchars($shipping['phoneNumber']) ?><br>
                  <?= htmlspecialchars($shipping['street']) ?>, <?= htmlspecialchars($shipping['barangay']) ?>,
                  <?= htmlspecialchars($shipping['city']) ?>
                  <?= $shipping['province'] ? ', ' . htmlspecialchars($shipping['province']) : '' ?>
                  <?= $shipping['zipCode'] ? ' ' . htmlspecialchars($shipping['zipCode']) : '' ?>
                </div>
              </div>
              <?php if ($shipping['shippedAt']): ?>
              <div class="col-sm-6">
                <div class="text-muted small fw-semibold mb-1">Shipped At</div>
                <div><?= date('M d, Y g:i A', strtotime($shipping['shippedAt'])) ?></div>
              </div>
              <?php endif; ?>
              <?php if ($shipping['deliveredAt']): ?>
              <div class="col-sm-6">
                <div class="text-muted small fw-semibold mb-1">Delivered At</div>
                <div><?= date('M d, Y g:i A', strtotime($shipping['deliveredAt'])) ?></div>
              </div>
              <?php endif; ?>
            </div>

          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<?php include('includes/footer.php'); ?>