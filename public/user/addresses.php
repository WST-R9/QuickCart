<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;

// -----------------------------------------------
// ADD ADDRESS
// -----------------------------------------------
if (isset($_POST['addAddress'])) {
    $label         = trim($_POST['label']);
    $recipientName = trim($_POST['recipientName']);
    $phoneNumber   = trim($_POST['phoneNumber_addr']);
    $street        = trim($_POST['street']);
    $barangay      = trim($_POST['barangay']);
    $city          = trim($_POST['city']);
    $province      = trim($_POST['province']);
    $zipCode       = trim($_POST['zipCode']);
    $isDefault     = isset($_POST['isDefault']) ? 1 : 0;

    if ($isDefault) {
        $conn->query("UPDATE addresses SET isDefault=0 WHERE userId=$userId");
    }

    $stmt = $conn->prepare("INSERT INTO addresses 
        (userId, label, recipientName, phoneNumber, street, barangay, city, province, zipCode, isDefault)
        VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
        "issssssssi",
        $userId, $label, $recipientName, $phoneNumber,
        $street, $barangay, $city, $province, $zipCode, $isDefault
    );
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Address added successfully.'];
    header("Location: addresses");
    exit;
}

// -----------------------------------------------
// EDIT ADDRESS
// -----------------------------------------------
if (isset($_POST['editAddress'])) {
    $addressId     = (int) $_POST['addressId'];
    $label         = trim($_POST['label']);
    $recipientName = trim($_POST['recipientName']);
    $phoneNumber   = trim($_POST['phoneNumber_addr']);
    $street        = trim($_POST['street']);
    $barangay      = trim($_POST['barangay']);
    $city          = trim($_POST['city']);
    $province      = trim($_POST['province']);
    $zipCode       = trim($_POST['zipCode']);
    $isDefault     = isset($_POST['isDefault']) ? 1 : 0;

    if ($isDefault) {
        $conn->query("UPDATE addresses SET isDefault=0 WHERE userId=$userId");
    }

    $stmt = $conn->prepare("UPDATE addresses SET
        label=?, recipientName=?, phoneNumber=?, street=?,
        barangay=?, city=?, province=?, zipCode=?, isDefault=?
        WHERE addressId=? AND userId=?");
    $stmt->bind_param(
        "sssssssiii",
        $label, $recipientName, $phoneNumber, $street,
        $barangay, $city, $province, $zipCode, $isDefault,
        $addressId, $userId
    );
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Address updated successfully.'];
    header("Location: addresses");
    exit;
}

// -----------------------------------------------
// SET DEFAULT
// -----------------------------------------------
if (isset($_GET['setDefault'])) {
    $addressId = (int) $_GET['setDefault'];
    $conn->query("UPDATE addresses SET isDefault=0 WHERE userId=$userId");
    $conn->query("UPDATE addresses SET isDefault=1 WHERE addressId=$addressId AND userId=$userId");
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Default address updated.'];
    header("Location: addresses");
    exit;
}

// -----------------------------------------------
// DELETE ADDRESS
// -----------------------------------------------
if (isset($_GET['deleteAddress'])) {
    $addressId = (int) $_GET['deleteAddress'];
    $stmt = $conn->prepare("DELETE FROM addresses WHERE addressId=? AND userId=?");
    $stmt->bind_param("ii", $addressId, $userId);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Address deleted.'];
    header("Location: addresses");
    exit;
}

// -----------------------------------------------
// FETCH ADDRESSES + USER INFO
// -----------------------------------------------
$stmt = $conn->prepare("SELECT * FROM addresses WHERE userId=? ORDER BY isDefault DESC, createdAt DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT firstName, lastName, phoneNumber FROM users WHERE userId=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$returnToCheckout = isset($_GET['from']) && $_GET['from'] === 'checkout';

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
    <h1>My Addresses</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item active">Addresses</li>
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
            <span>Add a delivery address below, then <a href="checkout" class="alert-link">return to checkout</a>.</span>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Left: Address List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-geo-alt me-2 text-success"></i>Saved Addresses</h5>

                    <?php if (empty($addresses)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-geo-alt" style="font-size:3rem; opacity:.3;"></i>
                            <p class="mt-2 mb-0">You have no saved addresses yet.</p>
                            <p class="small">Use the form on the right to add one.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($addresses as $addr): ?>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100 position-relative
                                        <?= $addr['isDefault'] ? 'border-success border-2 bg-light' : '' ?>"
                                        style="transition: box-shadow .2s;">

                                        <?php if ($addr['isDefault']): ?>
                                            <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                                <i class="bi bi-star-fill me-1"></i>Default
                                            </span>
                                        <?php endif; ?>

                                        <div class="mb-1">
                                            <span class="badge bg-success-subtle text-success fw-semibold">
                                                <?= htmlspecialchars($addr['label'] ?? 'Address') ?>
                                            </span>
                                        </div>
                                        <div class="fw-bold"><?= htmlspecialchars($addr['recipientName']) ?></div>
                                        <div class="text-muted small">
                                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($addr['phoneNumber']) ?>
                                        </div>
                                        <div class="small mt-1 text-muted">
                                            <i class="bi bi-geo me-1"></i>
                                            <?= htmlspecialchars($addr['street']) ?>,
                                            <?= htmlspecialchars($addr['barangay']) ?>,
                                            <?= htmlspecialchars($addr['city']) ?>
                                            <?php if ($addr['province']): ?>, <?= htmlspecialchars($addr['province']) ?><?php endif; ?>
                                            <?php if ($addr['zipCode']): ?> <?= htmlspecialchars($addr['zipCode']) ?><?php endif; ?>
                                        </div>

                                        <div class="d-flex gap-2 flex-wrap mt-3">
                                            <?php if (!$addr['isDefault']): ?>
                                                <a href="addresses?setDefault=<?= $addr['addressId'] ?><?= $returnToCheckout ? '&from=checkout' : '' ?>"
                                                    class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-star me-1"></i>Set Default
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($addr), ENT_QUOTES) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="addresses?deleteAddress=<?= $addr['addressId'] ?><?= $returnToCheckout ? '&from=checkout' : '' ?>"
                                                onclick="return confirm('Delete this address?');"
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

        <!-- Right: Add Address Form -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top:80px;">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-plus-circle me-2 text-success"></i>Add New Address</h5>
                    <form method="POST" action="addresses<?= $returnToCheckout ? '?from=checkout' : '' ?>" class="row g-2">

                        <div class="col-12">
                            <label class="form-label fw-semibold">Label</label>
                            <input type="text" name="label" class="form-control" placeholder="e.g. Home, Office" value="Home">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Recipient Name <span class="text-danger">*</span></label>
                            <input type="text" name="recipientName" class="form-control"
                                value="<?= htmlspecialchars(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? '')) ?>"
                                required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phoneNumber_addr" class="form-control"
                                value="<?= htmlspecialchars($user['phoneNumber'] ?? '') ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Street <span class="text-danger">*</span></label>
                            <input type="text" name="street" class="form-control" placeholder="House/Unit No., Street" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Barangay <span class="text-danger">*</span></label>
                            <input type="text" name="barangay" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">City / Municipality <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Province</label>
                            <input type="text" name="province" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Zip Code</label>
                            <input type="text" name="zipCode" class="form-control" maxlength="10">
                        </div>

                        <div class="col-12">
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="isDefault" id="isDefault"
                                    <?= empty($addresses) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="isDefault">Set as default address</label>
                            </div>
                        </div>

                        <div class="col-12 mt-2">
                            <button type="submit" name="addAddress" class="btn btn-success w-100">
                                <i class="bi bi-plus-circle me-1"></i> Add Address
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Edit Address Modal -->
<div class="modal fade" id="editAddressModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2 text-success"></i>Edit Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="addresses<?= $returnToCheckout ? '?from=checkout' : '' ?>">
                <div class="modal-body row g-3">
                    <input type="hidden" name="addressId" id="edit_addressId">

                    <div class="col-12">
                        <label class="form-label fw-semibold">Label</label>
                        <input type="text" name="label" id="edit_label" class="form-control" placeholder="e.g. Home, Office">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Recipient Name <span class="text-danger">*</span></label>
                        <input type="text" name="recipientName" id="edit_recipientName" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phoneNumber_addr" id="edit_phoneNumber" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Street <span class="text-danger">*</span></label>
                        <input type="text" name="street" id="edit_street" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Barangay <span class="text-danger">*</span></label>
                        <input type="text" name="barangay" id="edit_barangay" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">City / Municipality <span class="text-danger">*</span></label>
                        <input type="text" name="city" id="edit_city" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Province</label>
                        <input type="text" name="province" id="edit_province" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Zip Code</label>
                        <input type="text" name="zipCode" id="edit_zipCode" class="form-control" maxlength="10">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="isDefault" id="edit_isDefault">
                            <label class="form-check-label small" for="edit_isDefault">Set as default address</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="editAddress" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>