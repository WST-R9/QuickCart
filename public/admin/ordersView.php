<?php
include_once("../../app/middleware/admin.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/activityLog.php");
include_once("../../app/helpers/badges.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
  echo "<script>alert('Order not found!'); window.location.href='orders.php';</script>";
  exit;
}

$orderId = intval($_GET['id']);

// --------------------------------
// UPDATE ORDER STATUS
// --------------------------------
if (isset($_POST['updateOrderStatus'])) {
  $newStatus = mysqli_real_escape_string($conn, $_POST['orderStatus']);
  $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

  if (!in_array($newStatus, $validStatuses)) {
    $_SESSION['message'] = "Invalid status.";
    $_SESSION['code'] = "error";
    header("Location: ordersView.php?id=$orderId");
    exit;
  }

  mysqli_query($conn, "UPDATE orders SET status='$newStatus' WHERE orderId=$orderId");

  if ($newStatus === 'confirmed') {
    $checkShipping = mysqli_query($conn, "SELECT shippingId FROM shipping WHERE orderId=$orderId");

    if (mysqli_num_rows($checkShipping) == 0) {
      $orderUserQuery = mysqli_query($conn, "SELECT userId FROM orders WHERE orderId=$orderId");
      $orderUser = mysqli_fetch_assoc($orderUserQuery);
      $userId = $orderUser['userId'];

      $addrQuery = mysqli_query($conn, "
                SELECT * FROM addresses 
                WHERE userId=$userId AND isDefault=1 
                LIMIT 1
            ");
      $addr = mysqli_fetch_assoc($addrQuery);

      if ($addr) {
        $recipientName = mysqli_real_escape_string($conn, $addr['recipientName']);
        $phoneNumber = mysqli_real_escape_string($conn, $addr['phoneNumber']);
        $street = mysqli_real_escape_string($conn, $addr['street']);
        $barangay = mysqli_real_escape_string($conn, $addr['barangay']);
        $city = mysqli_real_escape_string($conn, $addr['city']);
        $province = mysqli_real_escape_string($conn, $addr['province'] ?? '');
        $zipCode = mysqli_real_escape_string($conn, $addr['zipCode'] ?? '');
        $addressId = $addr['addressId'];

        mysqli_query($conn, "
                    INSERT INTO shipping 
                        (orderId, addressId, status, recipientName, phoneNumber, street, barangay, city, province, zipCode)
                    VALUES 
                        ($orderId, $addressId, 'preparing', '$recipientName', '$phoneNumber', '$street', '$barangay', '$city', '$province', '$zipCode')
                ");
      }
    }
  }

  if ($newStatus === 'shipped') {
    mysqli_query($conn, "
            UPDATE shipping 
            SET status='shipped', shippedAt=NOW() 
            WHERE orderId=$orderId
        ");
  }

  if ($newStatus === 'delivered') {
    mysqli_query($conn, "
            UPDATE shipping 
            SET status='delivered', deliveredAt=NOW() 
            WHERE orderId=$orderId
        ");

    $paymentCheck = mysqli_query($conn, "
            SELECT paymentId, method, status 
            FROM payments 
            WHERE orderId=$orderId 
            LIMIT 1
        ");
    $payment = mysqli_fetch_assoc($paymentCheck);

    if ($payment && $payment['method'] === 'cod' && $payment['status'] === 'pending') {
      mysqli_query($conn, "
                UPDATE payments 
                SET status='paid', paidAt=NOW() 
                WHERE orderId=$orderId
            ");
    }
  }

  if ($newStatus === 'cancelled') {
    // Restore stock
    $itemsToRestore = mysqli_query($conn, "SELECT productId, quantity FROM orderitems WHERE orderId=$orderId");
    while ($item = mysqli_fetch_assoc($itemsToRestore)) {
      if ($item['productId']) {
        mysqli_query($conn, "
                UPDATE products 
                SET stock = stock + {$item['quantity']} 
                WHERE productId = {$item['productId']}
            ");
      }
    }

    // Set shipping to cancelled (not returned)
    mysqli_query($conn, "
        UPDATE shipping SET status='cancelled' WHERE orderId=$orderId
    ");

    // Set payment to cancelled if still pending
    mysqli_query($conn, "
        UPDATE payments SET status='cancelled' 
        WHERE orderId=$orderId AND status='pending'
    ");
  }

  $orderForLog = mysqli_fetch_assoc(mysqli_query($conn, "SELECT orderNumber FROM orders WHERE orderId=$orderId"));

  $_SESSION['message'] = "Order status updated to " . ucfirst($newStatus) . ".";
  $_SESSION['code'] = "success";
  logActivity($conn, $_SESSION['user_id'], 'updated_order_status', 'orders', $orderId, $orderForLog['orderNumber'], "Status changed to $newStatus");
  header("Location: ordersView.php?id=$orderId");
  exit;
}

// --------------------------------
// UPDATE SHIPPING STATUS
// --------------------------------
if (isset($_POST['updateShippingStatus'])) {
  $newShippingStatus = mysqli_real_escape_string($conn, $_POST['shippingStatus']);
  $validShippingStatuses = ['preparing', 'shipped', 'out_for_delivery', 'delivered', 'returned'];

  if (!in_array($newShippingStatus, $validShippingStatuses)) {
    $_SESSION['message'] = "Invalid shipping status.";
    $_SESSION['code'] = "error";
    header("Location: ordersView.php?id=$orderId");
    exit;
  }

  $shippedAt = $newShippingStatus === 'shipped' ? ", shippedAt=NOW()" : "";
  $deliveredAt = $newShippingStatus === 'delivered' ? ", deliveredAt=NOW()" : "";

  mysqli_query($conn, "
        UPDATE shipping 
        SET status='$newShippingStatus' $shippedAt $deliveredAt
        WHERE orderId=$orderId
    ");

  if ($newShippingStatus === 'shipped') {
    mysqli_query($conn, "UPDATE orders SET status='shipped' WHERE orderId=$orderId");
  }

  if ($newShippingStatus === 'out_for_delivery') {
    mysqli_query($conn, "UPDATE orders SET status='shipped' WHERE orderId=$orderId");
  }

  if ($newShippingStatus === 'delivered') {
    mysqli_query($conn, "UPDATE orders SET status='delivered' WHERE orderId=$orderId");

    $paymentCheck = mysqli_query($conn, "
            SELECT paymentId, method, status 
            FROM payments 
            WHERE orderId=$orderId 
            LIMIT 1
        ");
    $paymentRow = mysqli_fetch_assoc($paymentCheck);

    if ($paymentRow && $paymentRow['method'] === 'cod' && $paymentRow['status'] === 'pending') {
      mysqli_query($conn, "
                UPDATE payments 
                SET status='paid', paidAt=NOW() 
                WHERE orderId=$orderId
            ");
    }
  }

  $orderForLog = mysqli_fetch_assoc(mysqli_query($conn, "SELECT orderNumber FROM orders WHERE orderId=$orderId"));

  $_SESSION['message'] = "Shipping status updated to " . ucfirst(str_replace('_', ' ', $newShippingStatus)) . ".";
  $_SESSION['code'] = "success";
  logActivity($conn, $_SESSION['user_id'], 'updated_shipping_status', 'orders', $orderId, $orderForLog['orderNumber'], "Shipping changed to $newShippingStatus");
  header("Location: ordersView.php?id=$orderId");
  exit;
}

// --------------------------------
// UPDATE SHIPPING DETAILS (courier + tracking)
// --------------------------------
if (isset($_POST['updateShippingDetails'])) {
  $courier = mysqli_real_escape_string($conn, trim($_POST['courier']));
  $trackingNumber = mysqli_real_escape_string($conn, trim($_POST['trackingNumber']));

  mysqli_query($conn, "
        UPDATE shipping 
        SET courier='$courier', trackingNumber='$trackingNumber'
        WHERE orderId=$orderId
    ");

  $_SESSION['message'] = "Shipping details updated.";
  $_SESSION['code'] = "success";
  header("Location: ordersView.php?id=$orderId");
  exit;
}

// --------------------------------
// UPLOAD PROOF OF DELIVERY
// --------------------------------
if (isset($_POST['uploadProof'])) {
  if (!empty($_FILES['proofImage']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['proofImage']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
      $_SESSION['message'] = "Invalid file type. Only JPG, PNG, and WEBP allowed.";
      $_SESSION['code'] = "error";
      header("Location: ordersView.php?id=$orderId");
      exit;
    }

    $filename = 'pod_' . $orderId . '_' . time() . '.' . $ext;
    $uploadDir = '../uploads/proof/';

    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($_FILES['proofImage']['tmp_name'], $uploadDir . $filename)) {
      $safeFilename = mysqli_real_escape_string($conn, $filename);
      mysqli_query($conn, "
                UPDATE shipping 
                SET proofOfDelivery='$safeFilename'
                WHERE orderId=$orderId
            ");

      $_SESSION['message'] = "Proof of delivery uploaded successfully.";
      $_SESSION['code'] = "success";
    } else {
      $_SESSION['message'] = "Failed to upload file. Check folder permissions.";
      $_SESSION['code'] = "error";
    }
  } else {
    $_SESSION['message'] = "No file selected.";
    $_SESSION['code'] = "error";
  }

  header("Location: ordersView.php?id=$orderId");
  exit;
}

include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');

// ORDER INFO
$orderQuery = "SELECT 
    o.*,
    CONCAT(u.firstName, ' ', u.lastName) AS customerName,
    u.emailAddress,
    u.phoneNumber
FROM orders o
JOIN users u ON o.userId = u.userId
WHERE o.orderId = $orderId";

$orderResult = mysqli_query($conn, $orderQuery);

if (mysqli_num_rows($orderResult) == 0) {
  echo "<script>alert('Order not found!'); window.location.href='orders.php';</script>";
  exit;
}

$order = mysqli_fetch_assoc($orderResult);

// PAYMENT INFO
$paymentQuery = "SELECT * FROM payments WHERE orderId = $orderId LIMIT 1";
$paymentResult = mysqli_query($conn, $paymentQuery);
$payment = mysqli_fetch_assoc($paymentResult);

// SHIPPING INFO
$shippingQuery = "SELECT * FROM shipping WHERE orderId = $orderId LIMIT 1";
$shippingResult = mysqli_query($conn, $shippingQuery);
$shipping = mysqli_fetch_assoc($shippingResult);

// ORDER ITEMS
$itemsQuery = "SELECT * FROM orderitems WHERE orderId = $orderId";
$itemsResult = mysqli_query($conn, $itemsQuery);
?>

<div class="pagetitle">
  <h1>Order Details</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="orders.php">Orders</a></li>
      <li class="breadcrumb-item active">View Order</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">

    <!-- LEFT -->
    <div class="col-lg-8">

      <!-- ORDER INFO -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Order Information</h5>

          <div class="row g-3">
            <div class="col-md-6">
              <p class="mb-1 text-muted small">Order Number</p>
              <p class="fw-semibold"><?= htmlspecialchars($order['orderNumber']) ?></p>
            </div>
            <div class="col-md-6">
              <p class="mb-1 text-muted small">Order Status</p>
              <span class="badge <?= orderBadge($order['status']) ?>">
                <?= ucfirst($order['status']) ?>
              </span>
            </div>
            <div class="col-md-6">
              <p class="mb-1 text-muted small">Total Amount</p>
              <p class="fw-bold text-success">₱<?= number_format($order['totalAmount'], 2) ?></p>
            </div>
            <div class="col-md-6">
              <p class="mb-1 text-muted small">Ordered At</p>
              <p class="fw-semibold"><?= date("M d, Y h:i A", strtotime($order['orderedAt'])) ?></p>
            </div>
            <?php if (!empty($order['notes'])): ?>
              <div class="col-12">
                <p class="mb-1 text-muted small">Notes</p>
                <p class="fw-semibold"><?= htmlspecialchars($order['notes']) ?></p>
              </div>
            <?php endif; ?>
          </div>

          <!-- ORDER STATUS UPDATE -->
          <?php if (!in_array($order['status'], ['delivered', 'cancelled', 'refunded'])): ?>
            <hr>
            <form method="POST" class="d-flex align-items-center gap-2 flex-wrap">
              <label class="fw-semibold mb-0">Update Order Status:</label>
              <select name="orderStatus" class="form-select form-select-sm" style="width:auto;">
                <?php
                $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
                foreach ($statuses as $s):
                  ?>
                  <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" name="updateOrderStatus" class="btn btn-primary btn-sm">
                <i class="bi bi-check-circle me-1"></i> Update
              </button>
            </form>
          <?php else: ?>
            <hr>
            <p class="text-muted small mb-0">
              <i class="bi bi-lock me-1"></i>
              Order is <strong><?= ucfirst($order['status']) ?></strong> — no further status changes allowed.
            </p>
          <?php endif; ?>

        </div>
      </div>

      <!-- ORDER ITEMS -->
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Order Items</h5>

          <table class="table table-borderless">
            <thead>
              <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($item = mysqli_fetch_assoc($itemsResult)): ?>
                <tr>
                  <td><?= htmlspecialchars($item['productName']) ?></td>
                  <td><?= $item['quantity'] ?></td>
                  <td>₱<?= number_format($item['unitPrice'], 2) ?></td>
                  <td>₱<?= number_format($item['quantity'] * $item['unitPrice'], 2) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>

        </div>
      </div>

      <!-- PROOF OF DELIVERY -->
      <?php if ($shipping && in_array($order['status'], ['delivered', 'refunded'])): ?>
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">
              <i class="bi bi-camera me-1"></i> Proof of Delivery
            </h5>

            <?php if (!empty($shipping['proofOfDelivery'])): ?>
              <!-- Existing proof -->
              <div class="mb-3">
                <img src="../uploads/proof/<?= htmlspecialchars($shipping['proofOfDelivery']) ?>" alt="Proof of Delivery"
                  class="img-fluid rounded border" style="max-height: 320px; object-fit: cover; cursor: pointer;"
                  data-bs-toggle="modal" data-bs-target="#proofModal">
                <p class="text-muted small mt-2 mb-0">
                  <i class="bi bi-check-circle-fill text-success me-1"></i>
                  Proof of delivery on file. Click image to enlarge.
                </p>
              </div>

              <!-- Replace proof -->
              <hr>
              <p class="small fw-semibold mb-2">Replace Proof of Delivery</p>
              <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap">
                <input type="file" name="proofImage" class="form-control form-control-sm" accept="image/*"
                  style="width:auto;" required>
                <button type="submit" name="uploadProof" class="btn btn-outline-primary btn-sm">
                  <i class="bi bi-arrow-repeat me-1"></i> Replace
                </button>
              </form>

              <!-- Lightbox Modal -->
              <div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                      <h6 class="modal-title">Proof of Delivery</h6>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                      <img src="../uploads/proof/<?= htmlspecialchars($shipping['proofOfDelivery']) ?>"
                        alt="Proof of Delivery" class="img-fluid rounded">
                    </div>
                  </div>
                </div>
              </div>

            <?php else: ?>
              <!-- No proof yet -->
              <p class="text-muted small mb-3">
                <i class="bi bi-hourglass-split me-1"></i>
                No proof of delivery uploaded yet.
              </p>
              <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap">
                <input type="file" name="proofImage" class="form-control form-control-sm" accept="image/*"
                  style="width:auto;" required>
                <button type="submit" name="uploadProof" class="btn btn-primary btn-sm">
                  <i class="bi bi-upload me-1"></i> Upload Proof
                </button>
              </form>
            <?php endif; ?>

          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT -->
    <div class="col-lg-4">

      <!-- CUSTOMER INFO -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Customer</h5>

          <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($order['customerName']) ?></p>
          <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($order['emailAddress']) ?></p>
          <p class="mb-3"><strong>Phone:</strong> <?= htmlspecialchars($order['phoneNumber']) ?></p>

          <a href="customersView.php?id=<?= $order['userId'] ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-person me-1"></i> View Customer
          </a>
        </div>
      </div>

      <!-- PAYMENT INFO -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Payment</h5>

          <?php
          $isCancelled = in_array($order['status'], ['cancelled', 'refunded']);
          ?>

          <?php if ($isCancelled): ?>
            <div
              class="alert alert-<?= $order['status'] === 'refunded' ? 'secondary' : 'warning' ?> py-2 px-3 small mb-3">
              <i class="bi bi-info-circle me-1"></i>
              Order is <strong><?= ucfirst($order['status']) ?></strong>.
              <?= ($payment && $payment['status'] === 'pending') ? 'Payment was never collected.' : '' ?>
            </div>
          <?php endif; ?>

          <?php if ($payment): ?>
            <p class="mb-1"><strong>Method:</strong> <?= strtoupper(str_replace('_', ' ', $payment['method'])) ?></p>
            <p class="mb-1"><strong>Status:</strong>
              <span class="badge <?= paymentBadge($payment['status']) ?>"><?= ucfirst($payment['status']) ?></span>
            </p>
            <p class="mb-1"><strong>Amount:</strong> ₱<?= number_format($payment['amount'], 2) ?></p>
            <?php if (!empty($payment['referenceNumber'])): ?>
              <p class="mb-1"><strong>Reference:</strong> <?= htmlspecialchars($payment['referenceNumber']) ?></p>
            <?php endif; ?>
            <?php if ($payment['paidAt']): ?>
              <p class="mb-0"><strong>Paid At:</strong> <?= date("M d, Y h:i A", strtotime($payment['paidAt'])) ?></p>
            <?php endif; ?>
          <?php else: ?>
            <p class="text-muted small mb-0">No payment record found.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- SHIPPING INFO -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Shipping</h5>

          <?php
          $orderLocked = in_array($order['status'], ['cancelled', 'refunded', 'delivered']);
          $shippingLocked = $shipping && in_array($shipping['status'], ['delivered', 'returned', 'cancelled']);
          $shippingFormLocked = $orderLocked || $shippingLocked;
          ?>

          <?php if ($shipping): ?>

            <?php if ($isCancelled): ?>
              <div
                class="alert alert-<?= $order['status'] === 'refunded' ? 'secondary' : 'warning' ?> py-2 px-3 small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Order is <strong><?= ucfirst($order['status']) ?></strong> — shipment was not completed.
              </div>
            <?php endif; ?>

            <p class="mb-1"><strong>Status:</strong>
              <span class="badge <?= shippingBadge($shipping['status']) ?>">
                <?= ucfirst(str_replace('_', ' ', $shipping['status'])) ?>
              </span>
            </p>
            <p class="mb-1"><strong>Courier:</strong>
              <?= $shipping['courier'] ? htmlspecialchars($shipping['courier']) : '<span class="text-muted">Not set</span>' ?>
            </p>
            <p class="mb-2"><strong>Tracking #:</strong>
              <?= $shipping['trackingNumber'] ? htmlspecialchars($shipping['trackingNumber']) : '<span class="text-muted">Not set</span>' ?>
            </p>

            <hr>

            <p class="mb-1"><strong>Recipient:</strong> <?= htmlspecialchars($shipping['recipientName']) ?></p>
            <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($shipping['phoneNumber']) ?></p>
            <p class="mb-2"><strong>Address:</strong>
              <?= htmlspecialchars($shipping['street'] . ", " . $shipping['barangay'] . ", " . $shipping['city']) ?>
            </p>

            <?php if (!$shippingFormLocked): ?>
              <hr>

              <!-- Courier & Tracking form -->
              <form method="POST" class="mb-3">
                <p class="fw-semibold small mb-2">Courier & Tracking</p>
                <div class="mb-2">
                  <select name="courier" class="form-select form-select-sm">
                    <option value="">— Select Courier —</option>
                    <?php
                    $couriers = ['J&T Express', 'LBC', 'Ninja Van', 'GoGoExpress', 'Grab Express', 'Lalamove', 'In-House Delivery'];
                    foreach ($couriers as $c):
                      ?>
                      <option value="<?= $c ?>" <?= ($shipping['courier'] ?? '') === $c ? 'selected' : '' ?>>
                        <?= $c ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="input-group input-group-sm mb-2">
                  <input type="text" name="trackingNumber" id="trackingInput" class="form-control form-control-sm"
                    placeholder="Tracking number" value="<?= htmlspecialchars($shipping['trackingNumber'] ?? '') ?>">
                  <button type="button" class="btn btn-outline-secondary btn-sm" onclick="generateTracking()">
                    <i class="bi bi-arrow-clockwise"></i>
                  </button>
                </div>
                <button type="submit" name="updateShippingDetails" class="btn btn-outline-primary btn-sm w-100">
                  <i class="bi bi-save me-1"></i> Save Details
                </button>
              </form>

              <!-- Shipping status form -->
              <form method="POST" class="d-flex align-items-center gap-2 flex-wrap">
                <label class="fw-semibold mb-0 small">Shipping Status:</label>
                <select name="shippingStatus" class="form-select form-select-sm" style="width:auto;">
                  <?php
                  $sStatuses = ['preparing', 'shipped', 'out_for_delivery', 'delivered', 'returned'];
                  foreach ($sStatuses as $ss):
                    ?>
                    <option value="<?= $ss ?>" <?= $shipping['status'] === $ss ? 'selected' : '' ?>>
                      <?= ucfirst(str_replace('_', ' ', $ss)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" name="updateShippingStatus" class="btn btn-primary btn-sm">
                  <i class="bi bi-check-circle me-1"></i> Update
                </button>
              </form>

            <?php else: ?>
              <p class="text-muted small mb-0">
                <i class="bi bi-lock me-1"></i>
                <?php if ($isCancelled): ?>
                  Order is <strong><?= ucfirst($order['status']) ?></strong> — shipping controls are locked.
                <?php else: ?>
                  Shipment is <strong><?= ucfirst($shipping['status']) ?></strong>.
                <?php endif; ?>
              </p>
            <?php endif; ?>

          <?php else: ?>
            <p class="text-muted small mb-0">
              <i class="bi bi-info-circle me-1"></i>
              No shipping record yet. Confirm the order to auto-create one.
            </p>
          <?php endif; ?>

        </div>
      </div>

      <div class="d-grid">
        <a href="orders.php" class="btn btn-secondary">
          <i class="bi bi-arrow-left me-1"></i> Back to Orders
        </a>
      </div>

    </div>

  </div>
</section>

<?php include('./includes/footer.php'); ?>