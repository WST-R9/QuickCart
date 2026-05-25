<?php
include_once("../../app/middleware/admin.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/badges.php");

// Handle shipping update — BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateShipping'])) {
  $shippingId = (int) $_POST['shippingId'];
  $newStatus = $_POST['shippingStatus'] ?? '';
  $courier = trim($_POST['courier'] ?? '');
  $trackingNumber = trim($_POST['trackingNumber'] ?? '');

  $validStatuses = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'returned', 'cancelled'];

  if (in_array($newStatus, $validStatuses)) {
    $shippedAt = ($newStatus === 'shipped' || $newStatus === 'out_for_delivery') ? ", shippedAt = NOW()" : "";
    $deliveredAt = ($newStatus === 'delivered') ? ", deliveredAt = NOW()" : "";

    $stmt = $conn->prepare("UPDATE shipping SET status = ?, courier = ?, trackingNumber = ? $shippedAt $deliveredAt WHERE shippingId = ?");
    $stmt->bind_param("sssi", $newStatus, $courier, $trackingNumber, $shippingId);
    $stmt->execute();
    $stmt->close();

    // Notify the customer of the shipping update
    include_once("../../app/helpers/notifications.php");
    $orderInfo = $conn->query("
            SELECT o.userId, o.orderId, o.orderNumber
            FROM shipping s
            JOIN orders o ON s.orderId = o.orderId
            WHERE s.shippingId = $shippingId
        ")->fetch_assoc();

    if ($orderInfo) {
      notifyCustomerShippingUpdate(
        $conn,
        $orderInfo['userId'],
        $orderInfo['orderId'],
        $orderInfo['orderNumber'],
        $newStatus
      );
    }

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Shipping record updated successfully.'];
  } else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid shipping status.'];
  }
  header("Location: shipping");
  exit;
}

include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');

$shippingQuery = "SELECT 
    s.shippingId,
    s.orderId,
    o.orderNumber,
    o.status AS orderStatus,
    CONCAT(u.firstName, ' ', u.lastName) AS customerName,
    s.courier,
    s.trackingNumber,
    s.status,
    s.city,
    s.province,
    s.createdAt
FROM shipping s
JOIN orders o ON s.orderId = o.orderId
JOIN users u ON o.userId = u.userId
ORDER BY s.createdAt DESC";

$shippingResult = mysqli_query($conn, $shippingQuery);
$shippings = mysqli_fetch_all($shippingResult, MYSQLI_ASSOC);
?>

<div class="pagetitle">
  <h1>Shipping</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Shipping</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-12">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Shipping Records</h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Courier</th>
                <th>Tracking</th>
                <th>Order Status</th>
                <th>Shipping Status</th>
                <th>Location</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shippings as $row):
                $isCancelled = in_array($row['orderStatus'], ['cancelled', 'refunded']);
                ?>
                <tr class="<?= $isCancelled ? 'text-muted' : '' ?>">
                  <td class="fw-semibold"><?= htmlspecialchars($row['orderNumber']) ?></td>
                  <td><?= htmlspecialchars($row['customerName']) ?></td>
                  <td><?= $row['courier'] ? htmlspecialchars($row['courier']) : '<span class="text-muted">N/A</span>' ?>
                  </td>
                  <td>
                    <?= $row['trackingNumber'] ? htmlspecialchars($row['trackingNumber']) : '<span class="text-muted">N/A</span>' ?>
                  </td>
                  <td><span
                      class="badge <?= orderBadge($row['orderStatus']) ?>"><?= ucfirst($row['orderStatus']) ?></span></td>
                  <td><span
                      class="badge <?= shippingBadge($row['status']) ?>"><?= ucfirst(str_replace('_', ' ', $row['status'])) ?></span>
                  </td>
                  <td><?= htmlspecialchars($row['city'] . ', ' . $row['province']) ?></td>
                  <td><?= date("M d, Y", strtotime($row['createdAt'])) ?></td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="shippingView?id=<?= $row['shippingId'] ?>" class="btn btn-sm btn-primary"
                        title="View Details">
                        <i class="bi bi-eye"></i>
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-warning" title="Update Shipping" onclick="openUpdateShipping(
                                <?= $row['shippingId'] ?>,
                                '<?= htmlspecialchars($row['orderNumber']) ?>',
                                '<?= $row['status'] ?>',
                                '<?= htmlspecialchars($row['courier'] ?? '') ?>',
                                '<?= htmlspecialchars($row['trackingNumber'] ?? '') ?>'
                              )">
                        <i class="bi bi-pencil"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

        </div>
      </div>
    </div>
  </div>
</section>

<!-- Update Shipping Modal -->
<div class="modal fade" id="updateShippingModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="shipping">
        <div class="modal-header" style="background:#005d21;">
          <h5 class="modal-title text-white">
            <i class="bi bi-truck me-2"></i>Update Shipping
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="shippingId" id="modal_shippingId">

          <div class="mb-3">
            <label class="form-label fw-semibold">Order #</label>
            <input type="text" id="modal_orderNumber" class="form-control" disabled style="background:#f8f9fa;">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Shipping Status <span class="text-danger">*</span></label>
            <select name="shippingStatus" id="modal_shippingStatus" class="form-select" style="border-color:#d4e8da;"
              required>
              <option value="pending">Pending</option>
              <option value="processing">Processing</option>
              <option value="shipped">Shipped</option>
              <option value="out_for_delivery">Out for Delivery</option>
              <option value="delivered">Delivered</option>
              <option value="returned">Returned</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Courier</label>
            <input type="text" name="courier" id="modal_courier" class="form-control"
              placeholder="e.g. J&T, LBC, In-House Delivery…" style="border-color:#d4e8da;">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tracking Number</label>
            <input type="text" name="trackingNumber" id="modal_trackingNumber" class="form-control"
              placeholder="e.g. TRK-20260524-XXXXX" style="border-color:#d4e8da;">
            <div class="form-text">Leave blank if not yet available.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="updateShipping" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function openUpdateShipping(shippingId, orderNumber, status, courier, trackingNumber) {
    document.getElementById('modal_shippingId').value = shippingId;
    document.getElementById('modal_orderNumber').value = orderNumber;
    document.getElementById('modal_shippingStatus').value = status;
    document.getElementById('modal_courier').value = courier;
    document.getElementById('modal_trackingNumber').value = trackingNumber;
    new bootstrap.Modal(document.getElementById('updateShippingModal')).show();
  }
</script>

<?php include('./includes/footer.php'); ?>