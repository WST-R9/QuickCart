<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");
include_once("../../app/helpers/activityLog.php");


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
        $_SESSION['code']    = "error";
        header("Location: ordersView.php?id=$orderId");
        exit;
    }

    // Update order status
    mysqli_query($conn, "UPDATE orders SET status='$newStatus' WHERE orderId=$orderId");

    // If confirmed, create shipping record if it doesn't exist yet
    if ($newStatus === 'confirmed') {
        $checkShipping = mysqli_query($conn, "SELECT shippingId FROM shipping WHERE orderId=$orderId");

        if (mysqli_num_rows($checkShipping) == 0) {
            // Fetch default address of the customer
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
                $phoneNumber   = mysqli_real_escape_string($conn, $addr['phoneNumber']);
                $street        = mysqli_real_escape_string($conn, $addr['street']);
                $barangay      = mysqli_real_escape_string($conn, $addr['barangay']);
                $city          = mysqli_real_escape_string($conn, $addr['city']);
                $province      = mysqli_real_escape_string($conn, $addr['province'] ?? '');
                $zipCode       = mysqli_real_escape_string($conn, $addr['zipCode'] ?? '');
                $addressId     = $addr['addressId'];

                mysqli_query($conn, "
                    INSERT INTO shipping 
                        (orderId, addressId, status, recipientName, phoneNumber, street, barangay, city, province, zipCode)
                    VALUES 
                        ($orderId, $addressId, 'preparing', '$recipientName', '$phoneNumber', '$street', '$barangay', '$city', '$province', '$zipCode')
                ");
            }
        }
    }

    // If shipped, update shipping status and set shippedAt
    if ($newStatus === 'shipped') {
        mysqli_query($conn, "
            UPDATE shipping 
            SET status='shipped', shippedAt=NOW() 
            WHERE orderId=$orderId
        ");
    }

    // If delivered, update shipping status and set deliveredAt
    if ($newStatus === 'delivered') {
        mysqli_query($conn, "
            UPDATE shipping 
            SET status='delivered', deliveredAt=NOW() 
            WHERE orderId=$orderId
        ");

        // If payment method is COD and still pending, mark as paid
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

    // If cancelled, update shipping status too if exists
    if ($newStatus === 'cancelled') {
        mysqli_query($conn, "
            UPDATE shipping 
            SET status='returned' 
            WHERE orderId=$orderId
        ");
    }

    $_SESSION['message'] = "Order status updated to " . ucfirst($newStatus) . ".";
    $_SESSION['code']    = "success";
    header("Location: ordersView.php?id=$orderId");
    logActivity($conn, $_SESSION['user_id'], 'updated_order_status', 'orders', $orderId, $order['orderNumber'], "Status changed to $newStatus");
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
        $_SESSION['code']    = "error";
        header("Location: ordersView.php?id=$orderId");
        exit;
    }

    $shippedAt   = $newShippingStatus === 'shipped'    ? ", shippedAt=NOW()"    : "";
    $deliveredAt = $newShippingStatus === 'delivered'  ? ", deliveredAt=NOW()"  : "";

    mysqli_query($conn, "
        UPDATE shipping 
        SET status='$newShippingStatus' $shippedAt $deliveredAt
        WHERE orderId=$orderId
    ");

    // Sync order status with shipping
    if ($newShippingStatus === 'shipped') {
        mysqli_query($conn, "UPDATE orders SET status='shipped' WHERE orderId=$orderId");
    }

    if ($newShippingStatus === 'out_for_delivery') {
        mysqli_query($conn, "UPDATE orders SET status='shipped' WHERE orderId=$orderId");
    }

    if ($newShippingStatus === 'delivered') {
        mysqli_query($conn, "UPDATE orders SET status='delivered' WHERE orderId=$orderId");

        // Auto-mark COD as paid
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

    $_SESSION['message'] = "Shipping status updated to " . ucfirst(str_replace('_', ' ', $newShippingStatus)) . ".";
    $_SESSION['code']    = "success";
    header("Location: ordersView.php?id=$orderId");
    logActivity($conn, $_SESSION['user_id'], 'updated_shipping_status', 'orders', $orderId, $order['orderNumber'], "Shipping changed to $newShippingStatus");
    exit;
}

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
$paymentQuery  = "SELECT * FROM payments WHERE orderId = $orderId LIMIT 1";
$paymentResult = mysqli_query($conn, $paymentQuery);
$payment       = mysqli_fetch_assoc($paymentResult);

// SHIPPING INFO
$shippingQuery  = "SELECT * FROM shipping WHERE orderId = $orderId LIMIT 1";
$shippingResult = mysqli_query($conn, $shippingQuery);
$shipping       = mysqli_fetch_assoc($shippingResult);

// ORDER ITEMS
$itemsQuery  = "SELECT * FROM orderitems WHERE orderId = $orderId";
$itemsResult = mysqli_query($conn, $itemsQuery);

// STATUS BADGE HELPER
function orderBadge($status) {
    return match($status) {
        'pending'    => 'bg-warning text-dark',
        'confirmed'  => 'bg-primary',
        'processing' => 'bg-info text-dark',
        'shipped'    => 'bg-dark',
        'delivered'  => 'bg-success',
        'cancelled'  => 'bg-danger',
        'refunded'   => 'bg-secondary',
        default      => 'bg-secondary'
    };
}

function shippingBadge($status) {
    return match($status) {
        'preparing'        => 'bg-warning text-dark',
        'shipped'          => 'bg-primary',
        'out_for_delivery' => 'bg-info text-dark',
        'delivered'        => 'bg-success',
        'returned'         => 'bg-danger',
        default            => 'bg-secondary'
    };
}
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

          <?php if ($payment): ?>
            <p class="mb-1"><strong>Method:</strong> <?= strtoupper($payment['method']) ?></p>
            <p class="mb-1"><strong>Status:</strong>
              <?php
              $pb = match($payment['status']) {
                'paid'     => 'bg-success',
                'pending'  => 'bg-warning text-dark',
                'failed'   => 'bg-danger',
                'refunded' => 'bg-secondary',
                default    => 'bg-secondary'
              };
              ?>
              <span class="badge <?= $pb ?>"><?= ucfirst($payment['status']) ?></span>
            </p>
            <p class="mb-1"><strong>Amount:</strong> ₱<?= number_format($payment['amount'], 2) ?></p>
            <?php if (!empty($payment['referenceNumber'])): ?>
              <p class="mb-1"><strong>Reference:</strong> <?= htmlspecialchars($payment['referenceNumber']) ?></p>
            <?php endif; ?>
            <?php if ($payment['paidAt']): ?>
              <p class="mb-0"><strong>Paid At:</strong> <?= date("M d, Y h:i A", strtotime($payment['paidAt'])) ?></p>
            <?php endif; ?>
          <?php else: ?>
            <p class="text-muted">No payment record found.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- SHIPPING INFO -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Shipping</h5>

          <?php if ($shipping): ?>
            <p class="mb-1"><strong>Status:</strong>
              <span class="badge <?= shippingBadge($shipping['status']) ?>">
                <?= ucfirst(str_replace('_', ' ', $shipping['status'])) ?>
              </span>
            </p>
            <p class="mb-1"><strong>Courier:</strong> <?= htmlspecialchars($shipping['courier'] ?? 'N/A') ?></p>
            <p class="mb-1"><strong>Tracking #:</strong> <?= htmlspecialchars($shipping['trackingNumber'] ?? 'N/A') ?></p>
            <hr>
            <p class="mb-1"><strong>Recipient:</strong> <?= htmlspecialchars($shipping['recipientName']) ?></p>
            <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($shipping['phoneNumber']) ?></p>
            <p class="mb-2"><strong>Address:</strong>
              <?= htmlspecialchars($shipping['street'] . ", " . $shipping['barangay'] . ", " . $shipping['city']) ?>
            </p>

            <!-- SHIPPING STATUS UPDATE -->
            <?php if (!in_array($shipping['status'], ['delivered', 'returned'])): ?>
            <hr>
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
              Shipment is <strong><?= ucfirst($shipping['status']) ?></strong>.
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