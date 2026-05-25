<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/notifications.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;
$orderId = (int) ($_GET['id'] ?? 0);

// Fetch order — must be delivered and belong to user
$stmt = $conn->prepare("
    SELECT o.orderId, o.orderNumber, o.totalAmount, o.status, o.orderedAt,
           p.method AS paymentMethod,
           s.receivedAt
    FROM orders o
    LEFT JOIN payments p ON o.orderId = p.orderId
    LEFT JOIN shipping s ON o.orderId = s.orderId
    WHERE o.orderId = ? AND o.userId = ? AND o.status = 'delivered'
    LIMIT 1
");

$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Order not found or not eligible for refund.'];
    header("Location: orders");
    exit;
}

// Check if already submitted
$existing = $conn->prepare("SELECT refundId, status FROM refund_requests WHERE orderId = ? LIMIT 1");
$existing->bind_param("i", $orderId);
$existing->execute();
$existingReq = $existing->get_result()->fetch_assoc();
$existing->close();

$onlinePayments = ['gcash', 'maya', 'credit_card', 'bank_transfer'];
$isOnline = in_array($order['paymentMethod'], $onlinePayments);

// Fetch the saved payment method details for this order (for non-COD pre-fill)
$savedPmDetails = null;
if ($isOnline) {
    $pmStmt = $conn->prepare("
        SELECT pm.*
        FROM payment_methods pm
        JOIN payments py ON py.method = pm.method
        WHERE py.orderId = ? AND pm.userId = ?
        ORDER BY pm.isDefault DESC, pm.createdAt DESC
        LIMIT 1
    ");
    $pmStmt->bind_param("ii", $orderId, $userId);
    $pmStmt->execute();
    $savedPmDetails = $pmStmt->get_result()->fetch_assoc();
    $pmStmt->close();
}

// Handle submission — BEFORE HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitRefund'])) {

    // Re-check for duplicate
    $dupCheck = $conn->prepare("SELECT refundId FROM refund_requests WHERE orderId = ? LIMIT 1");
    $dupCheck->bind_param("i", $orderId);
    $dupCheck->execute();
    $dupResult = $dupCheck->get_result()->fetch_assoc();
    $dupCheck->close();

    if ($dupResult) {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'A refund request for this order already exists.'];
        header("Location: orderView?id=$orderId");
        exit;
    }

    $reason = trim($_POST['reason'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $refundMethod = trim($_POST['refundMethod'] ?? '');

    $validReasons = ['wrong_item', 'damaged_item', 'not_received', 'changed_mind', 'missing_item', 'other'];
    // Extended valid methods
    $validMethods = ['original', 'store_credit', 'store_pickup', 'gcash', 'maya', 'bank_transfer'];

    if (!$reason || !in_array($reason, $validReasons)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please select a valid reason.'];
        header("Location: refundOrder?id=$orderId");
        exit;
    }

    if (!in_array($refundMethod, $validMethods)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please select a valid refund method.'];
        header("Location: refundOrder?id=$orderId");
        exit;
    }

    // Collect account details for account-based refund methods
    $refundAccountType = null;
    $refundAccountName = null;
    $refundAccountNumber = null;
    $refundBankName = null;

    if (in_array($refundMethod, ['gcash', 'maya'])) {
        $refundAccountType = $refundMethod;
        $refundAccountName = trim($_POST['refundAccountName'] ?? '');
        $refundAccountNumber = trim($_POST['refundAccountNumber'] ?? '');

        if (empty($refundAccountName) || empty($refundAccountNumber)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please fill in your account name and number.'];
            header("Location: refundOrder?id=$orderId");
            exit;
        }
    } elseif ($refundMethod === 'bank_transfer') {
        $refundAccountType = 'bank_transfer';
        $refundAccountName = trim($_POST['refundAccountName'] ?? '');
        $refundAccountNumber = trim($_POST['refundAccountNumber'] ?? '');
        $refundBankName = trim($_POST['refundBankName'] ?? '');

        if (empty($refundAccountName) || empty($refundAccountNumber) || empty($refundBankName)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please fill in all bank transfer details.'];
            header("Location: refundOrder?id=$orderId");
            exit;
        }
    } elseif ($refundMethod === 'original' && $isOnline) {
        // Pre-fill from saved payment method if available
        $refundAccountType = $order['paymentMethod'];
        $refundAccountName = $savedPmDetails['accountName'] ?? $savedPmDetails['cardholderName'] ?? $savedPmDetails['bankAccountName'] ?? null;
        $refundAccountNumber = $savedPmDetails['accountNumber'] ?? $savedPmDetails['cardNumber'] ?? $savedPmDetails['bankAccountNumber'] ?? null;
        $refundBankName = $savedPmDetails['bankName'] ?? null;
    }

    // Handle image proof upload
    $imageProof = null;
    if (!empty($_FILES['imageProof']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['imageProof']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $_FILES['imageProof']['size'] <= 5 * 1024 * 1024) {
            $dir = '../uploads/refunds/';
            if (!is_dir($dir))
                mkdir($dir, 0755, true);
            $imageProof = 'refund_' . $userId . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['imageProof']['tmp_name'], $dir . $imageProof);
        }
    }

    // 7-day guard — server side
    $anchor = !empty($order['receivedAt']) ? $order['receivedAt'] : $order['orderedAt'];
    $daysSince = (int) (new DateTime())->diff(new DateTime($anchor))->days;
    if ($daysSince > 7) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'The 7-day window for this action has expired.'];
        header("Location: orderView?id=$orderId");
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO refund_requests
            (orderId, userId, reason, details, imageProof, refundMethod,
             refundAccountType, refundAccountName, refundAccountNumber, refundBankName, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param(
        "iissssssss",
        $orderId,
        $userId,
        $reason,
        $details,
        $imageProof,
        $refundMethod,
        $refundAccountType,
        $refundAccountName,
        $refundAccountNumber,
        $refundBankName
    );
    $stmt->execute();
    $stmt->close();

    // Notify admin
    createNotification(
        $conn,
        null,
        'admin',
        'refund_submitted',
        'New Refund Request',
        "A refund request was submitted for order {$order['orderNumber']}.",
        $orderId,
        'order'
    );

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Your refund/return request has been submitted. We will review it shortly.'];
    header("Location: orderView?id=$orderId");
    exit;
}

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
    <h1><?= $isOnline ? 'Request Refund' : 'Request Return' ?></h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item"><a href="orders">My Orders</a></li>
            <li class="breadcrumb-item"><a href="orderView?id=<?= $orderId ?>">Order Details</a></li>
            <li class="breadcrumb-item active"><?= $isOnline ? 'Request Refund' : 'Request Return' ?></li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <?php if (!empty($_SESSION['flash'])): ?>
                <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show" role="alert">
                    <i
                        class="bi bi-<?= $_SESSION['flash']['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-1"></i>
                    <?= $_SESSION['flash']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <?php if ($existingReq): ?>
                <!-- Already submitted -->
                <div class="card text-center">
                    <div class="card-body py-5">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:48px;"></i>
                        <h5 class="fw-bold mt-3 mb-2">Request Already Submitted</h5>
                        <p class="text-muted mb-1">
                            You have already submitted a <?= $isOnline ? 'refund' : 'return' ?> request for this order.
                        </p>
                        <?php $rb = match ($existingReq['status']) {
                            'approved' => 'bg-success',
                            'rejected' => 'bg-danger',
                            default => 'bg-warning text-dark'
                        }; ?>
                        <p class="mb-4">Status: <span class="badge <?= $rb ?>"><?= ucfirst($existingReq['status']) ?></span>
                        </p>
                        <a href="orderView?id=<?= $orderId ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Order
                        </a>
                    </div>
                </div>

            <?php else: ?>

                <!-- Order Summary Banner -->
                <div class="card mb-3" style="background:#f0faf3; border:1px solid #d4e8da;">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($order['orderNumber']) ?></div>
                            <div class="text-muted small">
                                Total: ₱<?= number_format($order['totalAmount'], 2) ?>
                                &nbsp;·&nbsp;
                                Paid via
                                <strong><?= ucwords(str_replace('_', ' ', $order['paymentMethod'] ?? 'COD')) ?></strong>
                            </div>
                        </div>
                        <span class="badge bg-success">Delivered</span>
                    </div>
                </div>

                <!-- Main Form Card -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi <?= $isOnline ? 'bi-cash-stack' : 'bi-box-arrow-left' ?> me-2 text-warning"></i>
                            <?= $isOnline ? 'Refund Request Form' : 'Return Request Form' ?>
                        </h5>
                        <p class="text-muted small mb-4">
                            <?php if ($isOnline): ?>
                                Fill out this form to request a refund. Our team will review your request within 1–3 business
                                days.
                            <?php else: ?>
                                Fill out this form to request a product return. Please prepare the item for pickup or drop-off.
                            <?php endif; ?>
                        </p>

                        <form method="POST" enctype="multipart/form-data">

                            <!-- Reason -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                                <select name="reason" class="form-select" style="border-color:#d4e8da;" required>
                                    <option value="" disabled selected>Select a reason</option>
                                    <option value="wrong_item">Wrong item received</option>
                                    <option value="damaged_item">Item was damaged or defective</option>
                                    <option value="not_received">Item not received</option>
                                    <option value="missing_item">Missing item</option>
                                    <option value="changed_mind">Changed my mind</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Details -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Details</label>
                                <textarea name="details" class="form-control" rows="4"
                                    placeholder="Describe your issue in detail…"
                                    style="border-color:#d4e8da; resize:vertical;"></textarea>
                            </div>

                            <!-- Image Proof -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Proof Image <span class="text-muted small">(optional, max 5 MB)</span>
                                </label>
                                <input type="file" name="imageProof" class="form-control"
                                    accept="image/jpeg,image/png,image/webp" style="border-color:#d4e8da;">
                                <div class="form-text">Attach a photo of the damaged/wrong item if applicable.</div>
                            </div>

                            <!-- ═══════════════════════════════════════════════════════
                                 REFUND METHOD — branches by payment type
                                 ═══════════════════════════════════════════════════════ -->

                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <?= $isOnline ? 'Refund Method' : 'Return &amp; Refund Method' ?>
                                    <span class="text-danger">*</span>
                                </label>

                                <?php if ($isOnline): ?>
                                    <!-- ── ONLINE PAYMENT: original account OR store pickup ── -->
                                    <div class="d-flex flex-column gap-2">

                                        <!-- Option 1: Return to original payment method -->
                                        <label class="refund-method-option d-flex align-items-start gap-3 p-3 rounded-3 border"
                                            style="cursor:pointer; border-color:#d4e8da !important; transition:all .2s;"
                                            data-target="original_details">
                                            <input class="form-check-input mt-1 flex-shrink-0 refund-method-radio" type="radio"
                                                name="refundMethod" value="original" checked>
                                            <div>
                                                <div class="fw-semibold small">
                                                    <i class="bi bi-arrow-counterclockwise me-1 text-success"></i>
                                                    Return to original payment method
                                                </div>
                                                <div class="text-muted" style="font-size:12px;">
                                                    Refund sent back to your
                                                    <strong><?= ucwords(str_replace('_', ' ', $order['paymentMethod'])) ?></strong>
                                                    account used for this order.
                                                </div>
                                                <?php if ($savedPmDetails): ?>
                                                    <?php
                                                    $displayAcct = $savedPmDetails['accountNumber']
                                                        ?? ($savedPmDetails['cardNumber']
                                                            ? '•••• ' . substr($savedPmDetails['cardNumber'], -4)
                                                            : ($savedPmDetails['bankAccountNumber'] ?? null));
                                                    $displayName = $savedPmDetails['accountName']
                                                        ?? $savedPmDetails['cardholderName']
                                                        ?? $savedPmDetails['bankAccountName']
                                                        ?? null;
                                                    ?>
                                                    <div class="mt-1 px-2 py-1 rounded-2 d-inline-block"
                                                        style="background:#e8f5e9; font-size:11px;">
                                                        <?php if ($displayName): ?>
                                                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($displayName) ?>
                                                            &nbsp;
                                                        <?php endif; ?>
                                                        <?php if ($displayAcct): ?>
                                                            <i class="bi bi-hash me-1"></i><?= htmlspecialchars($displayAcct) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </label>

                                        <!-- Option 2: Store pickup -->
                                        <label class="refund-method-option d-flex align-items-start gap-3 p-3 rounded-3 border"
                                            style="cursor:pointer; border-color:#d4e8da !important; transition:all .2s;"
                                            data-target="">
                                            <input class="form-check-input mt-1 flex-shrink-0 refund-method-radio" type="radio"
                                                name="refundMethod" value="store_pickup">
                                            <div>
                                                <div class="fw-semibold small">
                                                    <i class="bi bi-shop me-1 text-primary"></i>
                                                    Collect refund at our store
                                                </div>
                                                <div class="text-muted" style="font-size:12px;">
                                                    Bring the item(s) to our physical store. Refund will be given in cash upon
                                                    item inspection.
                                                </div>
                                            </div>
                                        </label>

                                    </div><!-- end online options -->

                                <?php else: ?>
                                    <!-- ── COD: store pickup OR provide account ── -->
                                    <div class="d-flex flex-column gap-2">

                                        <!-- Option 1: Store pickup (return item + collect cash) -->
                                        <label class="refund-method-option d-flex align-items-start gap-3 p-3 rounded-3 border"
                                            style="cursor:pointer; border-color:#d4e8da !important; transition:all .2s;"
                                            data-target="">
                                            <input class="form-check-input mt-1 flex-shrink-0 refund-method-radio" type="radio"
                                                name="refundMethod" value="store_pickup" checked>
                                            <div>
                                                <div class="fw-semibold small">
                                                    <i class="bi bi-shop me-1 text-primary"></i>
                                                    Return item &amp; collect cash at store
                                                </div>
                                                <div class="text-muted" style="font-size:12px;">
                                                    Bring the item(s) to our physical store. Cash refund will be handed over
                                                    after inspection.
                                                </div>
                                            </div>
                                        </label>

                                        <!-- Option 2: GCash -->
                                        <label class="refund-method-option d-flex align-items-start gap-3 p-3 rounded-3 border"
                                            style="cursor:pointer; border-color:#d4e8da !important; transition:all .2s;"
                                            data-target="cod_account_fields" data-acct-type="gcash">
                                            <input class="form-check-input mt-1 flex-shrink-0 refund-method-radio" type="radio"
                                                name="refundMethod" value="gcash">
                                            <div>
                                                <div class="fw-semibold small">
                                                    <i class="bi bi-phone me-1 text-primary"></i>GCash
                                                </div>
                                                <div class="text-muted" style="font-size:12px;">
                                                    We'll send your refund to your GCash account.
                                                </div>
                                            </div>
                                        </label>

                                        <!-- Option 3: Maya -->
                                        <label class="refund-method-option d-flex align-items-start gap-3 p-3 rounded-3 border"
                                            style="cursor:pointer; border-color:#d4e8da !important; transition:all .2s;"
                                            data-target="cod_account_fields" data-acct-type="maya">
                                            <input class="form-check-input mt-1 flex-shrink-0 refund-method-radio" type="radio"
                                                name="refundMethod" value="maya">
                                            <div>
                                                <div class="fw-semibold small">
                                                    <i class="bi bi-wallet2 me-1 text-info"></i>Maya
                                                </div>
                                                <div class="text-muted" style="font-size:12px;">
                                                    We'll send your refund to your Maya account.
                                                </div>
                                            </div>
                                        </label>

                                        <!-- Option 4: Bank Transfer -->
                                        <label class="refund-method-option d-flex align-items-start gap-3 p-3 rounded-3 border"
                                            style="cursor:pointer; border-color:#d4e8da !important; transition:all .2s;"
                                            data-target="cod_account_fields" data-acct-type="bank_transfer">
                                            <input class="form-check-input mt-1 flex-shrink-0 refund-method-radio" type="radio"
                                                name="refundMethod" value="bank_transfer">
                                            <div>
                                                <div class="fw-semibold small">
                                                    <i class="bi bi-bank me-1 text-secondary"></i>Bank Transfer
                                                </div>
                                                <div class="text-muted" style="font-size:12px;">
                                                    We'll transfer your refund to your bank account.
                                                </div>
                                            </div>
                                        </label>

                                    </div><!-- end COD options -->

                                    <!-- COD account fields (shown when GCash / Maya / Bank Transfer is picked) -->
                                    <div id="cod_account_fields" class="mt-3 p-3 rounded-3 d-none"
                                        style="background:#f8fffe; border:1px solid #d4e8da;">

                                        <!-- GCash / Maya fields -->
                                        <div id="cod_ewallet_fields">
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold small">
                                                        Account Name <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" name="refundAccountName" id="cod_accountName"
                                                        class="form-control" placeholder="Full name on account"
                                                        style="border-color:#d4e8da;">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold small">
                                                        Account Number <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" name="refundAccountNumber" id="cod_accountNumber"
                                                        class="form-control" placeholder="09XXXXXXXXX"
                                                        style="border-color:#d4e8da;">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bank transfer extra field -->
                                        <div id="cod_bank_extra" class="d-none">
                                            <div class="mt-2">
                                                <label class="form-label fw-semibold small">
                                                    Bank Name <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="refundBankName" id="cod_bankName" class="form-control"
                                                    placeholder="e.g. BDO, BPI, Metrobank" style="border-color:#d4e8da;">
                                            </div>
                                        </div>

                                    </div><!-- end cod_account_fields -->

                                <?php endif; /* isOnline / COD */ ?>
                            </div><!-- end refund method section -->

                            <!-- Submit -->
                            <div class="d-flex gap-2 mt-2">
                                <button type="submit" name="submitRefund" class="btn btn-warning fw-semibold px-4">
                                    <i class="bi bi-send me-1"></i>Submit Request
                                </button>
                                <a href="orderView?id=<?= $orderId ?>" class="btn btn-outline-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            <?php endif; /* existingReq */ ?>
        </div>
    </div>
</section>

<style>
    /* Highlight selected refund method card */
    .refund-method-option:has(input:checked) {
        border-color: #198754 !important;
        background: #f0faf3;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const radios = document.querySelectorAll('.refund-method-radio');
        const codAcct = document.getElementById('cod_account_fields');
        const codBank = document.getElementById('cod_bank_extra');

        function updateRefundUI() {
            radios.forEach(r => {
                const label = r.closest('.refund-method-option');
                if (!label) return;

                if (r.checked) {
                    label.style.borderColor = '#198754';
                    label.style.background = '#f0faf3';
                } else {
                    label.style.borderColor = '#d4e8da';
                    label.style.background = '';
                }
            });

            // Handle COD account sub-fields
            if (!codAcct) return;

            const chosen = document.querySelector('.refund-method-radio:checked');
            const acctType = chosen?.closest('.refund-method-option')?.dataset?.acctType ?? '';

            if (['gcash', 'maya', 'bank_transfer'].includes(chosen?.value)) {
                codAcct.classList.remove('d-none');

                if (chosen.value === 'bank_transfer') {
                    codBank.classList.remove('d-none');
                    // Adjust placeholder
                    document.getElementById('cod_accountNumber').placeholder = 'Account number';
                } else {
                    codBank.classList.add('d-none');
                    document.getElementById('cod_accountNumber').placeholder = '09XXXXXXXXX';
                }
            } else {
                codAcct.classList.add('d-none');
            }
        }

        radios.forEach(r => r.addEventListener('change', updateRefundUI));
        updateRefundUI(); // Run on page load to reflect default selection
    });
</script>

<?php include('includes/footer.php'); ?>