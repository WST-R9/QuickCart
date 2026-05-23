<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$userId = $_SESSION['authUser']['userId'] ?? 0;

// ----------------------------------------
// FILTERS
// ----------------------------------------
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$allowedStatuses = ['pending','confirmed','processing','shipped','delivered','cancelled','refunded'];

$where  = "o.userId = ?";
$params = [$userId];
$types  = 'i';

if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses)) {
    $where   .= " AND o.status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}

if ($search !== '') {
    $where   .= " AND o.orderNumber LIKE ?";
    $params[] = "%$search%";
    $types   .= 's';
}

// Count
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM orders o WHERE $where");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalOrders = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = max(1, (int)ceil($totalOrders / $perPage));

// Orders
$stmt = $conn->prepare(
    "SELECT o.orderId, o.orderNumber, o.totalAmount, o.status, o.orderedAt
     FROM orders o
     WHERE $where
     ORDER BY o.orderedAt DESC
     LIMIT ? OFFSET ?"
);
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes  = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$ordersResult = $stmt->get_result();

$badgeMap = [
    'pending'    => 'bg-warning text-dark',
    'confirmed'  => 'bg-primary',
    'processing' => 'bg-info text-dark',
    'shipped'    => 'bg-dark',
    'delivered'  => 'bg-success',
    'cancelled'  => 'bg-danger',
    'refunded'   => 'bg-secondary',
];
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

  <!-- Filter Bar -->
  <div class="filter-bar">
    <form method="GET" action="" class="row g-2 align-items-center">
      <div class="col-12 col-md-4">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" class="form-control"
                 placeholder="Search by order number…"
                 value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>
      <div class="col-6 col-md-3">
        <select name="status" class="form-select">
          <option value="">All Statuses</option>
          <?php foreach ($allowedStatuses as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>>
              <?= ucfirst($s) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-funnel me-1"></i> Filter
        </button>
      </div>
      <?php if ($search || $statusFilter): ?>
        <div class="col-12 col-md-2">
          <a href="orders" class="btn btn-outline-secondary w-100">
            <i class="bi bi-x-circle me-1"></i> Clear
          </a>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- Results meta -->
  <div class="results-meta">
    Showing <strong><?= $totalOrders ?></strong> order<?= $totalOrders !== 1 ? 's' : '' ?>
    <?php if ($statusFilter): ?> with status "<strong><?= ucfirst($statusFilter) ?></strong>"<?php endif; ?>
  </div>

  <!-- Orders Table -->
  <div class="card">
    <div class="card-body">
      <?php if ($ordersResult->num_rows === 0): ?>
        <div class="empty-state">
          <i class="bi bi-bag-x"></i>
          <h5>No orders found</h5>
          <p>
            <?php if ($search || $statusFilter): ?>
              Try clearing your filters.
            <?php else: ?>
              You haven't placed any orders yet.
            <?php endif; ?>
          </p>
          <a href="allProducts" class="btn btn-primary mt-2">
            <i class="bi bi-shop me-1"></i> Start Shopping
          </a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-borderless orders-table align-middle">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php while ($order = $ordersResult->fetch_assoc()):
                $s     = $order['status'];
                $badge = $badgeMap[$s] ?? 'bg-secondary';
              ?>
                <tr>
                  <td class="fw-bold"><?= htmlspecialchars($order['orderNumber']) ?></td>
                  <td>₱<?= number_format($order['totalAmount'], 2) ?></td>
                  <td><span class="badge <?= $badge ?>"><?= ucfirst($s) ?></span></td>
                  <td class="text-muted small"><?= date("M d, Y", strtotime($order['orderedAt'])) ?></td>
                  <td>
                    <a href="order-detail?id=<?= (int)$order['orderId'] ?>"
                       class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-eye me-1"></i> View
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <nav class="mt-3 d-flex justify-content-center flex-wrap gap-2">
            <ul class="pagination">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>">
                  <i class="bi bi-chevron-left"></i>
                </a>
              </li>
              <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>">
                  <i class="bi bi-chevron-right"></i>
                </a>
              </li>
            </ul>
            <p class="text-muted small w-100 text-center">Page <?= $page ?> of <?= $totalPages ?></p>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

</section>

<?php include('includes/footer.php'); ?>