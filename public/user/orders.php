<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$userId = $_SESSION['authUser']['userId'] ?? 0;
$status = $_GET['status'] ?? '';

$where  = "o.userId = ?";
$params = [$userId];
$types  = 'i';

if ($status !== '') {
    $where  .= " AND o.status = ?";
    $params[] = $status;
    $types   .= 's';
}

$stmt = $conn->prepare("
    SELECT o.orderId, o.orderNumber, o.totalAmount, o.status, o.orderedAt,
           COUNT(oi.orderItemId) AS itemCount
    FROM orders o
    LEFT JOIN orderitems oi ON o.orderId = oi.orderId
    WHERE $where
    GROUP BY o.orderId
    ORDER BY o.orderedAt DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statuses = ['pending','confirmed','processing','shipped','delivered','cancelled','refunded'];
?>

<div class="pagetitle">
  <h1>My Orders</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">My Orders</li>
    </ol>
  </nav>
</div>

<section class="section">

  <!-- Status filter tabs -->
  <div class="d-flex align-items-center mb-3 px-3 py-2 bg-white rounded-3 border"
       style="border-color:#d4e8da !important; overflow-x:auto; gap:8px; flex-wrap:nowrap;">
    <a href="orders" class="pill <?= $status === '' ? 'active' : '' ?>">All</a>
    <?php foreach ($statuses as $s): ?>
      <a href="orders?status=<?= $s ?>" class="pill <?= $status === $s ? 'active' : '' ?>">
        <?= ucfirst($s) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($orders)): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state">
          <i class="bi bi-bag-x"></i>
          <h5>No orders found</h5>
          <p><?= $status ? 'No ' . $status . ' orders.' : 'You have not placed any orders yet.' ?></p>
          <a href="allProducts" class="btn btn-primary mt-2">
            <i class="bi bi-bag me-1"></i> Start Shopping
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Orders <span>(<?= count($orders) ?>)</span></h5>
        <div class="table-responsive">
          <table class="table table-borderless table-hover">
            <thead style="background:#e6f4ea;">
              <tr>
                <th>Order #</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order):
                $badge = match($order['status']) {
                    'pending'    => 'bg-warning text-dark',
                    'confirmed'  => 'bg-primary',
                    'processing' => 'bg-info text-dark',
                    'shipped'    => 'bg-dark',
                    'delivered'  => 'bg-success',
                    'cancelled'  => 'bg-danger',
                    'refunded'   => 'bg-secondary',
                    default      => 'bg-secondary',
                };
              ?>
              <tr>
                <td class="fw-bold"><?= htmlspecialchars($order['orderNumber']) ?></td>
                <td><?= $order['itemCount'] ?> item<?= $order['itemCount'] != 1 ? 's' : '' ?></td>
                <td class="fw-bold text-success">₱<?= number_format($order['totalAmount'], 2) ?></td>
                <td><span class="badge <?= $badge ?>"><?= ucfirst($order['status']) ?></span></td>
                <td><?= date('M d, Y', strtotime($order['orderedAt'])) ?></td>
                <td>
                  <a href="orderView?id=<?= $order['orderId'] ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-eye me-1"></i> View
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>