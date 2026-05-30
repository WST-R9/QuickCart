<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");
include_once("../../app/helpers/badges.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Payment not found!'); window.location.href='payments.php';</script>";
    exit;
}

$paymentId = intval($_GET['id']);

$paymentQuery = "SELECT 
    p.*,
    o.orderNumber,
    o.status AS orderStatus,
    o.totalAmount,
    o.orderedAt,
    o.userId,
    CONCAT(u.firstName, ' ', u.lastName) AS customerName,
    u.emailAddress,
    u.phoneNumber
FROM payments p
JOIN orders o ON p.orderId = o.orderId
JOIN users u ON o.userId = u.userId
WHERE p.paymentId = $paymentId
LIMIT 1";

$paymentResult = mysqli_query($conn, $paymentQuery);

if (mysqli_num_rows($paymentResult) == 0) {
    echo "<script>alert('Payment not found!'); window.location.href='payments.php';</script>";
    exit;
}

$payment = mysqli_fetch_assoc($paymentResult);

$itemsQuery  = "SELECT * FROM orderitems WHERE orderId = {$payment['orderId']}";
$itemsResult = mysqli_query($conn, $itemsQuery);

$shippingQuery  = "SELECT * FROM shipping WHERE orderId = {$payment['orderId']} LIMIT 1";
$shippingResult = mysqli_query($conn, $shippingQuery);
$shipping       = mysqli_fetch_assoc($shippingResult);

$isCancelled = in_array($payment['orderStatus'], ['cancelled', 'refunded']);
?>

<div class="pagetitle">
    <h1>Payment Details</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item"><a href="payments">Payments</a></li>
            <li class="breadcrumb-item active">View Payment</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">

    <?php if ($isCancelled): ?>
    <div class="alert alert-<?= $payment['orderStatus'] === 'refunded' ? 'secondary' : 'danger' ?> d-flex align-items-center mb-3" role="alert">
        <i class="bi bi-<?= $payment['orderStatus'] === 'refunded' ? 'arrow-counterclockwise' : 'x-circle' ?> me-2 fs-5"></i>
        <div>
            This order has been <strong><?= ucfirst($payment['orderStatus']) ?></strong>.
            <?= $payment['status'] === 'pending' ? 'Payment was never collected.' : '' ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">

        <!-- LEFT -->
        <div class="col-lg-8">

            <!-- PAYMENT INFO -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Payment Information</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Payment ID</p>
                            <p class="fw-semibold">#<?= $payment['paymentId'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Order Number</p>
                            <p class="fw-semibold">
                                <a href="ordersView.php?id=<?= $payment['orderId'] ?>">
                                    <?= htmlspecialchars($payment['orderNumber']) ?>
                                </a>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Payment Method</p>
                            <p class="fw-semibold"><?= strtoupper(str_replace('_', ' ', $payment['method'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Payment Status</p>
                            <span class="badge <?= paymentBadge($payment['status']) ?>">
                                <?= ucfirst($payment['status']) ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Amount</p>
                            <p class="fw-bold fs-5 text-success">₱<?= number_format($payment['amount'], 2) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Reference Number</p>
                            <p class="fw-semibold">
                                <?= $payment['referenceNumber'] ? htmlspecialchars($payment['referenceNumber']) : '<span class="text-muted">N/A</span>' ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Paid At</p>
                            <p class="fw-semibold">
                                <?= $payment['paidAt'] ? date("M d, Y h:i A", strtotime($payment['paidAt'])) : '<span class="text-muted">Not yet paid</span>' ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Created At</p>
                            <p class="fw-semibold"><?= date("M d, Y h:i A", strtotime($payment['createdAt'])) ?></p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ORDER ITEMS -->
            <div class="card recent-sales overflow-auto">
                <div class="card-body">
                    <h5 class="card-title">Order Items <span>| <?= htmlspecialchars($payment['orderNumber']) ?></span></h5>

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
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="fw-bold text-success">₱<?= number_format($payment['amount'], 2) ?></td>
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
                    <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($payment['customerName']) ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($payment['emailAddress']) ?></p>
                    <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($payment['phoneNumber']) ?></p>
                </div>
            </div>

            <!-- ORDER SUMMARY -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Order #</span>
                            <span class="fw-semibold"><?= htmlspecialchars($payment['orderNumber']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Order Status</span>
                            <span class="badge <?= orderStatusBadge($payment['orderStatus']) ?>">
                                <?= ucfirst($payment['orderStatus']) ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Order Total</span>
                            <span class="fw-semibold">₱<?= number_format($payment['totalAmount'], 2) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Ordered At</span>
                            <span class="fw-semibold"><?= date("M d, Y", strtotime($payment['orderedAt'])) ?></span>
                        </li>
                    </ul>
                    <div class="mt-3">
                        <a href="ordersView.php?id=<?= $payment['orderId'] ?>" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i> View Full Order
                        </a>
                    </div>
                </div>
            </div>

            <!-- SHIPPING SNAPSHOT -->
            <?php if ($shipping): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Shipping</h5>
                    <?php if ($isCancelled): ?>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Order was <?= ucfirst($payment['orderStatus']) ?> — shipment was not completed.
                        </p>
                    <?php endif; ?>
                    <p class="mb-1"><strong>Courier:</strong>
                        <?= $shipping['courier'] ? htmlspecialchars($shipping['courier']) : '<span class="text-muted">N/A</span>' ?>
                    </p>
                    <p class="mb-1"><strong>Tracking #:</strong>
                        <?= $shipping['trackingNumber'] ? htmlspecialchars($shipping['trackingNumber']) : '<span class="text-muted">N/A</span>' ?>
                    </p>
                    <p class="mb-1"><strong>Status:</strong>
                        <span class="badge <?= shippingBadge($shipping['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', $shipping['status'])) ?>
                        </span>
                    </p>
                    <p class="mb-0"><strong>Deliver to:</strong>
                        <?= htmlspecialchars($shipping['city'] . ', ' . $shipping['province']) ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <div class="d-grid">
                <a href="payments" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Payments
                </a>
            </div>

        </div>
    </div>
</section>

<?php include('./includes/footer.php'); ?>