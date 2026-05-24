<?php
include_once("../../app/middleware/admin.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/badges.php");

// Handle payment update — BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updatePayment'])) {
    $paymentId      = (int) $_POST['paymentId'];
    $newStatus      = $_POST['paymentStatus'] ?? '';
    $referenceNumber = trim($_POST['referenceNumber'] ?? '');

    $validStatuses = ['pending', 'paid', 'failed', 'refunded'];
    if (in_array($newStatus, $validStatuses)) {
        $paidAt = ($newStatus === 'paid') ? ", paidAt = NOW()" : "";
        $stmt = $conn->prepare("UPDATE payments SET status = ?, referenceNumber = ? $paidAt WHERE paymentId = ?");
        $stmt->bind_param("ssi", $newStatus, $referenceNumber, $paymentId);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Payment updated successfully.'];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid payment status.'];
    }
    header("Location: payments");
    exit;
}

include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');

$paymentsQuery = "SELECT 
    p.paymentId,
    p.orderId,
    o.orderNumber,
    o.status AS orderStatus,
    CONCAT(u.firstName, ' ', u.lastName) AS customerName,
    p.method,
    p.status,
    p.amount,
    p.referenceNumber,
    p.createdAt
FROM payments p
JOIN orders o ON p.orderId = o.orderId
JOIN users u ON o.userId = u.userId
ORDER BY p.createdAt DESC";

$paymentsResult = mysqli_query($conn, $paymentsQuery);
$payments = mysqli_fetch_all($paymentsResult, MYSQLI_ASSOC);
?>

<div class="pagetitle">
  <h1>Payments</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Payments</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-12">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Payment Transactions</h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Method</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Reference</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $row):
                $isCancelled = in_array($row['orderStatus'], ['cancelled', 'refunded']);
              ?>
                <tr class="<?= $isCancelled ? 'text-muted' : '' ?>">
                  <td class="fw-semibold"><?= htmlspecialchars($row['orderNumber']) ?></td>
                  <td><?= htmlspecialchars($row['customerName']) ?></td>
                  <td><?= strtoupper(str_replace('_', ' ', $row['method'])) ?></td>
                  <td><span class="badge <?= paymentBadge($row['status']) ?>"><?= ucfirst($row['status']) ?></span></td>
                  <td>₱<?= number_format($row['amount'], 2) ?></td>
                  <td><?= $row['referenceNumber'] ? htmlspecialchars($row['referenceNumber']) : '<span class="text-muted">N/A</span>' ?></td>
                  <td><?= date("M d, Y", strtotime($row['createdAt'])) ?></td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="paymentsView?id=<?= $row['paymentId'] ?>"
                         class="btn btn-sm btn-primary" title="View Details">
                        <i class="bi bi-eye"></i>
                      </a>
                      <button type="button"
                              class="btn btn-sm btn-outline-warning"
                              title="Update Payment"
                              onclick="openUpdatePayment(
                                <?= $row['paymentId'] ?>,
                                '<?= htmlspecialchars($row['orderNumber']) ?>',
                                '<?= $row['status'] ?>',
                                '<?= htmlspecialchars($row['referenceNumber'] ?? '') ?>'
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

<!-- Update Payment Modal -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="payments">
        <div class="modal-header" style="background:#005d21;">
          <h5 class="modal-title text-white">
            <i class="bi bi-pencil me-2"></i>Update Payment
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="paymentId" id="modal_paymentId">

          <div class="mb-3">
            <label class="form-label fw-semibold">Order #</label>
            <input type="text" id="modal_orderNumber" class="form-control" disabled
                   style="background:#f8f9fa;">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Payment Status <span class="text-danger">*</span></label>
            <select name="paymentStatus" id="modal_paymentStatus" class="form-select"
                    style="border-color:#d4e8da;" required>
              <option value="pending">Pending</option>
              <option value="paid">Paid</option>
              <option value="failed">Failed</option>
              <option value="refunded">Refunded</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Reference Number</label>
            <input type="text" name="referenceNumber" id="modal_referenceNumber"
                   class="form-control" placeholder="e.g. GCash ref, bank ref…"
                   style="border-color:#d4e8da;">
            <div class="form-text">Leave blank if not applicable.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="updatePayment" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openUpdatePayment(paymentId, orderNumber, status, referenceNumber) {
  document.getElementById('modal_paymentId').value       = paymentId;
  document.getElementById('modal_orderNumber').value     = orderNumber;
  document.getElementById('modal_paymentStatus').value   = status;
  document.getElementById('modal_referenceNumber').value = referenceNumber;
  new bootstrap.Modal(document.getElementById('updatePaymentModal')).show();
}
</script>

<?php include('./includes/footer.php'); ?>
