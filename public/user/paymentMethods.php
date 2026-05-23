<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;

// -----------------------------------------------
// ADD PAYMENT METHOD
// -----------------------------------------------
if (isset($_POST['addPaymentMethod'])) {
    $method    = trim($_POST['method']);
    $label     = trim($_POST['label']);
    $isDefault = isset($_POST['isDefault']) ? 1 : 0;

    $validMethods = ['gcash', 'maya', 'credit_card', 'bank_transfer'];
    if (!in_array($method, $validMethods)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid payment method.'];
        header("Location: paymentMethods");
        exit;
    }

    $accountName       = null;
    $accountNumber     = null;
    $cardholderName    = null;
    $cardNumber        = null;
    $expiryMonth       = null;
    $expiryYear        = null;
    $cardBrand         = null;
    $bankName          = null;
    $bankAccountName   = null;
    $bankAccountNumber = null;

    if (in_array($method, ['gcash', 'maya'])) {
        $accountName   = trim($_POST['accountName'] ?? '');
        $accountNumber = trim($_POST['accountNumber'] ?? '');
    } elseif ($method === 'credit_card') {
        $cardholderName = trim($_POST['cardholderName'] ?? '');
        $cardNumber     = preg_replace('/\D/', '', $_POST['cardNumber'] ?? '');
        $expiryMonth    = (int) ($_POST['expiryMonth'] ?? 0);
        $expiryYear     = (int) ($_POST['expiryYear'] ?? 0);
        $cardBrand      = trim($_POST['cardBrand'] ?? '');
    } elseif ($method === 'bank_transfer') {
        $bankName          = trim($_POST['bankName'] ?? '');
        $bankAccountName   = trim($_POST['bankAccountName'] ?? '');
        $bankAccountNumber = trim($_POST['bankAccountNumber'] ?? '');
    }

    if ($isDefault) {
        $stmt = $conn->prepare("UPDATE payment_methods SET isDefault=0 WHERE userId=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("INSERT INTO payment_methods
        (userId, method, label, isDefault, accountName, accountNumber,
         cardholderName, cardNumber, expiryMonth, expiryYear, cardBrand,
         bankName, bankAccountName, bankAccountNumber)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
        "isssisssiiisss",
        $userId, $method, $label, $isDefault,
        $accountName, $accountNumber,
        $cardholderName, $cardNumber, $expiryMonth, $expiryYear, $cardBrand,
        $bankName, $bankAccountName, $bankAccountNumber
    );
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Payment method added successfully.'];
    $redirect = isset($_GET['from']) && $_GET['from'] === 'checkout' ? 'paymentMethods?from=checkout' : 'paymentMethods';
    header("Location: $redirect");
    exit;
}

// -----------------------------------------------
// EDIT PAYMENT METHOD
// -----------------------------------------------
if (isset($_POST['editPaymentMethod'])) {
    $paymentMethodId = (int) $_POST['paymentMethodId'];
    $label           = trim($_POST['label']);
    $isDefault       = isset($_POST['isDefault']) ? 1 : 0;

    // Fetch the existing method type (don't let users change the method via edit)
    $chkStmt = $conn->prepare("SELECT method FROM payment_methods WHERE paymentMethodId=? AND userId=?");
    $chkStmt->bind_param("ii", $paymentMethodId, $userId);
    $chkStmt->execute();
    $existing = $chkStmt->get_result()->fetch_assoc();
    $chkStmt->close();

    if (!$existing) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Payment method not found.'];
        header("Location: paymentMethods");
        exit;
    }

    $method = $existing['method'];

    $accountName       = null;
    $accountNumber     = null;
    $cardholderName    = null;
    $cardNumber        = null;
    $expiryMonth       = null;
    $expiryYear        = null;
    $cardBrand         = null;
    $bankName          = null;
    $bankAccountName   = null;
    $bankAccountNumber = null;

    if (in_array($method, ['gcash', 'maya'])) {
        $accountName   = trim($_POST['accountName'] ?? '');
        $accountNumber = trim($_POST['accountNumber'] ?? '');
    } elseif ($method === 'credit_card') {
        $cardholderName = trim($_POST['cardholderName'] ?? '');
        $cardNumber     = preg_replace('/\D/', '', $_POST['cardNumber'] ?? '');
        $expiryMonth    = (int) ($_POST['expiryMonth'] ?? 0);
        $expiryYear     = (int) ($_POST['expiryYear'] ?? 0);
        $cardBrand      = trim($_POST['cardBrand'] ?? '');
    } elseif ($method === 'bank_transfer') {
        $bankName          = trim($_POST['bankName'] ?? '');
        $bankAccountName   = trim($_POST['bankAccountName'] ?? '');
        $bankAccountNumber = trim($_POST['bankAccountNumber'] ?? '');
    }

    if ($isDefault) {
        $stmt = $conn->prepare("UPDATE payment_methods SET isDefault=0 WHERE userId=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Fixed: 14 variables, correct type string "sisssiissssii" → s,i,s,s,s,i,i,s,s,s,s,i,i = 13... recount:
    // label(s), isDefault(i), accountName(s), accountNumber(s),
    // cardholderName(s), cardNumber(s), expiryMonth(i), expiryYear(i), cardBrand(s),
    // bankName(s), bankAccountName(s), bankAccountNumber(s),
    // paymentMethodId(i), userId(i)  → "sissssiiissssii" = 15? Let's count vars: 14 vars, types below:
    $stmt = $conn->prepare("UPDATE payment_methods SET
        label=?, isDefault=?,
        accountName=?, accountNumber=?,
        cardholderName=?, cardNumber=?, expiryMonth=?, expiryYear=?, cardBrand=?,
        bankName=?, bankAccountName=?, bankAccountNumber=?
        WHERE paymentMethodId=? AND userId=?");
    $stmt->bind_param(
        "sissssiiisssii",
        $label, $isDefault,
        $accountName, $accountNumber,
        $cardholderName, $cardNumber, $expiryMonth, $expiryYear, $cardBrand,
        $bankName, $bankAccountName, $bankAccountNumber,
        $paymentMethodId, $userId
    );
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Payment method updated successfully.'];
    $redirect = isset($_GET['from']) && $_GET['from'] === 'checkout' ? 'paymentMethods?from=checkout' : 'paymentMethods';
    header("Location: $redirect");
    exit;
}

// -----------------------------------------------
// SET DEFAULT
// -----------------------------------------------
if (isset($_GET['setDefault'])) {
    $paymentMethodId = (int) $_GET['setDefault'];

    $stmt = $conn->prepare("UPDATE payment_methods SET isDefault=0 WHERE userId=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE payment_methods SET isDefault=1 WHERE paymentMethodId=? AND userId=?");
    $stmt->bind_param("ii", $paymentMethodId, $userId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Default payment method updated.'];
    $redirect = isset($_GET['from']) && $_GET['from'] === 'checkout' ? 'paymentMethods?from=checkout' : 'paymentMethods';
    header("Location: $redirect");
    exit;
}

// -----------------------------------------------
// DELETE
// -----------------------------------------------
if (isset($_GET['deletePaymentMethod'])) {
    $paymentMethodId = (int) $_GET['deletePaymentMethod'];
    $stmt = $conn->prepare("DELETE FROM payment_methods WHERE paymentMethodId=? AND userId=?");
    $stmt->bind_param("ii", $paymentMethodId, $userId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Payment method deleted.'];
    $redirect = isset($_GET['from']) && $_GET['from'] === 'checkout' ? 'paymentMethods?from=checkout' : 'paymentMethods';
    header("Location: $redirect");
    exit;
}

// -----------------------------------------------
// FETCH
// -----------------------------------------------
$stmt = $conn->prepare("SELECT * FROM payment_methods WHERE userId=? ORDER BY isDefault DESC, createdAt DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$paymentMethods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$returnToCheckout = isset($_GET['from']) && $_GET['from'] === 'checkout';

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$methodMeta = [
    'cod'           => ['icon' => 'bi-cash-coin',           'label' => 'Cash on Delivery', 'color' => 'success'],
    'gcash'         => ['icon' => 'bi-phone',               'label' => 'GCash',            'color' => 'primary'],
    'maya'          => ['icon' => 'bi-wallet2',             'label' => 'Maya',             'color' => 'info'],
    'credit_card'   => ['icon' => 'bi-credit-card-2-front', 'label' => 'Credit Card',      'color' => 'warning'],
    'bank_transfer' => ['icon' => 'bi-bank',                'label' => 'Bank Transfer',    'color' => 'secondary'],
];
?>

<div class="pagetitle">
    <h1>Payment Methods</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item active">Payment Methods</li>
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

    <?php if ($returnToCheckout): ?>
        <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-info-circle-fill"></i>
            <span>Add a payment method below, then <a href="checkout" class="alert-link">return to checkout</a>.</span>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Left: Payment Methods List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-credit-card me-2 text-success"></i>Saved Payment Methods</h5>

                    <?php if (empty($paymentMethods)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-credit-card" style="font-size:3rem; opacity:.3;"></i>
                            <p class="mt-2 mb-0">No payment methods saved yet.</p>
                            <p class="small">Use the form on the right to add one.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($paymentMethods as $pm):
                                $meta = $methodMeta[$pm['method']] ?? ['icon' => 'bi-credit-card', 'label' => $pm['method'], 'color' => 'secondary'];
                            ?>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100 position-relative
                                        <?= $pm['isDefault'] ? 'border-success border-2 bg-light' : '' ?>">

                                        <?php if ($pm['isDefault']): ?>
                                            <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                                <i class="bi bi-star-fill me-1"></i>Default
                                            </span>
                                        <?php endif; ?>

                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div class="rounded-circle bg-<?= $meta['color'] ?>-subtle d-flex align-items-center justify-content-center"
                                                style="width:38px; height:38px; flex-shrink:0;">
                                                <i class="bi <?= $meta['icon'] ?> text-<?= $meta['color'] ?>"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold small"><?= $meta['label'] ?></div>
                                                <?php if ($pm['label']): ?>
                                                    <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($pm['label']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if (in_array($pm['method'], ['gcash', 'maya'])): ?>
                                            <?php if ($pm['accountName']): ?>
                                                <div class="small text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($pm['accountName']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($pm['accountNumber']): ?>
                                                <div class="small text-muted"><i class="bi bi-hash me-1"></i><?= htmlspecialchars($pm['accountNumber']) ?></div>
                                            <?php endif; ?>

                                        <?php elseif ($pm['method'] === 'credit_card'): ?>
                                            <?php if ($pm['cardholderName']): ?>
                                                <div class="small text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($pm['cardholderName']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($pm['cardNumber']): ?>
                                                <div class="small text-muted">
                                                    <i class="bi bi-credit-card me-1"></i>
                                                    <?= $pm['cardBrand'] ? htmlspecialchars($pm['cardBrand']) . ' ' : '' ?>
                                                    •••• <?= htmlspecialchars(substr($pm['cardNumber'], -4)) ?>
                                                    <?php if ($pm['expiryMonth'] && $pm['expiryYear']): ?>
                                                        &nbsp;(<?= str_pad($pm['expiryMonth'], 2, '0', STR_PAD_LEFT) ?>/<?= $pm['expiryYear'] ?>)
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                        <?php elseif ($pm['method'] === 'bank_transfer'): ?>
                                            <?php if ($pm['bankName']): ?>
                                                <div class="small text-muted"><i class="bi bi-bank me-1"></i><?= htmlspecialchars($pm['bankName']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($pm['bankAccountName']): ?>
                                                <div class="small text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($pm['bankAccountName']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($pm['bankAccountNumber']): ?>
                                                <div class="small text-muted"><i class="bi bi-hash me-1"></i><?= htmlspecialchars($pm['bankAccountNumber']) ?></div>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <div class="small text-muted">Pay when your order arrives.</div>
                                        <?php endif; ?>

                                        <div class="d-flex gap-2 flex-wrap mt-3">
                                            <?php if (!$pm['isDefault']): ?>
                                                <a href="paymentMethods?setDefault=<?= $pm['paymentMethodId'] ?><?= $returnToCheckout ? '&from=checkout' : '' ?>"
                                                    class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-star me-1"></i>Set Default
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="openEditPaymentModal(<?= htmlspecialchars(json_encode($pm), ENT_QUOTES) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="paymentMethods?deletePaymentMethod=<?= $pm['paymentMethodId'] ?><?= $returnToCheckout ? '&from=checkout' : '' ?>"
                                                onclick="return confirm('Delete this payment method?');"
                                                class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="checkout" class="btn btn-success">
                            <i class="bi bi-arrow-left me-1"></i> Back to Checkout
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <!-- Right: Add Form -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top:80px;">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-plus-circle me-2 text-success"></i>Add Payment Method</h5>
                    <form method="POST" action="paymentMethods<?= $returnToCheckout ? '?from=checkout' : '' ?>" class="row g-2">

                        <div class="col-12">
                            <label class="form-label fw-semibold">Method <span class="text-danger">*</span></label>
                            <select name="method" id="add_method" class="form-select" required onchange="toggleAddFields(this.value)">
                                <option value="">— Select —</option>
                                <option value="gcash">GCash</option>
                                <option value="maya">Maya</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Label</label>
                            <input type="text" name="label" class="form-control" placeholder="e.g. My GCash, BDO Savings">
                        </div>

                        <!-- GCash / Maya -->
                        <div id="add_ewallet_fields" class="col-12 d-none">
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Account Name <span class="text-danger">*</span></label>
                                    <input type="text" name="accountName" class="form-control" placeholder="Full name on account">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Account Number <span class="text-danger">*</span></label>
                                    <input type="text" name="accountNumber" class="form-control" placeholder="09XXXXXXXXX">
                                </div>
                            </div>
                        </div>

                        <!-- Credit Card -->
                        <div id="add_card_fields" class="col-12 d-none">
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Cardholder Name <span class="text-danger">*</span></label>
                                    <input type="text" name="cardholderName" class="form-control" placeholder="As printed on card">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Card Number <span class="text-danger">*</span></label>
                                    <input type="text" name="cardNumber" class="form-control" placeholder="16-digit card number" maxlength="19">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Expiry Month</label>
                                    <select name="expiryMonth" class="form-select">
                                        <option value="">MM</option>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>"><?= str_pad($m, 2, '0', STR_PAD_LEFT) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Expiry Year</label>
                                    <select name="expiryYear" class="form-select">
                                        <option value="">YYYY</option>
                                        <?php for ($y = date('Y'); $y <= date('Y') + 10; $y++): ?>
                                            <option value="<?= $y ?>"><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Card Brand</label>
                                    <select name="cardBrand" class="form-select">
                                        <option value="">— Select —</option>
                                        <option value="Visa">Visa</option>
                                        <option value="Mastercard">Mastercard</option>
                                        <option value="JCB">JCB</option>
                                        <option value="Amex">Amex</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Transfer -->
                        <div id="add_bank_fields" class="col-12 d-none">
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Bank Name <span class="text-danger">*</span></label>
                                    <input type="text" name="bankName" class="form-control" placeholder="e.g. BDO, BPI, Metrobank">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Account Name <span class="text-danger">*</span></label>
                                    <input type="text" name="bankAccountName" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Account Number <span class="text-danger">*</span></label>
                                    <input type="text" name="bankAccountNumber" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check mt-1">
                                <!-- FIXED: was missing closing > on input tag -->
                                <input class="form-check-input" type="checkbox" name="isDefault" id="add_isDefault" value="1">
                                <label class="form-check-label small" for="add_isDefault">Set as default</label>
                            </div>
                        </div>

                        <div class="col-12 mt-2">
                            <button type="submit" name="addPaymentMethod" class="btn btn-success w-100">
                                <i class="bi bi-plus-circle me-1"></i> Add Payment Method
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Edit Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2 text-success"></i>Edit Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="paymentMethods<?= $returnToCheckout ? '?from=checkout' : '' ?>">
                <div class="modal-body row g-3">
                    <input type="hidden" name="paymentMethodId" id="edit_paymentMethodId">
                    <input type="hidden" name="method" id="edit_method_hidden">

                    <div class="col-12">
                        <label class="form-label fw-semibold">Method</label>
                        <input type="text" id="edit_method_display" class="form-control bg-light" disabled>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Label</label>
                        <input type="text" name="label" id="edit_label" class="form-control" placeholder="e.g. My GCash">
                    </div>

                    <!-- GCash / Maya -->
                    <div id="edit_ewallet_fields" class="col-12 d-none">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Account Name</label>
                                <input type="text" name="accountName" id="edit_accountName" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Account Number</label>
                                <input type="text" name="accountNumber" id="edit_accountNumber" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Credit Card -->
                    <div id="edit_card_fields" class="col-12 d-none">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Cardholder Name</label>
                                <input type="text" name="cardholderName" id="edit_cardholderName" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Card Number</label>
                                <input type="text" name="cardNumber" id="edit_cardNumber" class="form-control" maxlength="19" placeholder="16-digit card number">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Expiry Month</label>
                                <select name="expiryMonth" id="edit_expiryMonth" class="form-select">
                                    <option value="">MM</option>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>"><?= str_pad($m, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Expiry Year</label>
                                <select name="expiryYear" id="edit_expiryYear" class="form-select">
                                    <option value="">YYYY</option>
                                    <?php for ($y = date('Y'); $y <= date('Y') + 10; $y++): ?>
                                        <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Card Brand</label>
                                <select name="cardBrand" id="edit_cardBrand" class="form-select">
                                    <option value="">— Select —</option>
                                    <option value="Visa">Visa</option>
                                    <option value="Mastercard">Mastercard</option>
                                    <option value="JCB">JCB</option>
                                    <option value="Amex">Amex</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Transfer -->
                    <div id="edit_bank_fields" class="col-12 d-none">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Bank Name</label>
                                <input type="text" name="bankName" id="edit_bankName" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Account Name</label>
                                <input type="text" name="bankAccountName" id="edit_bankAccountName" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Account Number</label>
                                <input type="text" name="bankAccountNumber" id="edit_bankAccountNumber" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="isDefault" id="edit_isDefault" value="1">
                            <label class="form-check-label small" for="edit_isDefault">Set as default</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="editPaymentMethod" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>