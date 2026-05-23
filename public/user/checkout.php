<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;

// Fetch cart items
$stmt = $conn->prepare("
    SELECT c.cartId, c.quantity,
           p.productId, p.name, p.price, p.stock, p.imageUrl,
           cat.name AS categoryName
    FROM cart c
    JOIN products p ON c.productId = p.productId
    LEFT JOIN categories cat ON p.categoryId = cat.categoryId
    WHERE c.userId = ?
    ORDER BY c.addedAt DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Redirect back if cart is empty
if (empty($cartItems)) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Your cart is empty.'];
    header("Location: cart");
    exit;
}

// Fetch user's saved payment methods
$stmt = $conn->prepare("SELECT * FROM payment_methods WHERE userId=? ORDER BY isDefault DESC, createdAt DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$savedPaymentMethods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Inject virtual COD if user has no COD entry saved
$hasCod = !empty(array_filter($savedPaymentMethods, fn($p) => $p['method'] === 'cod'));
if (!$hasCod) {
    array_unshift($savedPaymentMethods, [
        'paymentMethodId' => 'cod_virtual',
        'method'          => 'cod',
        'label'           => '',
        'isDefault'       => 0,
        'accountNumber'   => null,
        'cardNumber'      => null,
        'bankName'        => null,
    ]);
}

// Default to COD if no user-set default exists
$hasDefault     = !empty(array_filter($savedPaymentMethods, fn($p) => $p['isDefault']));
$defaultPaymentId = $hasDefault
    ? array_values(array_filter($savedPaymentMethods, fn($p) => $p['isDefault']))[0]['paymentMethodId']
    : (array_values(array_filter($savedPaymentMethods, fn($p) => $p['method'] === 'cod'))[0]['paymentMethodId'] ??
        ($savedPaymentMethods[0]['paymentMethodId'] ?? 0));

// Fetch user's saved addresses
$stmt = $conn->prepare("SELECT * FROM addresses WHERE userId = ? ORDER BY isDefault DESC, createdAt DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalAmount = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$totalCount  = array_sum(array_column($cartItems, 'quantity'));

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['placeOrder'])) {
    $addressId       = (int) ($_POST['addressId'] ?? 0);
    $paymentMethodId = $_POST['paymentMethodId'] ?? 0;
    $notes           = mb_substr(trim($_POST['notes'] ?? ''), 0, 500);
    $referenceNumber = trim($_POST['referenceNumber'] ?? '');

    // Validate address belongs to user
    $addrStmt = $conn->prepare("SELECT * FROM addresses WHERE addressId = ? AND userId = ?");
    $addrStmt->bind_param("ii", $addressId, $userId);
    $addrStmt->execute();
    $address = $addrStmt->get_result()->fetch_assoc();
    $addrStmt->close();

    if (!$address) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please select a valid delivery address.'];
        header("Location: checkout");
        exit;
    }

    // Handle virtual COD or real saved payment method
    if ($paymentMethodId === 'cod_virtual') {
        $paymentMethod = 'cod';
        $selectedPm    = ['method' => 'cod'];
    } else {
        $paymentMethodId = (int) $paymentMethodId;
        $pmStmt = $conn->prepare("SELECT * FROM payment_methods WHERE paymentMethodId = ? AND userId = ?");
        $pmStmt->bind_param("ii", $paymentMethodId, $userId);
        $pmStmt->execute();
        $selectedPm = $pmStmt->get_result()->fetch_assoc();
        $pmStmt->close();

        if (!$selectedPm) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please select a valid payment method.'];
            header("Location: checkout");
            exit;
        }

        $paymentMethod = $selectedPm['method'];
    }

    // Require reference number for non-COD payments
    if ($paymentMethod !== 'cod' && empty($referenceNumber)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please provide your payment reference number.'];
        header("Location: checkout");
        exit;
    }

    $conn->begin_transaction();

    try {
        // Re-fetch cart inside transaction for accuracy
        $cartStmt = $conn->prepare("
            SELECT c.quantity, p.productId, p.name, p.price, p.stock
            FROM cart c
            JOIN products p ON c.productId = p.productId
            WHERE c.userId = ?
        ");
        $cartStmt->bind_param("i", $userId);
        $cartStmt->execute();
        $lockedCartItems = $cartStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $cartStmt->close();

        if (empty($lockedCartItems)) {
            throw new Exception("Your cart is empty.");
        }

        // Recalculate total server-side (never trust client-side total)
        $verifiedTotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $lockedCartItems));

        // Generate order number: QC-YYYYMMDD-XXXXX
        $orderNumber = 'QC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        // Insert order
        $oStmt = $conn->prepare("
            INSERT INTO orders (orderNumber, userId, totalAmount, status, notes)
            VALUES (?, ?, ?, 'pending', ?)
        ");
        $oStmt->bind_param("sids", $orderNumber, $userId, $verifiedTotal, $notes);
        $oStmt->execute();
        $orderId = $conn->insert_id;
        $oStmt->close();

        // Insert order items + decrement stock with sufficiency check
        foreach ($lockedCartItems as $item) {
            $iStmt = $conn->prepare("
                INSERT INTO orderitems (orderId, productId, productName, quantity, unitPrice)
                VALUES (?, ?, ?, ?, ?)
            ");
            $iStmt->bind_param("iisid", $orderId, $item['productId'], $item['name'], $item['quantity'], $item['price']);
            $iStmt->execute();
            $iStmt->close();

            // Decrement stock only if sufficient quantity exists
            $sStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE productId = ? AND stock >= ?");
            $sStmt->bind_param("iii", $item['quantity'], $item['productId'], $item['quantity']);
            $sStmt->execute();
            if ($sStmt->affected_rows === 0) {
                $sStmt->close();
                throw new Exception("'{$item['name']}' is out of stock or has insufficient quantity.");
            }
            $sStmt->close();
        }

        // Insert payment (with reference number for non-COD)
        if ($paymentMethod !== 'cod' && !empty($referenceNumber)) {
            $pStmt = $conn->prepare("
                INSERT INTO payments (orderId, method, status, amount, referenceNumber)
                VALUES (?, ?, 'pending', ?, ?)
            ");
            $pStmt->bind_param("isds", $orderId, $paymentMethod, $verifiedTotal, $referenceNumber);
        } else {
            $pStmt = $conn->prepare("
                INSERT INTO payments (orderId, method, status, amount)
                VALUES (?, ?, 'pending', ?)
            ");
            $pStmt->bind_param("isd", $orderId, $paymentMethod, $verifiedTotal);
        }
        $pStmt->execute();
        $pStmt->close();

        // Insert shipping (snapshot address)
        $shStmt = $conn->prepare("
            INSERT INTO shipping (orderId, addressId, status, recipientName, phoneNumber,
                                  street, barangay, city, province, zipCode)
            VALUES (?, ?, 'preparing', ?, ?, ?, ?, ?, ?, ?)
        ");
        $shStmt->bind_param(
            "iisssssss",
            $orderId,
            $addressId,
            $address['recipientName'],
            $address['phoneNumber'],
            $address['street'],
            $address['barangay'],
            $address['city'],
            $address['province'],
            $address['zipCode']
        );
        $shStmt->execute();
        $shStmt->close();

        // Clear cart
        $cStmt = $conn->prepare("DELETE FROM cart WHERE userId = ?");
        $cStmt->bind_param("i", $userId);
        $cStmt->execute();
        $cStmt->close();

        $conn->commit();

        $_SESSION['flash'] = ['type' => 'success', 'message' => "Order <strong>$orderNumber</strong> placed successfully!"];
        header("Location: orders");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Order failed: ' . $e->getMessage()];
        header("Location: checkout");
        exit;
    }
}

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$methodMeta = [
    'cod'           => ['icon' => 'bi-cash-coin',           'label' => 'Cash on Delivery', 'desc' => 'Pay when your order arrives'],
    'gcash'         => ['icon' => 'bi-phone',               'label' => 'GCash',            'desc' => 'Pay via GCash e-wallet'],
    'maya'          => ['icon' => 'bi-wallet2',             'label' => 'Maya',             'desc' => 'Pay via Maya e-wallet'],
    'credit_card'   => ['icon' => 'bi-credit-card-2-front', 'label' => 'Credit Card',      'desc' => 'Visa, Mastercard'],
    'bank_transfer' => ['icon' => 'bi-bank',                'label' => 'Bank Transfer',    'desc' => 'Online banking transfer'],
];

// Payment instructions per method shown inside the modal
$paymentInstructions = [
    'gcash' => [
        'steps' => [
            'Open your GCash app and tap <strong>Send Money</strong>.',
            'Enter the merchant GCash number: <strong>09XX-XXX-XXXX</strong>.',
            'Enter the exact amount: <strong>&#8369;' . number_format($totalAmount, 2) . '</strong>.',
            'Copy the <strong>Reference Number</strong> from your transaction receipt.',
            'Paste it in the field below and click <strong>Confirm Order</strong>.',
        ],
        'note' => 'Make sure the amount matches your order total exactly.',
    ],
    'maya' => [
        'steps' => [
            'Open your Maya app and tap <strong>Send Money</strong>.',
            'Enter the merchant Maya number: <strong>09XX-XXX-XXXX</strong>.',
            'Enter the exact amount: <strong>&#8369;' . number_format($totalAmount, 2) . '</strong>.',
            'Copy the <strong>Reference Number</strong> from your transaction receipt.',
            'Paste it in the field below and click <strong>Confirm Order</strong>.',
        ],
        'note' => 'Make sure the amount matches your order total exactly.',
    ],
    'credit_card' => [
        'steps' => [
            'Complete your card payment through your bank\'s portal.',
            'You will receive a transaction confirmation via SMS or email.',
            'Copy the <strong>Reference / Approval Number</strong> from the confirmation.',
            'Paste it in the field below and click <strong>Confirm Order</strong>.',
        ],
        'note' => 'Your card will be charged &#8369;' . number_format($totalAmount, 2) . '.',
    ],
    'bank_transfer' => [
        'steps' => [
            'Log in to your online banking app.',
            'Transfer <strong>&#8369;' . number_format($totalAmount, 2) . '</strong> to:<br><span class="text-muted">Bank: <strong>BDO</strong> &nbsp;|&nbsp; Account Name: <strong>QuickCart Inc.</strong> &nbsp;|&nbsp; Account No.: <strong>1234-5678-90</strong></span>',
            'Copy the <strong>Reference / Transaction Number</strong> from your receipt.',
            'Paste it in the field below and click <strong>Confirm Order</strong>.',
        ],
        'note' => 'Bank transfers may take a few minutes to reflect. Your order will be verified by our team.',
    ],
];
?>

<div class="pagetitle">
    <h1>Checkout</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item"><a href="cart">Cart</a></li>
            <li class="breadcrumb-item active">Checkout</li>
        </ol>
    </nav>
</div>

<section class="section">

    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $_SESSION['flash']['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-1"></i>
            <?= $_SESSION['flash']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <form method="POST" action="checkout" id="checkoutForm">
        <!-- Hidden field for reference number, filled by modal -->
        <input type="hidden" name="referenceNumber" id="referenceNumberInput">

        <div class="row g-4">

            <!-- Left column -->
            <div class="col-lg-8">

                <!-- Delivery Address -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-geo-alt me-2 text-success"></i>Delivery Address</h5>

                        <?php if (empty($addresses)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                You have no saved addresses.
                                <a href="addresses?from=checkout" class="alert-link">Add one here</a>.
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($addresses as $addr): ?>
                                    <div class="col-md-6">
                                        <label class="d-block cursor-pointer">
                                            <input type="radio" name="addressId" value="<?= $addr['addressId'] ?>"
                                                class="d-none address-radio"
                                                <?= $addr['isDefault'] ? 'checked' : '' ?>>
                                            <div class="address-card p-3 rounded-3 border h-100
                                                <?= $addr['isDefault'] ? 'border-success bg-light' : '' ?>"
                                                style="cursor:pointer; transition:all .2s;">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <span class="badge bg-success-subtle text-success fw-semibold">
                                                        <?= htmlspecialchars($addr['label'] ?? 'Address') ?>
                                                    </span>
                                                    <?php if ($addr['isDefault']): ?>
                                                        <span class="badge bg-success">Default</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="fw-bold"><?= htmlspecialchars($addr['recipientName']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($addr['phoneNumber']) ?></div>
                                                <div class="small mt-1">
                                                    <?= htmlspecialchars($addr['street']) ?>,
                                                    <?= htmlspecialchars($addr['barangay']) ?>,
                                                    <?= htmlspecialchars($addr['city']) ?>
                                                    <?php if ($addr['province']): ?>, <?= htmlspecialchars($addr['province']) ?><?php endif; ?>
                                                    <?php if ($addr['zipCode']): ?> <?= htmlspecialchars($addr['zipCode']) ?><?php endif; ?>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="addresses?from=checkout" class="btn btn-outline-secondary btn-sm mt-3">
                                <i class="bi bi-plus-lg me-1"></i> Add New Address
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-credit-card me-2 text-success"></i>Payment Method</h5>

                        <div class="row g-3">
                            <?php foreach ($savedPaymentMethods as $pm):
                                $meta = $methodMeta[$pm['method']] ?? ['icon' => 'bi-credit-card', 'label' => $pm['method'], 'desc' => ''];
                                $isSelected = (string) $pm['paymentMethodId'] === (string) $defaultPaymentId;
                            ?>
                                <div class="col-6 col-md-4">
                                    <label class="d-block cursor-pointer">
                                        <input type="radio" name="paymentMethodId"
                                            value="<?= $pm['paymentMethodId'] ?>"
                                            class="d-none payment-radio"
                                            data-method="<?= $pm['method'] ?>"
                                            <?= $isSelected ? 'checked' : '' ?>>
                                        <div class="payment-card p-3 rounded-3 border text-center <?= $isSelected ? 'border-success bg-light' : '' ?>"
                                            style="cursor:pointer; transition:all .2s;">
                                            <i class="bi <?= $meta['icon'] ?> fs-4 text-success mb-1 d-block"></i>
                                            <div class="fw-bold small"><?= $meta['label'] ?></div>
                                            <?php if ($pm['label']): ?>
                                                <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($pm['label']) ?></div>
                                            <?php else: ?>
                                                <div class="text-muted" style="font-size:11px;"><?= $meta['desc'] ?></div>
                                            <?php endif; ?>
                                            <?php if (in_array($pm['method'], ['gcash', 'maya']) && !empty($pm['accountNumber'])): ?>
                                                <div class="text-muted mt-1" style="font-size:10px;"><?= htmlspecialchars($pm['accountNumber']) ?></div>
                                            <?php elseif ($pm['method'] === 'credit_card' && !empty($pm['cardNumber'])): ?>
                                                <div class="text-muted mt-1" style="font-size:10px;">•••• <?= htmlspecialchars(substr($pm['cardNumber'], -4)) ?></div>
                                            <?php elseif ($pm['method'] === 'bank_transfer' && !empty($pm['bankName'])): ?>
                                                <div class="text-muted mt-1" style="font-size:10px;"><?= htmlspecialchars($pm['bankName']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <a href="paymentMethods?from=checkout" class="btn btn-outline-secondary btn-sm mt-3">
                            <i class="bi bi-plus-lg me-1"></i> Add New Payment Method
                        </a>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-chat-left-text me-2 text-success"></i>Order Notes
                            <span class="text-muted fw-normal" style="font-size:13px;">(optional)</span>
                        </h5>
                        <textarea name="notes" class="form-control" rows="3"
                            placeholder="Special instructions, preferred delivery time, gate codes…"
                            style="border-color:#d4e8da; resize:none;" maxlength="500"></textarea>
                    </div>
                </div>

            </div>

            <!-- Right column: Order Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top:80px;">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>

                        <!-- Items list -->
                        <div class="mb-3" style="max-height:260px; overflow-y:auto;">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <img src="../uploads/products/<?= htmlspecialchars($item['imageUrl'] ?? '') ?>"
                                        onerror="this.src='assets/img/product-placeholder.png'"
                                        style="width:40px; height:40px; object-fit:contain; border-radius:6px; background:#f4f9f5; padding:2px; flex-shrink:0;">
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="small fw-semibold text-truncate"><?= htmlspecialchars($item['name']) ?></div>
                                        <div class="text-muted" style="font-size:11px;">x<?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></div>
                                    </div>
                                    <div class="small fw-bold flex-shrink-0">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr style="border-color:#d4e8da;">

                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Subtotal (<?= $totalCount ?> item<?= $totalCount !== 1 ? 's' : '' ?>)</span>
                            <span class="fw-semibold small">₱<?= number_format($totalAmount, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted small">Shipping</span>
                            <span class="text-success fw-semibold small">Free</span>
                        </div>
                        <hr style="border-color:#d4e8da;">
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold">Total</span>
                            <span class="fw-bold text-success fs-5" style="font-family:'Nunito',sans-serif;">
                                ₱<?= number_format($totalAmount, 2) ?>
                            </span>
                        </div>

                        <?php if (!empty($addresses)): ?>
                            <button type="button" id="placeOrderBtn" class="btn btn-primary w-100 mb-2"
                                style="font-size:15px; padding:10px;">
                                <i class="bi bi-bag-check me-1"></i> Place Order
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary w-100 mb-2" disabled
                                title="Add a delivery address to continue">
                                <i class="bi bi-bag-check me-1"></i> Place Order
                            </button>
                        <?php endif; ?>
                        <a href="cart" class="btn btn-outline-secondary w-100 btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Back to Cart
                        </a>

                    </div>
                </div>
            </div>

        </div>
    </form>
</section>

<!-- Payment Reference Modal -->
<div class="modal fade" id="paymentReferenceModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="bi bi-send-check me-2 text-success"></i>
                    <span id="modalPaymentLabel">Submit Payment</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Amount badge -->
                <div class="text-center mb-3">
                    <div class="d-inline-block bg-success-subtle rounded-3 px-4 py-2">
                        <div class="text-muted small">Amount to Pay</div>
                        <div class="fw-bold text-success fs-4" style="font-family:'Nunito',sans-serif;">
                            &#8369;<?= number_format($totalAmount, 2) ?>
                        </div>
                    </div>
                </div>

                <!-- Payment steps -->
                <div class="mb-3">
                    <div class="fw-semibold small mb-2 text-muted text-uppercase" style="letter-spacing:.5px;">
                        How to Pay
                    </div>
                    <ol class="ps-3 mb-0" id="modalStepsList" style="font-size:14px; line-height:2;">
                        <!-- Populated by JS -->
                    </ol>
                </div>

                <!-- Note -->
                <div class="alert alert-warning py-2 px-3 small mb-3" id="modalNote" style="display:none;">
                    <i class="bi bi-info-circle me-1"></i>
                    <span id="modalNoteText"></span>
                </div>

                <!-- Reference number input -->
                <div>
                    <label class="form-label fw-semibold">
                        Reference Number <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="modalReferenceInput" class="form-control form-control-lg"
                        placeholder="Paste your reference number here"
                        style="border-color:#d4e8da; font-family:monospace; letter-spacing:.5px;">
                    <div class="text-muted mt-1" style="font-size:11px;">
                        Found in your payment app's transaction history or receipt.
                    </div>
                    <div class="text-danger small mt-1 d-none" id="refError">
                        <i class="bi bi-exclamation-circle me-1"></i>Please enter your reference number.
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" id="confirmOrderBtn">
                    <i class="bi bi-bag-check me-1"></i> Confirm Order
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Inject PHP data for JS — must be before main.js is loaded -->
<script>
    const paymentInstructions = <?= json_encode($paymentInstructions) ?>;
    const methodMetaMap       = <?= json_encode(array_map(fn($m) => ['label' => $m['label']], $methodMeta)) ?>;
</script>

<?php include('includes/footer.php'); ?>