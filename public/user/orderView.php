<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/badges.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;
$orderId = intval($_GET['id'] ?? 0);

// ── Handle Cancel ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelOrder'])) {
    $stmt = $conn->prepare("
        UPDATE orders SET status = 'cancelled'
        WHERE orderId = ? AND userId = ? AND status = 'pending'
    ");
    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $rows = $conn->query("SELECT productId, quantity FROM orderitems WHERE orderId = $orderId");
        while ($row = $rows->fetch_assoc()) {
            $conn->query("UPDATE products SET stock = stock + {$row['quantity']} WHERE productId = {$row['productId']}");
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order cancelled successfully.'];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Unable to cancel this order.'];
    }
    $stmt->close();
    header("Location: orderView?id=$orderId");
    exit;
}

// ── Handle Order Received ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderReceived'])) {
    $stmt = $conn->prepare("
        UPDATE shipping
        SET    receivedAt = NOW()
        WHERE  orderId = ? AND receivedAt IS NULL
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order marked as received! You can now rate your products.'];
    header("Location: orderView?id=$orderId");
    exit;
}

// ── Handle Order Again ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderAgain'])) {
    $rows = $conn->query("SELECT productId, quantity FROM orderitems WHERE orderId = $orderId");
    $added = 0;
    while ($item = $rows->fetch_assoc()) {
        if (!$item['productId'])
            continue;
        $s = $conn->prepare("SELECT stock FROM products WHERE productId = ? AND status = 'active'");
        $s->bind_param('i', $item['productId']);
        $s->execute();
        $product = $s->get_result()->fetch_assoc();
        $s->close();
        if (!$product || $product['stock'] < 1)
            continue;
        $qty = min($item['quantity'], $product['stock']);
        $c = $conn->prepare("
            INSERT INTO cart (userId, productId, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $c->bind_param('iii', $userId, $item['productId'], $qty);
        $c->execute();
        $c->close();
        $added++;
    }
    if ($added > 0) {
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Items added to cart. Review and place your order.'];
        header("Location: checkout");
    } else {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'No items could be added — they may be out of stock or unavailable.'];
        header("Location: orderView?id=$orderId");
    }
    exit;
}

// ── Fetch Order Header ─────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT o.orderId, o.orderNumber, o.totalAmount, o.status, o.notes, o.orderedAt,
           p.method          AS paymentMethod,
           p.status          AS paymentStatus,
           p.referenceNumber,
           s.recipientName, s.phoneNumber,
           s.street, s.barangay, s.city, s.province, s.zipCode,
           s.courier, s.trackingNumber,
           s.status          AS shippingStatus,
           s.proofOfDelivery,
           s.receivedAt
    FROM   orders o
    LEFT JOIN payments p ON o.orderId = p.orderId
    LEFT JOIN shipping s ON o.orderId = s.orderId
    WHERE  o.orderId = ? AND o.userId = ?
    LIMIT  1
");
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Order not found.'];
    header("Location: orders");
    exit;
}

// ── Fetch Order Items ──────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT oi.orderItemId, oi.productName, oi.quantity, oi.unitPrice, oi.subtotal,
           pr.imageUrl
    FROM   orderitems oi
    LEFT JOIN products pr ON oi.productId = pr.productId
    WHERE  oi.orderId = ?
");
$stmt->bind_param('i', $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Derived state ──────────────────────────────────────────────────────────
$badge = orderStatusBadge($order['status']);
$progressSteps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
$currentStepIdx = array_search($order['status'], $progressSteps);
$showTracker = $currentStepIdx !== false;

$totalGaps = count($progressSteps) - 1; // 4
$fillPct = $totalGaps > 0 ? round(($currentStepIdx / $totalGaps) * 80) : 0;

$onlinePayments = ['gcash', 'maya', 'credit_card', 'bank_transfer'];
$isOnline = in_array($order['paymentMethod'], $onlinePayments);
$isReceived = !empty($order['receivedAt']);

// ── 7-day window (mirrors reviews.php) ────────────────────────────────────
$windowAnchor = !empty($order['receivedAt']) ? $order['receivedAt'] : $order['orderedAt'];
$daysSince = (int) (new DateTime())->diff(new DateTime($windowAnchor))->days;
$canActOnOrder = $daysSince <= 7;
$daysLeftOrder = max(0, 7 - $daysSince);

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
    <h1><?= htmlspecialchars($order['orderNumber']) ?></h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item"><a href="orders">My Orders</a></li>
            <li class="breadcrumb-item active">Order Details</li>
        </ol>
    </nav>
</div>

<section class="section">

    <!-- ── Status Tracker ── -->
    <?php if ($showTracker): ?>
        <div class="card mb-3">
            <div class="card-body py-4">
                <div class="order-tracker d-flex justify-content-between align-items-center position-relative px-2"
                    style="--fill-pct: <?= $fillPct ?>%">
                    <div class="tracker-line"></div>
                    <?php foreach ($progressSteps as $i => $step):
                        $done = $i < $currentStepIdx;
                        $active = $i === $currentStepIdx;
                        $icon = match ($step) {
                            'pending' => 'bi-clock',
                            'confirmed' => 'bi-check-circle',
                            'processing' => 'bi-gear',
                            'shipped' => 'bi-truck',
                            'delivered' => 'bi-bag-check',
                            default => 'bi-circle',
                        };
                        ?>
                        <div class="tracker-step text-center <?= $done ? 'done' : ($active ? 'active' : '') ?>">
                            <div class="tracker-icon mb-1">
                                <i class="bi <?= $icon ?>"></i>
                            </div>
                            <small><?= ucfirst($step) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert <?= $order['status'] === 'cancelled' ? 'alert-danger' : 'alert-secondary' ?> d-flex align-items-center mb-3"
            role="alert">
            <i
                class="bi <?= $order['status'] === 'cancelled' ? 'bi-x-circle' : 'bi-arrow-counterclockwise' ?> me-2 fs-5"></i>
            <div>This order has been <strong><?= ucfirst($order['status']) ?></strong>.</div>
        </div>
    <?php endif; ?>

    <div class="row g-3">

        <!-- ── Left Column: Items ── -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        Items
                        <span class="text-muted fw-normal fs-6">(<?= count($items) ?>)</span>
                    </h5>

                    <?php foreach ($items as $item): ?>
                        <div class="d-flex align-items-center gap-3 py-3 border-bottom">
                            <div class="flex-shrink-0">
                                <?php if (!empty($item['imageUrl'])): ?>
                                    <img src="../uploads/products/<?= htmlspecialchars($item['imageUrl']) ?>"
                                        alt="<?= htmlspecialchars($item['productName']) ?>"
                                        onerror="this.src='assets/img/product-placeholder.png'" class="rounded"
                                        style="width:72px;height:72px;object-fit:cover;">
                                <?php else: ?>
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                        style="width:72px;height:72px;">
                                        <i class="bi bi-image text-muted fs-4"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?= htmlspecialchars($item['productName']) ?></div>
                                <small class="text-muted">
                                    ₱<?= number_format($item['unitPrice'], 2) ?> &times; <?= $item['quantity'] ?>
                                </small>
                            </div>
                            <div class="fw-bold text-success text-end" style="min-width:90px;">
                                ₱<?= number_format($item['subtotal'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-end align-items-center pt-3 gap-3">
                        <span class="text-muted">Order Total</span>
                        <span class="fs-5 fw-bold text-success">₱<?= number_format($order['totalAmount'], 2) ?></span>
                    </div>

                    <!-- ── Proof of Delivery ── -->
                    <?php if ($order['status'] === 'delivered' || $order['status'] === 'refunded'): ?>
                        <div class="mt-4 pt-3 border-top proof-of-delivery">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-camera me-1"></i> Proof of Delivery
                            </h6>
                            <?php if (!empty($order['proofOfDelivery'])): ?>
                                <img src="../uploads/proof/<?= htmlspecialchars($order['proofOfDelivery']) ?>"
                                    alt="Proof of Delivery">
                                <p class="text-muted small mt-2 mb-0">
                                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                                    Delivery photo on record.
                                </p>
                            <?php elseif ($order['status'] === 'delivered' && !$isReceived): ?>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-hourglass-split me-1"></i>
                                    No proof uploaded yet. Confirm receipt using the button on the right.
                                </p>
                            <?php else: ?>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-dash-circle me-1"></i>
                                    No proof of delivery was attached.
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- ── Support Ticket (COD + Not Received) ── -->
                    <?php if ($order['status'] === 'delivered' && !$isOnline && !$isReceived): ?>
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-shrink-0 text-warning fs-4">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-semibold mb-1">Haven't received your order?</h6>
                                    <p class="text-muted small mb-3">
                                        This order was paid via <strong>Cash on Delivery</strong>, so a refund request isn't
                                        available.
                                        If your order was marked delivered but never arrived, please file a support ticket
                                        and
                                        our team will investigate.
                                    </p>
                                    <a href="supportTicket?orderId=<?= $orderId ?>&issue=not_received"
                                        class="btn btn-warning btn-sm">
                                        <i class="bi bi-headset me-1"></i> File a Support Ticket
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- ── Right Column: Info + Actions ── -->
        <div class="col-lg-4 d-flex flex-column gap-3">

            <!-- Order Info -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Info</h5>
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted ps-0">Order #</td>
                            <td class="fw-semibold text-end"><?= htmlspecialchars($order['orderNumber']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Status</td>
                            <td class="text-end">
                                <span class="badge <?= $badge ?>"><?= ucfirst($order['status']) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Date Placed</td>
                            <td class="text-end"><?= date('M d, Y', strtotime($order['orderedAt'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Payment</td>
                            <td class="text-end">
                                <?= htmlspecialchars(strtoupper(str_replace('_', ' ', $order['paymentMethod'] ?? '—'))) ?>
                            </td>
                        </tr>
                        <?php if (!empty($order['referenceNumber'])): ?>
                            <tr>
                                <td class="text-muted ps-0">Ref #</td>
                                <td class="text-end"><?= htmlspecialchars($order['referenceNumber']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($order['trackingNumber'])): ?>
                            <tr>
                                <td class="text-muted ps-0">Tracking #</td>
                                <td class="text-end"><?= htmlspecialchars($order['trackingNumber']) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($order['courier'])): ?>
                            <tr>
                                <td class="text-muted ps-0">Courier</td>
                                <td class="text-end"><?= htmlspecialchars($order['courier']) ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Shipping Address -->
            <?php if (!empty($order['recipientName'])): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-geo-alt me-1"></i> Shipping Address</h5>
                        <p class="mb-1 fw-semibold"><?= htmlspecialchars($order['recipientName']) ?></p>
                        <p class="mb-0 text-muted small" style="line-height:1.7;">
                            <?= htmlspecialchars($order['phoneNumber']) ?><br>
                            <?= htmlspecialchars($order['street']) ?>,
                            <?= htmlspecialchars($order['barangay']) ?>,
                            <?= htmlspecialchars($order['city']) ?>
                            <?php if (!empty($order['province'])): ?>,
                                <?= htmlspecialchars($order['province']) ?>
                            <?php endif; ?>
                            <?php if (!empty($order['zipCode'])): ?>
                                <?= htmlspecialchars($order['zipCode']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if (!empty($order['notes'])): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-chat-left-text me-1"></i> Notes</h5>
                        <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ── Actions ── -->

            <!-- Cancel (pending only) -->
            <?php if ($order['status'] === 'pending'): ?>
                <form method="POST"
                    onsubmit="return confirm('Are you sure you want to cancel this order? This cannot be undone.')">
                    <input type="hidden" name="cancelOrder" value="1">
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-x-circle me-1"></i> Cancel Order
                    </button>
                </form>
            <?php endif; ?>

            <!-- Delivered actions -->
            <?php if ($order['status'] === 'delivered'): ?>

                <!-- Refund / Return → 7-day guard -->
                <?php if ($canActOnOrder): ?>
                    <a href="refundOrder?id=<?= $orderId ?>" class="btn btn-warning w-100">
                        <?php if ($isOnline): ?>
                            <i class="bi bi-cash-stack me-1"></i> Request Refund
                        <?php else: ?>
                            <i class="bi bi-box-arrow-left me-1"></i> Return Order
                        <?php endif; ?>
                    </a>
                    <p class="text-muted small text-center mb-0">
                        <i class="bi bi-clock me-1"></i>
                        <?= $daysLeftOrder === 0 ? 'Last day' : $daysLeftOrder . ' day(s) left' ?>
                        to <?= $isOnline ? 'request a refund' : 'return this order' ?>.
                    </p>
                <?php else: ?>
                    <button class="btn btn-warning w-100" disabled data-bs-toggle="tooltip"
                        title="<?= $isOnline ? 'Refund' : 'Return' ?> window has closed (7-day limit)">
                        <i class="bi bi-lock me-1"></i>
                        <?= $isOnline ? 'Refund Closed' : 'Return Closed' ?>
                    </button>
                <?php endif; ?>

                <!-- Order Received / Rate -->
                <?php if (!$isReceived): ?>
                    <?php if (!$isOnline): ?>
                        <form method="POST" onsubmit="return confirm('Confirm that you have received this order?')">
                            <input type="hidden" name="orderReceived" value="1">
                            <button type="submit" class="btn btn-success w-100" id="btnOrderReceived"
                                <?= empty($order['proofOfDelivery']) ? 'disabled title="Waiting for delivery confirmation from courier"' : '' ?>>
                                <i class="bi bi-box-seam me-1"></i> Order Received
                            </button>
                        </form>
                        <?php if (empty($order['proofOfDelivery'])): ?>
                            <p class="text-muted small text-center mb-0">
                                <i class="bi bi-lock me-1"></i>
                                Button unlocks once the courier uploads proof of delivery.
                                If it never arrives, <a href="supportTicket?orderId=<?= $orderId ?>&issue=not_received">file a support
                                    ticket</a>.
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="POST" onsubmit="return confirm('Confirm that you have received this order?')">
                            <input type="hidden" name="orderReceived" value="1">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-box-seam me-1"></i> Order Received
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Rate Products → 7-day guard -->
                    <?php if ($canActOnOrder): ?>
                        <a href="rateOrder?id=<?= $orderId ?>" class="btn btn-success w-100">
                            <i class="bi bi-star me-1"></i> Rate Products
                        </a>
                        <p class="text-muted small text-center mb-0">
                            <i class="bi bi-clock me-1"></i>
                            <?= $daysLeftOrder === 0 ? 'Last day' : $daysLeftOrder . ' day(s) left' ?> to rate.
                        </p>
                    <?php else: ?>
                        <button class="btn btn-success w-100" disabled data-bs-toggle="tooltip"
                            title="Rating window has closed (7-day limit)">
                            <i class="bi bi-lock me-1"></i> Rating Closed
                        </button>
                    <?php endif; ?>
                <?php endif; ?>

            <?php endif; ?>

            <!-- Refunded → still allow rating, with 7-day guard -->
            <?php if ($order['status'] === 'refunded'): ?>
                <?php if ($canActOnOrder): ?>
                    <a href="rateOrder?id=<?= $orderId ?>" class="btn btn-success w-100">
                        <i class="bi bi-star me-1"></i> Rate Products
                    </a>
                    <p class="text-muted small text-center mb-0">
                        <i class="bi bi-clock me-1"></i>
                        <?= $daysLeftOrder === 0 ? 'Last day' : $daysLeftOrder . ' day(s) left' ?> to rate.
                    </p>
                <?php else: ?>
                    <button class="btn btn-success w-100" disabled data-bs-toggle="tooltip"
                        title="Rating window has closed (7-day limit)">
                        <i class="bi bi-lock me-1"></i> Rating Closed
                    </button>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Order Again -->
            <?php if (in_array($order['status'], ['cancelled', 'delivered', 'refunded'])): ?>
                <form method="POST">
                    <input type="hidden" name="orderAgain" value="1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-arrow-repeat me-1"></i> Order Again
                    </button>
                </form>
            <?php endif; ?>

            <a href="orders" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-left me-1"></i> Back to My Orders
            </a>

        </div>
    </div>

</section>

<?php include('includes/footer.php'); ?>