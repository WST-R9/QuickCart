<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$userId = $_SESSION['authUser']['userId'] ?? 0;

$order       = null;
$orderItems  = null;
$errorMsg    = '';
$orderNumber = trim($_GET['order'] ?? '');

if ($orderNumber !== '') {
    $stmt = $conn->prepare(
        "SELECT o.orderId, o.orderNumber, o.totalAmount, o.status,
                o.orderedAt, o.address
         FROM orders o
         WHERE o.orderNumber = ? AND o.userId = ?
         LIMIT 1"
    );
    $stmt->bind_param("si", $orderNumber, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        $errorMsg = "Order not found. Please check the order number and try again.";
    } else {
        $stmt2 = $conn->prepare(
            "SELECT oi.quantity, oi.unitPrice, p.name AS productName, p.imageUrl
             FROM order_items oi
             JOIN products p ON oi.productId = p.productId
             WHERE oi.orderId = ?"
        );
        $stmt2->bind_param("i", $order['orderId']);
        $stmt2->execute();
        $orderItems = $stmt2->get_result();
        $stmt2->close();
    }
}

// Status steps for the tracker
$statusSteps = ['pending','confirmed','processing','shipped','delivered'];
$badgeMap = [
    'pending'    => 'bg-warning text-dark',
    'confirmed'  => 'bg-primary',
    'processing' => 'bg-info text-dark',
    'shipped'    => 'bg-dark',
    'delivered'  => 'bg-success',
    'cancelled'  => 'bg-danger',
    'refunded'   => 'bg-secondary',
];

function stepIcon(string $step): string {
    return match ($step) {
        'pending'    => 'bi-clock',
        'confirmed'  => 'bi-check-circle',
        'processing' => 'bi-box-seam',
        'shipped'    => 'bi-truck',
        'delivered'  => 'bi-house-check',
        default      => 'bi-circle',
    };
}

$currentStepIndex = $order ? array_search($order['status'], $statusSteps) : -1;
?>

<div class="pagetitle">
  <h1>Track an Order</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Track Order</li>
    </ol>
  </nav>
</div>

<section class="section">

  <!-- Search Form -->
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Enter Your Order Number</h5>
      <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-12 col-md-6">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="order" class="form-control"
                   placeholder="e.g. ORD-20260522-00001"
                   value="<?= htmlspecialchars($orderNumber) ?>">
          </div>
        </div>
        <div class="col-6 col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-truck me-1"></i> Track
          </button>
        </div>
        <?php if ($orderNumber): ?>
          <div class="col-6 col-md-2">
            <a href="track" class="btn btn-outline-secondary w-100">
              <i class="bi bi-x-circle me-1"></i> Clear
            </a>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Error -->
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-circle-fill"></i>
      <?= htmlspecialchars($errorMsg) ?>
    </div>
  <?php endif; ?>

  <!-- Order Result -->
  <?php if ($order): ?>

    <?php if (in_array($order['status'], ['cancelled','refunded'])): ?>
      <!-- Cancelled / Refunded -->
      <div class="card">
        <div class="card-body text-center py-4">
          <i class="bi bi-x-circle-fill text-danger" style="font-size:3rem;"></i>
          <h5 class="mt-3">Order <?= htmlspecialchars($order['orderNumber']) ?></h5>
          <span class="badge <?= $badgeMap[$order['status']] ?> fs-6 mt-1">
            <?= ucfirst($order['status']) ?>
          </span>
          <p class="text-muted mt-3 mb-0">
            This order has been <?= $order['status'] === 'cancelled' ? 'cancelled' : 'refunded' ?>.
            Please contact support if you have questions.
          </p>
          <a href="tickets" class="btn btn-outline-primary mt-3">
            <i class="bi bi-headset me-1"></i> Get Support
          </a>
        </div>
      </div>
    <?php else: ?>
      <!-- Progress Tracker -->
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <h5 class="card-title mb-0">Order <?= htmlspecialchars($order['orderNumber']) ?></h5>
            <span class="badge <?= $badgeMap[$order['status']] ?? 'bg-secondary' ?>">
              <?= ucfirst($order['status']) ?>
            </span>
          </div>
          <p class="text-muted small mb-4">
            Placed on <?= date("F d, Y \a\\t g:i A", strtotime($order['orderedAt'])) ?>
          </p>

          <!-- Steps -->
          <div class="order-tracker d-flex justify-content-between position-relative mb-4">
            <div class="tracker-line position-absolute top-50 start-0 end-0"
                 style="height:3px;background:#dee2e6;z-index:0;transform:translateY(-50%);margin:0 5%;"></div>
            <?php foreach ($statusSteps as $idx => $step):
              $done   = $currentStepIndex !== false && $idx <= $currentStepIndex;
              $active = $currentStepIndex !== false && $idx === $currentStepIndex;
            ?>
              <div class="tracker-step text-center position-relative" style="z-index:1;flex:1;">
                <div class="tracker-icon mx-auto d-flex align-items-center justify-content-center rounded-circle mb-2"
                     style="width:48px;height:48px;
                            background:<?= $done ? '#005d21' : '#dee2e6' ?>;
                            color:<?= $done ? '#fff' : '#6c757d' ?>;
                            font-size:1.2rem;
                            box-shadow:<?= $active ? '0 0 0 4px rgba(0,93,33,.2)' : 'none' ?>;">
                  <i class="bi <?= stepIcon($step) ?>"></i>
                </div>
                <div class="small fw-semibold" style="color:<?= $done ? '#005d21' : '#6c757d' ?>;">
                  <?= ucfirst($step) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Order Summary -->
          <div class="row g-3">
            <div class="col-md-6">
              <p class="mb-1 text-muted small fw-semibold">DELIVERY ADDRESS</p>
              <p class="mb-0"><?= htmlspecialchars($order['address'] ?? 'N/A') ?></p>
            </div>
            <div class="col-md-6 text-md-end">
              <p class="mb-1 text-muted small fw-semibold">ORDER TOTAL</p>
              <p class="mb-0 fw-bold fs-5" style="color:#005d21;">
                ₱<?= number_format($order['totalAmount'], 2) ?>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <?php if ($orderItems && $orderItems->num_rows > 0): ?>
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Items in This Order</h5>
            <div class="table-responsive">
              <table class="table table-borderless align-middle">
                <thead>
                  <tr>
                    <th colspan="2">Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($item = $orderItems->fetch_assoc()): ?>
                    <tr>
                      <td style="width:52px;">
                        <img src="../uploads/products/<?= htmlspecialchars($item['imageUrl'] ?? '') ?>"
                             alt="<?= htmlspecialchars($item['productName']) ?>"
                             style="width:44px;height:44px;object-fit:cover;border-radius:8px;"
                             onerror="this.src='assets/img/product-placeholder.png'">
                      </td>
                      <td class="fw-semibold"><?= htmlspecialchars($item['productName']) ?></td>
                      <td class="text-center"><?= (int)$item['quantity'] ?></td>
                      <td class="text-end">₱<?= number_format($item['unitPrice'], 2) ?></td>
                      <td class="text-end fw-bold">
                        ₱<?= number_format($item['unitPrice'] * $item['quantity'], 2) ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="text-center mt-2">
      <a href="orders" class="btn btn-outline-primary">
        <i class="bi bi-bag-check me-1"></i> View All Orders
      </a>
      <a href="tickets" class="btn btn-outline-secondary ms-2">
        <i class="bi bi-headset me-1"></i> Get Support
      </a>
    </div>

  <?php elseif ($orderNumber === ''): ?>
    <!-- No search yet -->
    <div class="card">
      <div class="card-body">
        <div class="empty-state">
          <i class="bi bi-truck"></i>
          <h5>Track Your Order</h5>
          <p>Enter your order number above to see real-time status updates.</p>
          <a href="orders" class="btn btn-outline-primary mt-2">
            <i class="bi bi-bag-check me-1"></i> View My Orders
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>