<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/badges.php");

$userId  = $_SESSION['authUser']['userId'] ?? 0;
$orderId = intval($_GET['id'] ?? 0);

// ── Fetch order (must belong to user, must be delivered) ──────────────────
$stmt = $conn->prepare("
    SELECT o.orderId, o.orderNumber, o.totalAmount, o.status, o.orderedAt,
           p.method AS paymentMethod, p.referenceNumber,
           s.courier, s.trackingNumber, s.deliveredAt
    FROM   orders   o
    LEFT JOIN payments p ON o.orderId = p.orderId
    LEFT JOIN shipping s ON o.orderId = s.orderId
    WHERE  o.orderId = ? AND o.userId = ?
    LIMIT 1
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

// Only delivered orders can be refunded/returned
if (!in_array($order['status'], ['delivered'])) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Only delivered orders can have a refund or return request.'];
    header("Location: orderView?id=$orderId");
    exit;
}

// Check if a refund request already exists
$chk = $conn->prepare("SELECT refundId, status FROM refund_requests WHERE orderId = ?");
$chk->bind_param('i', $orderId);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();
$chk->close();

// ── Fetch order items ─────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT oi.productName, oi.quantity, oi.unitPrice, oi.subtotal,
           pr.imageUrl
    FROM   orderitems oi
    LEFT JOIN products pr ON oi.productId = pr.productId
    WHERE  oi.orderId = ?
");
$stmt->bind_param('i', $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Determine if online payment ───────────────────────────────────────────
$onlinePayments = ['gcash', 'maya', 'credit_card', 'bank_transfer'];
$isOnline       = in_array($order['paymentMethod'], $onlinePayments);
$pageTitle      = $isOnline ? 'Request Refund' : 'Request Return';

// ── Handle form submission ────────────────────────────────────────────────
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitRefund'])) {
    $reason       = trim($_POST['reason']       ?? '');
    $details      = trim($_POST['details']      ?? '');
    $refundMethod = trim($_POST['refundMethod'] ?? 'original');

    if (!$reason)                                                $errors[] = 'Please select a reason.';
    if (!in_array($refundMethod, ['original', 'store_credit'])) $errors[] = 'Invalid refund method.';

    // Validate image if provided
    $imageProof = null;
    if (!empty($_FILES['imageProof']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['imageProof']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Only JPG, PNG, or WEBP images are allowed.';
        } elseif ($_FILES['imageProof']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Proof image must be under 5MB.';
        }
    }

    if (!$errors) {
        if ($existing) {
            $_SESSION['flash'] = ['type' => 'info', 'message' => 'A request has already been submitted for this order.'];
            header("Location: orderView?id=$orderId");
            exit;
        }

        // Upload image
        if (!empty($_FILES['imageProof']['name'])) {
            $uploadDir = '../uploads/proof/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext        = strtolower(pathinfo($_FILES['imageProof']['name'], PATHINFO_EXTENSION));
            $imageProof = 'proof_' . $orderId . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['imageProof']['tmp_name'], $uploadDir . $imageProof);
        }

        // Insert refund request
        $ins = $conn->prepare("
            INSERT INTO refund_requests (orderId, userId, reason, details, refundMethod, imageProof)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ins->bind_param('iissss', $orderId, $userId, $reason, $details, $refundMethod, $imageProof);
        $ins->execute();
        $ins->close();

        // Mark order as refunded
        $upd = $conn->prepare("UPDATE orders SET status = 'refunded' WHERE orderId = ? AND userId = ?");
        $upd->bind_param('ii', $orderId, $userId);
        $upd->execute();
        $upd->close();

        // Mark shipping as returned
        $conn->query("UPDATE shipping SET status = 'returned' WHERE orderId = $orderId");

        // Mark payment as refunded for online payments
        if ($isOnline) {
            $conn->query("UPDATE payments SET status = 'refunded' WHERE orderId = $orderId");
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => $isOnline
            ? 'Refund request submitted successfully. We will process it within 3–5 business days.'
            : 'Return request submitted successfully. Our team will contact you shortly.'];
        header("Location: orderView?id=$orderId");
        exit;
    }
}

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
    <h1><?= $pageTitle ?></h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item"><a href="orders">My Orders</a></li>
            <li class="breadcrumb-item"><a href="orderView?id=<?= $orderId ?>">Order Details</a></li>
            <li class="breadcrumb-item active"><?= $pageTitle ?></li>
        </ol>
    </nav>
</div>

<section class="section">

    <?php if ($errors): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
            <i class="bi bi-exclamation-circle-fill fs-5 mt-1"></i>
            <ul class="mb-0 ps-2">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($existing): ?>
        <!-- Already submitted state -->
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <span style="
                        display:inline-flex;align-items:center;justify-content:center;
                        width:72px;height:72px;border-radius:50%;
                        background:<?= $existing['status']==='approved' ? '#d1f0db' : ($existing['status']==='rejected' ? '#fde8ea' : '#fff8e1') ?>;
                        font-size:2rem;
                    ">
                        <i class="bi <?= $existing['status']==='approved' ? 'bi-check-circle-fill text-success' : ($existing['status']==='rejected' ? 'bi-x-circle-fill text-danger' : 'bi-hourglass-split text-warning') ?>"></i>
                    </span>
                </div>
                <h5 class="fw-bold mb-1">
                    <?php if ($existing['status'] === 'approved'): ?>Request Approved
                    <?php elseif ($existing['status'] === 'rejected'): ?>Request Rejected
                    <?php else: ?>Request Pending Review
                    <?php endif; ?>
                </h5>
                <p class="text-muted mb-4">
                    <?php if ($existing['status'] === 'approved'): ?>
                        Your <?= $isOnline ? 'refund' : 'return' ?> has been approved. Please allow 3–5 business days for processing.
                    <?php elseif ($existing['status'] === 'rejected'): ?>
                        Your request was reviewed and could not be approved. Please contact support for more details.
                    <?php else: ?>
                        Your request is under review. We'll update you once it's processed.
                    <?php endif; ?>
                </p>
                <a href="orderView?id=<?= $orderId ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Order
                </a>
            </div>
        </div>

    <?php else: ?>

    <div class="row g-3">

        <!-- ── Left: Form ── -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <?= $isOnline ? 'Refund Details' : 'Return Details' ?>
                    </h5>

                    <form method="POST" id="refundForm" enctype="multipart/form-data">

                        <!-- Reason -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Reason <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2">
                                <?php
                                $reasons = [
                                    ['val' => 'item_not_as_described', 'icon' => 'bi-emoji-frown',           'label' => 'Item not as described'],
                                    ['val' => 'wrong_item_received',   'icon' => 'bi-box-seam',              'label' => 'Wrong item received'],
                                    ['val' => 'damaged_item',          'icon' => 'bi-exclamation-triangle',  'label' => 'Item arrived damaged'],
                                    ['val' => 'missing_item',          'icon' => 'bi-question-circle',       'label' => 'Missing item(s)'],
                                    ['val' => 'quality_issue',         'icon' => 'bi-star-half',             'label' => 'Quality not satisfactory'],
                                    ['val' => 'changed_mind',          'icon' => 'bi-arrow-counterclockwise','label' => 'Changed my mind'],
                                ];
                                foreach ($reasons as $r):
                                    $checked = ($_POST['reason'] ?? '') === $r['val'];
                                ?>
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="reason"
                                           id="reason_<?= $r['val'] ?>"
                                           value="<?= $r['val'] ?>"
                                           <?= $checked ? 'checked' : '' ?> required>
                                    <label class="btn btn-outline-secondary w-100 text-start reason-btn"
                                           for="reason_<?= $r['val'] ?>">
                                        <i class="bi <?= $r['icon'] ?> me-2"></i><?= $r['label'] ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Additional details -->
                        <div class="mb-4">
                            <label for="details" class="form-label fw-semibold">
                                Additional Details <span class="text-muted fw-normal">(optional)</span>
                            </label>
                            <textarea name="details" id="details" rows="4"
                                      class="form-control"
                                      placeholder="Describe the issue in more detail…"><?= htmlspecialchars($_POST['details'] ?? '') ?></textarea>
                        </div>

                        <!-- Proof image upload -->
                        <div class="mb-4">
                            <label for="imageProof" class="form-label fw-semibold">
                                Attach Photo <span class="text-muted fw-normal">(optional, max 5MB)</span>
                            </label>
                            <input type="file" name="imageProof" id="imageProof"
                                   class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div id="proofPreviewWrap" class="mt-2 d-none">
                                <img id="proofPreview" src="#" alt="Preview"
                                     class="rounded border"
                                     style="max-height:160px;object-fit:cover;">
                            </div>
                            <div class="form-text">
                                A photo of the damaged or incorrect item helps speed up processing.
                            </div>
                        </div>

                        <?php if ($isOnline): ?>
                        <!-- Refund method -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Refund To</label>
                            <div class="d-flex gap-3 flex-wrap">
                                <div>
                                    <input type="radio" class="btn-check" name="refundMethod"
                                           id="rm_original" value="original"
                                           <?= ($_POST['refundMethod'] ?? 'original') === 'original' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary" for="rm_original">
                                        <i class="bi bi-credit-card me-2"></i>
                                        Original Payment Method
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" class="btn-check" name="refundMethod"
                                           id="rm_store" value="store_credit"
                                           <?= ($_POST['refundMethod'] ?? '') === 'store_credit' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary" for="rm_store">
                                        <i class="bi bi-wallet2 me-2"></i>
                                        Store Credit
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                            <input type="hidden" name="refundMethod" value="original">
                        <?php endif; ?>

                        <!-- Info notice -->
                        <div class="alert alert-info d-flex gap-2 align-items-start mb-4">
                            <i class="bi bi-info-circle-fill fs-5 mt-1 text-primary"></i>
                            <div>
                                <?php if ($isOnline): ?>
                                    Refunds are processed within <strong>3–5 business days</strong> after approval.
                                    The amount will be returned to your original payment method or as store credit.
                                <?php else: ?>
                                    Our team will contact you within <strong>1–2 business days</strong> to arrange
                                    collection of the item. Please keep the original packaging if possible.
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="submitRefund" class="btn btn-warning px-4">
                                <i class="bi <?= $isOnline ? 'bi-cash-stack' : 'bi-box-arrow-left' ?> me-1"></i>
                                Submit <?= $isOnline ? 'Refund' : 'Return' ?> Request
                            </button>
                            <a href="orderView?id=<?= $orderId ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- ── Right: Order summary ── -->
        <div class="col-lg-4 d-flex flex-column gap-3">

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted ps-0">Order #</td>
                            <td class="fw-semibold text-end"><?= htmlspecialchars($order['orderNumber']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Date Placed</td>
                            <td class="text-end"><?= date('M d, Y', strtotime($order['orderedAt'])) ?></td>
                        </tr>
                        <?php if ($order['deliveredAt']): ?>
                        <tr>
                            <td class="text-muted ps-0">Delivered</td>
                            <td class="text-end"><?= date('M d, Y', strtotime($order['deliveredAt'])) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-muted ps-0">Payment</td>
                            <td class="text-end"><?= strtoupper(str_replace('_', ' ', $order['paymentMethod'] ?? '—')) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Total</td>
                            <td class="fw-bold text-success text-end">₱<?= number_format($order['totalAmount'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Items (<?= count($items) ?>)</h5>
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                            <div class="flex-shrink-0">
                                <?php if (!empty($item['imageUrl'])): ?>
                                    <img src="../uploads/products/<?= htmlspecialchars($item['imageUrl']) ?>"
                                         onerror="this.src='assets/img/product-placeholder.png'"
                                         class="rounded"
                                         style="width:52px;height:52px;object-fit:cover;">
                                <?php else: ?>
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                         style="width:52px;height:52px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small"><?= htmlspecialchars($item['productName']) ?></div>
                                <small class="text-muted">
                                    ₱<?= number_format($item['unitPrice'], 2) ?> &times; <?= $item['quantity'] ?>
                                </small>
                            </div>
                            <div class="fw-semibold text-success small">
                                ₱<?= number_format($item['subtotal'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
    <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>