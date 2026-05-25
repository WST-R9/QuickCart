<?php
include_once("../../app/middleware/admin.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/notifications.php");

$refundId = (int) ($_GET['id'] ?? 0);

// Fetch refund request with order, user, and payment info
$stmt = $conn->prepare("
    SELECT rr.*,
           o.orderNumber, o.totalAmount, o.status AS orderStatus, o.orderedAt,
           CONCAT(u.firstName,' ',u.lastName) AS customerName,
           u.emailAddress, u.phoneNumber,
           p.method AS paymentMethod, p.status AS paymentStatus
    FROM refund_requests rr
    JOIN orders o ON rr.orderId = o.orderId
    JOIN users  u ON rr.userId  = u.userId
    LEFT JOIN payments p ON o.orderId = p.orderId
    WHERE rr.refundId = ?
    LIMIT 1
");
$stmt->bind_param("i", $refundId);
$stmt->execute();
$refund = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$refund) {
  $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Refund request not found.'];
  header("Location: refunds");
  exit;
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.productName, oi.quantity, oi.unitPrice, oi.subtotal
    FROM orderitems oi
    WHERE oi.orderId = ?
");
$stmt->bind_param("i", $refund['orderId']);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch shipping snapshot
$stmt = $conn->prepare("
    SELECT s.recipientName, s.phoneNumber AS shippingPhone,
           s.street, s.barangay, s.city, s.province, s.zipCode,
           s.courier, s.trackingNumber, s.status AS shippingStatus,
           s.shippedAt, s.deliveredAt
    FROM shipping s
    WHERE s.orderId = ?
    LIMIT 1
");
$stmt->bind_param("i", $refund['orderId']);
$stmt->execute();
$shipping = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $refund['status'] === 'pending') {
  $action = $_POST['action'] ?? '';

  if (in_array($action, ['approved', 'rejected'])) {
    $stmt = $conn->prepare("UPDATE refund_requests SET status = ?, updatedAt = NOW() WHERE refundId = ?");
    $stmt->bind_param("si", $action, $refundId);
    $stmt->execute();
    $stmt->close();

    if ($action === 'approved') {
      // Mark order as refunded
      $stmt = $conn->prepare("UPDATE orders SET status = 'refunded', updatedAt = NOW() WHERE orderId = ?");
      $stmt->bind_param("i", $refund['orderId']);
      $stmt->execute();
      $stmt->close();

      // Mark payment as refunded
      $stmt = $conn->prepare("UPDATE payments SET status = 'refunded' WHERE orderId = ?");
      $stmt->bind_param("i", $refund['orderId']);
      $stmt->execute();
      $stmt->close();
    }

    // Notify customer
    $msg = $action === 'approved'
      ? "Your refund request for order {$refund['orderNumber']} has been approved."
      : "Your refund request for order {$refund['orderNumber']} has been rejected.";

    createNotification(
      $conn,
      $refund['userId'],
      'customer',
      'refund_' . $action,
      'Refund Request ' . ucfirst($action),
      $msg,
      $refund['orderId'],
      'order'
    );

    $_SESSION['flash'] = [
      'type' => $action === 'approved' ? 'success' : 'warning',
      'message' => "Refund request has been {$action}."
    ];
    header("Location: refundsView?id=$refundId");
    exit;
  }
}

// Re-fetch after possible update
$stmt = $conn->prepare("SELECT status FROM refund_requests WHERE refundId = ?");
$stmt->bind_param("i", $refundId);
$stmt->execute();
$refund['status'] = $stmt->get_result()->fetch_assoc()['status'];
$stmt->close();

$onlinePayments = ['gcash', 'maya', 'credit_card', 'bank_transfer'];
$isOnline = in_array($refund['paymentMethod'], $onlinePayments);

$reasonLabels = [
  'wrong_item' => 'Wrong item received',
  'damaged_item' => 'Item was damaged or defective',
  'not_received' => 'Item not received',
  'missing_item' => 'Missing item',
  'changed_mind' => 'Changed my mind',
  'other' => 'Other',
];

$statusBadge = match ($refund['status']) {
  'approved' => 'bg-success',
  'rejected' => 'bg-danger',
  default => 'bg-warning text-dark',
};

include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
?>

<div class="pagetitle">
  <h1>Refund Request Details</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="refunds">Refund Requests</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($refund['orderNumber']) ?></li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row g-3">

    <!-- LEFT COLUMN -->
    <div class="col-lg-8">

      <!-- Refund Info -->
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h5 class="card-title mb-0">
                <i class="bi bi-arrow-repeat me-2 text-warning"></i>
                Refund / Return Request
              </h5>
              <div class="text-muted small mt-1">Submitted on
                <?= date('F d, Y \a\t h:i A', strtotime($refund['createdAt'])) ?></div>
            </div>
            <span class="badge <?= $statusBadge ?> fs-6"><?= ucfirst($refund['status']) ?></span>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <div class="text-muted small mb-1">Reason</div>
              <div class="fw-semibold">
                <?= $reasonLabels[$refund['reason']] ?? ucfirst(str_replace('_', ' ', $refund['reason'])) ?></div>
            </div>
            <div class="col-sm-6">
              <div class="text-muted small mb-1">Refund Method</div>
              <div class="fw-semibold"><?= ucfirst(str_replace('_', ' ', $refund['refundMethod'])) ?></div>
            </div>

            <?php if (!empty($refund['refundAccountType']) || !empty($refund['refundAccountName']) || !empty($refund['refundAccountNumber'])): ?>
              <div class="col-12">
                <div class="text-muted small mb-1">Refund Account Details</div>
                <div class="p-2 rounded" style="background:#f0faf3; border:1px solid #d4e8da;">
                  <?php if (!empty($refund['refundAccountType'])): ?>
                    <div class="small mb-1">
                      <span class="text-muted">Type:</span>
                      <span class="fw-semibold ms-1"><?= ucwords(str_replace('_', ' ', $refund['refundAccountType'])) ?></span>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($refund['refundAccountName'])): ?>
                    <div class="small mb-1">
                      <span class="text-muted">Account Name:</span>
                      <span class="fw-semibold ms-1"><?= htmlspecialchars($refund['refundAccountName']) ?></span>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($refund['refundAccountNumber'])): ?>
                    <div class="small mb-1">
                      <span class="text-muted">Account Number:</span>
                      <span class="fw-semibold ms-1"><?= htmlspecialchars($refund['refundAccountNumber']) ?></span>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($refund['refundBankName'])): ?>
                    <div class="small mb-0">
                      <span class="text-muted">Bank:</span>
                      <span class="fw-semibold ms-1"><?= htmlspecialchars($refund['refundBankName']) ?></span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
            <?php if ($refund['details']): ?>
              <div class="col-12">
                <div class="text-muted small mb-1">Details</div>
                <div class="p-2 rounded" style="background:#f8f9fa;border:1px solid #e9ecef;">
                  <?= nl2br(htmlspecialchars($refund['details'])) ?>
                </div>
              </div>
            <?php endif; ?>
            <?php if ($refund['imageProof']): ?>
              <div class="col-12">
                <div class="text-muted small mb-1">Proof Image</div>
                <a href="../uploads/refunds/<?= htmlspecialchars($refund['imageProof']) ?>" target="_blank">
                  <img src="../uploads/refunds/<?= htmlspecialchars($refund['imageProof']) ?>" alt="Proof"
                    class="img-thumbnail" style="max-height:220px;object-fit:cover;cursor:zoom-in;">
                </a>
              </div>
            <?php endif; ?>
          </div>

          <!-- Action Buttons -->
          <?php if ($refund['status'] === 'pending'): ?>
            <hr>
            <div class="d-flex gap-2 flex-wrap">
              <form method="POST" onsubmit="return confirm('Approve this refund request?')">
                <input type="hidden" name="action" value="approved">
                <button type="submit" class="btn btn-success fw-semibold px-4">
                  <i class="bi bi-check-lg me-1"></i>Approve
                </button>
              </form>
              <form method="POST" onsubmit="return confirm('Reject this refund request?')">
                <input type="hidden" name="action" value="rejected">
                <button type="submit" class="btn btn-danger fw-semibold px-4">
                  <i class="bi bi-x-lg me-1"></i>Reject
                </button>
              </form>
            </div>
          <?php elseif ($refund['status'] === 'approved'): ?>
            <hr>
            <div class="alert alert-success mb-0 py-2">
              <i class="bi bi-check-circle me-2"></i>This request has been <strong>approved</strong>.
              <?= $isOnline ? 'Refund will be returned to the original payment method or as store credit.' : 'Customer should return the item.' ?>
            </div>
          <?php else: ?>
            <hr>
            <div class="alert alert-danger mb-0 py-2">
              <i class="bi bi-x-circle me-2"></i>This request has been <strong>rejected</strong>.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Order Items -->
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-bag me-2 text-success"></i>Order Items</h5>
          <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0">
              <thead style="background:#e6f4ea;">
                <tr>
                  <th>Product</th>
                  <th class="text-center">Qty</th>
                  <th class="text-end">Unit Price</th>
                  <th class="text-end">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <tr>
                    <td><?= htmlspecialchars($item['productName']) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-end">₱<?= number_format($item['unitPrice'], 2) ?></td>
                    <td class="text-end">₱<?= number_format($item['subtotal'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3" class="text-end fw-bold">Total</td>
                  <td class="text-end fw-bold">₱<?= number_format($refund['totalAmount'], 2) ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Shipping Info -->
      <?php if ($shipping): ?>
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-truck me-2 text-primary"></i>Shipping Info</h5>
            <div class="row g-2">
              <div class="col-sm-6">
                <div class="text-muted small">Recipient</div>
                <div class="fw-semibold"><?= htmlspecialchars($shipping['recipientName']) ?></div>
              </div>
              <div class="col-sm-6">
                <div class="text-muted small">Phone</div>
                <div><?= htmlspecialchars($shipping['shippingPhone']) ?></div>
              </div>
              <div class="col-12">
                <div class="text-muted small">Address</div>
                <div>
                  <?= htmlspecialchars($shipping['street'] . ', ' . $shipping['barangay'] . ', ' . $shipping['city'] . ', ' . $shipping['province'] . ' ' . $shipping['zipCode']) ?>
                </div>
              </div>
              <?php if ($shipping['courier']): ?>
                <div class="col-sm-6">
                  <div class="text-muted small">Courier</div>
                  <div><?= htmlspecialchars($shipping['courier']) ?></div>
                </div>
              <?php endif; ?>
              <?php if ($shipping['trackingNumber']): ?>
                <div class="col-sm-6">
                  <div class="text-muted small">Tracking #</div>
                  <div class="fw-semibold"><?= htmlspecialchars($shipping['trackingNumber']) ?></div>
                </div>
              <?php endif; ?>
              <div class="col-sm-6">
                <div class="text-muted small">Shipping Status</div>
                <div><?= ucfirst(str_replace('_', ' ', $shipping['shippingStatus'])) ?></div>
              </div>
              <?php if ($shipping['deliveredAt']): ?>
                <div class="col-sm-6">
                  <div class="text-muted small">Delivered At</div>
                  <div><?= date('M d, Y h:i A', strtotime($shipping['deliveredAt'])) ?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT COLUMN -->
    <div class="col-lg-4">

      <!-- Customer Info -->
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-person me-2 text-success"></i>Customer</h5>
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
              style="width:48px;height:48px;background:#005d21;font-size:18px;flex-shrink:0;">
              <?= strtoupper(substr($refund['customerName'], 0, 1)) ?>
            </div>
            <div>
              <div class="fw-bold"><?= htmlspecialchars($refund['customerName']) ?></div>
              <div class="text-muted small"><?= htmlspecialchars($refund['emailAddress']) ?></div>
              <div class="text-muted small"><?= htmlspecialchars($refund['phoneNumber']) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-receipt me-2 text-success"></i>Order Summary</h5>
          <div class="mb-2">
            <div class="text-muted small">Order Number</div>
            <div class="fw-bold"><?= htmlspecialchars($refund['orderNumber']) ?></div>
          </div>
          <div class="mb-2">
            <div class="text-muted small">Order Date</div>
            <div><?= date('M d, Y', strtotime($refund['orderedAt'])) ?></div>
          </div>
          <div class="mb-2">
            <div class="text-muted small">Order Status</div>
            <div>
              <?php
              $osBadge = match ($refund['orderStatus']) {
                'delivered' => 'bg-success',
                'cancelled' => 'bg-danger',
                'refunded' => 'bg-secondary',
                'shipped' => 'bg-info',
                'processing' => 'bg-primary',
                default => 'bg-warning text-dark',
              };
              ?>
              <span class="badge <?= $osBadge ?>"><?= ucfirst($refund['orderStatus']) ?></span>
            </div>
          </div>
          <div class="mb-2">
            <div class="text-muted small">Payment Method</div>
            <div><?= ucfirst(str_replace('_', ' ', $refund['paymentMethod'])) ?></div>
          </div>
          <div class="mb-2">
            <div class="text-muted small">Payment Status</div>
            <div>
              <?php
              $psBadge = match ($refund['paymentStatus']) {
                'paid' => 'bg-success',
                'refunded' => 'bg-secondary',
                'failed' => 'bg-danger',
                'cancelled' => 'bg-danger',
                default => 'bg-warning text-dark',
              };
              ?>
              <span class="badge <?= $psBadge ?>"><?= ucfirst($refund['paymentStatus']) ?></span>
            </div>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between fw-bold">
            <span>Total Amount</span>
            <span>₱<?= number_format($refund['totalAmount'], 2) ?></span>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-link-45deg me-2 text-success"></i>Quick Links</h5>
          <div class="d-grid gap-2">
            <a href="orderView?id=<?= $refund['orderId'] ?>" class="btn btn-outline-success btn-sm">
              <i class="bi bi-bag me-1"></i>View Full Order
            </a>
            <a href="refunds" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-arrow-left me-1"></i>Back to Refund Requests
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include('./includes/footer.php'); ?>