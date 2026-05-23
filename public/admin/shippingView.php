<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Shipping record not found!'); window.location.href='shipping.php';</script>";
    exit;
}

$shippingId = intval($_GET['id']);

// SHIPPING INFO
$shippingQuery = "SELECT 
    s.*,
    o.orderNumber,
    o.status AS orderStatus,
    o.totalAmount,
    o.orderedAt,
    o.userId,
    CONCAT(u.firstName, ' ', u.lastName) AS customerName,
    u.emailAddress,
    u.phoneNumber AS customerPhone
FROM shipping s
JOIN orders o ON s.orderId = o.orderId
JOIN users u ON o.userId = u.userId
WHERE s.shippingId = $shippingId
LIMIT 1";

$shippingResult = mysqli_query($conn, $shippingQuery);

if (mysqli_num_rows($shippingResult) == 0) {
    echo "<script>alert('Shipping record not found!'); window.location.href='shipping.php';</script>";
    exit;
}

$shipping = mysqli_fetch_assoc($shippingResult);

// ORDER ITEMS
$itemsQuery = "SELECT * FROM orderitems WHERE orderId = {$shipping['orderId']}";
$itemsResult = mysqli_query($conn, $itemsQuery);

// PAYMENT INFO
$paymentQuery = "SELECT * FROM payments WHERE orderId = {$shipping['orderId']} LIMIT 1";
$paymentResult = mysqli_query($conn, $paymentQuery);
$payment = mysqli_fetch_assoc($paymentResult);

// STATUS BADGE HELPER
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
    <h1>Shipping Details</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item"><a href="shipping">Shipping</a></li>
            <li class="breadcrumb-item active">View Shipment</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <div class="row">

        <!-- LEFT -->
        <div class="col-lg-8">

            <!-- SHIPMENT INFO -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Shipment Information</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Order Number</p>
                            <p class="fw-semibold">
                                <a href="order-view.php?id=<?= $shipping['orderId'] ?>">
                                    <?= htmlspecialchars($shipping['orderNumber']) ?>
                                </a>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Shipping Status</p>
                            <span class="badge <?= shippingBadge($shipping['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $shipping['status'])) ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Courier</p>
                            <p class="fw-semibold"><?= htmlspecialchars($shipping['courier'] ?? 'N/A') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Tracking Number</p>
                            <p class="fw-semibold"><?= htmlspecialchars($shipping['trackingNumber'] ?? 'N/A') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Shipped At</p>
                            <p class="fw-semibold">
                                <?= $shipping['shippedAt'] ? date("M d, Y h:i A", strtotime($shipping['shippedAt'])) : '<span class="text-muted">Not yet shipped</span>' ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Delivered At</p>
                            <p class="fw-semibold">
                                <?= $shipping['deliveredAt'] ? date("M d, Y h:i A", strtotime($shipping['deliveredAt'])) : '<span class="text-muted">Not yet delivered</span>' ?>
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- DELIVERY ADDRESS -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Delivery Address <span>| Snapshot at time of order</span></h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Recipient Name</p>
                            <p class="fw-semibold"><?= htmlspecialchars($shipping['recipientName']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Phone</p>
                            <p class="fw-semibold"><?= htmlspecialchars($shipping['phoneNumber']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Street</p>
                            <p class="fw-semibold"><?= htmlspecialchars($shipping['street']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Barangay</p>
                            <p class="fw-semibold"><?= htmlspecialchars($shipping['barangay']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">City</p>
                            <p class="fw-semibold"><?= htmlspecialchars($shipping['city']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Province</p>
                            <p class="fw-semibold"><?= htmlspecialchars($shipping['province'] ?? 'N/A') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Zip Code</p>
                            <p class="fw-semibold"><?= htmlspecialchars($shipping['zipCode'] ?? 'N/A') ?></p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ORDER ITEMS -->
            <div class="card recent-sales overflow-auto">
                <div class="card-body">
                    <h5 class="card-title">Order Items <span>| <?= htmlspecialchars($shipping['orderNumber']) ?></span></h5>

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
                            <?php if (mysqli_num_rows($itemsResult) > 0): ?>
                                <?php while ($item = mysqli_fetch_assoc($itemsResult)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['productName']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>₱<?= number_format($item['unitPrice'], 2) ?></td>
                                        <td>₱<?= number_format($item['quantity'] * $item['unitPrice'], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No items found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="border-top">
                                <td colspan="3" class="text-end fw-bold">Order Total</td>
                                <td class="fw-bold text-success">₱<?= number_format($shipping['totalAmount'], 2) ?></td>
                            </tr>
                        </tfoot>
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

                    <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($shipping['customerName']) ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($shipping['emailAddress']) ?></p>
                    <p class="mb-3"><strong>Phone:</strong> <?= htmlspecialchars($shipping['customerPhone']) ?></p>

                    <a href="customersView.php?id=<?= $shipping['userId'] ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-person me-1"></i> View Customer
                    </a>
                </div>
            </div>

            <!-- PAYMENT SNAPSHOT -->
            <?php if ($payment): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Payment</h5>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Method</span>
                            <span class="fw-semibold"><?= strtoupper($payment['method']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Status</span>
                            <span class="fw-semibold"><?= ucfirst($payment['status']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Amount</span>
                            <span class="fw-semibold text-success">₱<?= number_format($payment['amount'], 2) ?></span>
                        </li>
                    </ul>

                    <div class="mt-3">
                        <a href="payment-view.php?id=<?= $payment['paymentId'] ?>" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i> View Payment
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- QUICK LINKS -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quick Links</h5>
                    <div class="d-grid gap-2">
                        <a href="order-view.php?id=<?= $shipping['orderId'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-bag-check me-1"></i> View Order
                        </a>
                        <a href="shipping" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Back to Shipping
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<?php include('./includes/footer.php'); ?>