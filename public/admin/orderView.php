<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Order not found!'); window.location.href='orders.php';</script>";
    exit;
}

$orderId = intval($_GET['id']);

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

          <p><strong>Order Number:</strong> <?= htmlspecialchars($order['orderNumber']) ?></p>
          <p><strong>Status:</strong> <?= ucfirst($order['status']) ?></p>
          <p><strong>Total Amount:</strong> ₱<?= number_format($order['totalAmount'], 2) ?></p>
          <p><strong>Ordered At:</strong> <?= date("M d, Y h:i A", strtotime($order['orderedAt'])) ?></p>

          <?php if (!empty($order['notes'])): ?>
            <p><strong>Notes:</strong> <?= htmlspecialchars($order['notes']) ?></p>
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

          <p><strong>Name:</strong> <?= htmlspecialchars($order['customerName']) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($order['emailAddress']) ?></p>
          <p><strong>Phone:</strong> <?= htmlspecialchars($order['phoneNumber']) ?></p>

          <a href="customer-view.php?id=<?= $order['userId'] ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-person"></i> View Customer
          </a>
        </div>
      </div>

      <!-- PAYMENT INFO -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Payment</h5>

          <?php if ($payment): ?>
            <p><strong>Method:</strong> <?= strtoupper($payment['method']) ?></p>
            <p><strong>Status:</strong> <?= ucfirst($payment['status']) ?></p>
            <p><strong>Amount:</strong> ₱<?= number_format($payment['amount'], 2) ?></p>

            <?php if (!empty($payment['referenceNumber'])): ?>
              <p><strong>Reference:</strong> <?= htmlspecialchars($payment['referenceNumber']) ?></p>
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
            <p><strong>Status:</strong> <?= ucfirst($shipping['status']) ?></p>
            <p><strong>Courier:</strong> <?= htmlspecialchars($shipping['courier']) ?></p>
            <p><strong>Tracking #:</strong> <?= htmlspecialchars($shipping['trackingNumber']) ?></p>

            <hr>

            <p><strong>Recipient:</strong> <?= htmlspecialchars($shipping['recipientName']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($shipping['phoneNumber']) ?></p>
            <p><strong>Address:</strong> 
              <?= htmlspecialchars($shipping['street'] . ", " . $shipping['barangay'] . ", " . $shipping['city']) ?>
            </p>

          <?php else: ?>
            <p class="text-muted">No shipping record found.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="d-grid">
        <a href="orders.php" class="btn btn-secondary">
          <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
      </div>

    </div>

  </div>
</section>

<?php include('./includes/footer.php'); ?>