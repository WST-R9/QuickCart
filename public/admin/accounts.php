<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// Get logged-in admin's userId from session
$userId = $_SESSION['user_id'];

// -----------------------------------------------
// UPDATE PROFILE INFO
// -----------------------------------------------
if (isset($_POST['updateProfile'])) {
    $firstName   = mysqli_real_escape_string($conn, trim($_POST['firstName']));
    $middleName  = mysqli_real_escape_string($conn, trim($_POST['middleName']));
    $lastName    = mysqli_real_escape_string($conn, trim($_POST['lastName']));
    $birthday    = trim($_POST['birthday']);
    $gender      = trim($_POST['gender']);
    $phoneNumber = trim($_POST['phoneNumber']);
    $emailAddress = mysqli_real_escape_string($conn, trim($_POST['emailAddress']));

    // Validate email
    if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format.";
        $_SESSION['code']    = "error";
        header("Location: accounts.php");
        exit;
    }

    // Validate phone
    if (!preg_match('/^\d{10,15}$/', $phoneNumber)) {
        $_SESSION['message'] = "Phone number must be 10–15 digits only.";
        $_SESSION['code']    = "error";
        header("Location: accounts.php");
        exit;
    }

    // Check email duplicate (exclude self)
    $checkEmail = $conn->prepare("SELECT userId FROM users WHERE emailAddress = ? AND userId != ? LIMIT 1");
    $checkEmail->bind_param("si", $emailAddress, $userId);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $_SESSION['message'] = "Email address is already in use by another account.";
        $_SESSION['code']    = "error";
        header("Location: accounts.php");
        exit;
    }
    $checkEmail->close();

    $updateProfile = $conn->prepare("UPDATE users 
        SET firstName=?, middleName=?, lastName=?, birthday=?, gender=?, phoneNumber=?, emailAddress=?
        WHERE userId=?");
    $updateProfile->bind_param("sssssssi",
        $firstName, $middleName, $lastName, $birthday, $gender, $phoneNumber, $emailAddress, $userId
    );
    $updateProfile->execute();
    $updateProfile->close();

    // Update session name
    $_SESSION['authUser']['fullName'] = $firstName . ' ' . $lastName;

    $_SESSION['message'] = "Profile updated successfully.";
    $_SESSION['code']    = "success";
    header("Location: accounts.php");
    exit;
}

// -----------------------------------------------
// CHANGE PASSWORD
// -----------------------------------------------
if (isset($_POST['changePassword'])) {
    $currentPassword = $_POST['currentPassword'];
    $newPassword     = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Fetch current password
    $fetchPass = $conn->prepare("SELECT password FROM users WHERE userId = ?");
    $fetchPass->bind_param("i", $userId);
    $fetchPass->execute();
    $fetchPass->bind_result($storedPassword);
    $fetchPass->fetch();
    $fetchPass->close();

    // NOTE: Plain-text check to match your existing system
    if ($currentPassword !== $storedPassword) {
        $_SESSION['message'] = "Current password is incorrect.";
        $_SESSION['code']    = "error";
        header("Location: accounts.php");
        exit;
    }

    if (strlen($newPassword) < 6) {
        $_SESSION['message'] = "New password must be at least 6 characters.";
        $_SESSION['code']    = "error";
        header("Location: accounts.php");
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['message'] = "New passwords do not match.";
        $_SESSION['code']    = "error";
        header("Location: accounts.php");
        exit;
    }

    $updatePass = $conn->prepare("UPDATE users SET password=? WHERE userId=?");
    $updatePass->bind_param("si", $newPassword, $userId);
    $updatePass->execute();
    $updatePass->close();

    $_SESSION['message'] = "Password changed successfully.";
    $_SESSION['code']    = "success";
    header("Location: accounts.php");
    exit;
}

// -----------------------------------------------
// ADD ADDRESS
// -----------------------------------------------
if (isset($_POST['addAddress'])) {
    $label         = mysqli_real_escape_string($conn, trim($_POST['label']));
    $recipientName = mysqli_real_escape_string($conn, trim($_POST['recipientName']));
    $phoneNumber   = trim($_POST['phoneNumber_addr']);
    $street        = mysqli_real_escape_string($conn, trim($_POST['street']));
    $barangay      = mysqli_real_escape_string($conn, trim($_POST['barangay']));
    $city          = mysqli_real_escape_string($conn, trim($_POST['city']));
    $province      = mysqli_real_escape_string($conn, trim($_POST['province']));
    $zipCode       = trim($_POST['zipCode']);
    $isDefault     = isset($_POST['isDefault']) ? 1 : 0;

    // If new address is default, unset others
    if ($isDefault) {
        $conn->query("UPDATE addresses SET isDefault=0 WHERE userId=$userId");
    }

    $insertAddr = $conn->prepare("INSERT INTO addresses 
        (userId, label, recipientName, phoneNumber, street, barangay, city, province, zipCode, isDefault)
        VALUES (?,?,?,?,?,?,?,?,?,?)");
    $insertAddr->bind_param("issssssssi",
        $userId, $label, $recipientName, $phoneNumber, $street, $barangay, $city, $province, $zipCode, $isDefault
    );
    $insertAddr->execute();
    $insertAddr->close();

    $_SESSION['message'] = "Address added successfully.";
    $_SESSION['code']    = "success";
    header("Location: accounts.php");
    exit;
}

// -----------------------------------------------
// DELETE ADDRESS
// -----------------------------------------------
if (isset($_GET['deleteAddress'])) {
    $addressId = intval($_GET['deleteAddress']);
    $conn->query("DELETE FROM addresses WHERE addressId=$addressId AND userId=$userId");

    $_SESSION['message'] = "Address deleted.";
    $_SESSION['code']    = "success";
    header("Location: accounts.php");
    exit;
}

// -----------------------------------------------
// SET DEFAULT ADDRESS
// -----------------------------------------------
if (isset($_GET['setDefault'])) {
    $addressId = intval($_GET['setDefault']);
    $conn->query("UPDATE addresses SET isDefault=0 WHERE userId=$userId");
    $conn->query("UPDATE addresses SET isDefault=1 WHERE addressId=$addressId AND userId=$userId");

    $_SESSION['message'] = "Default address updated.";
    $_SESSION['code']    = "success";
    header("Location: accounts.php");
    exit;
}

// -----------------------------------------------
// FETCH ADMIN INFO
// -----------------------------------------------
$adminQuery  = $conn->prepare("SELECT * FROM users WHERE userId=?");
$adminQuery->bind_param("i", $userId);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();
$admin       = $adminResult->fetch_assoc();
$adminQuery->close();

// FETCH ADDRESSES
$addrResult = mysqli_query($conn, "SELECT * FROM addresses WHERE userId=$userId ORDER BY isDefault DESC, createdAt DESC");
?>

<div class="pagetitle">
    <h1>Account Settings</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item active">Account Settings</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <div class="row">

        <!-- LEFT COLUMN -->
        <div class="col-lg-8">

            <!-- PROFILE CARD -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-person-circle me-1"></i> Profile Information
                    </h5>

                    <form method="POST">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="firstName" class="form-control"
                                       value="<?= htmlspecialchars($admin['firstName']) ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Middle Name</label>
                                <input type="text" name="middleName" class="form-control"
                                       value="<?= htmlspecialchars($admin['middleName'] ?? '') ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="lastName" class="form-control"
                                       value="<?= htmlspecialchars($admin['lastName']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="emailAddress" class="form-control"
                                       value="<?= htmlspecialchars($admin['emailAddress']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phoneNumber" class="form-control"
                                       value="<?= htmlspecialchars($admin['phoneNumber']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Birthday <span class="text-danger">*</span></label>
                                <input type="date" name="birthday" class="form-control"
                                       value="<?= htmlspecialchars($admin['birthday']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="Male"   <?= ($admin['gender'] == 'Male')   ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($admin['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted">Username</label>
                                <input type="text" class="form-control bg-light"
                                       value="<?= htmlspecialchars($admin['username']) ?>" disabled>
                                <small class="text-muted">Username cannot be changed.</small>
                            </div>

                            <div class="col-12">
                                <button type="submit" name="updateProfile" class="btn btn-success px-4">
                                    <i class="bi bi-save me-1"></i> Save Profile
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <!-- CHANGE PASSWORD CARD -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-shield-lock me-1"></i> Change Password
                    </h5>

                    <form method="POST" class="row g-3">

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="currentPassword" id="currentPassword"
                                       class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="currentPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="newPassword" id="newPassword"
                                       class="form-control" required minlength="6">
                                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="newPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimum 6 characters.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="confirmPassword" id="confirmPassword"
                                       class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="confirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="changePassword" class="btn btn-primary px-4">
                                <i class="bi bi-key me-1"></i> Change Password
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- ADDRESSES CARD -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-geo-alt me-1"></i> My Addresses
                    </h5>

                    <!-- Address List -->
                    <?php if (mysqli_num_rows($addrResult) > 0): ?>
                        <div class="row g-3 mb-4">
                            <?php while ($addr = mysqli_fetch_assoc($addrResult)): ?>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 position-relative h-100
                                        <?= $addr['isDefault'] ? 'border-success border-2' : '' ?>">

                                        <?php if ($addr['isDefault']): ?>
                                            <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                                <i class="bi bi-star-fill me-1"></i>Default
                                            </span>
                                        <?php endif; ?>

                                        <p class="mb-1">
                                            <span class="badge bg-secondary"><?= htmlspecialchars($addr['label'] ?? 'Address') ?></span>
                                        </p>
                                        <p class="mb-1 fw-semibold"><?= htmlspecialchars($addr['recipientName']) ?></p>
                                        <p class="mb-1 text-muted small"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($addr['phoneNumber']) ?></p>
                                        <p class="mb-2 text-muted small">
                                            <i class="bi bi-geo me-1"></i>
                                            <?= htmlspecialchars($addr['street']) ?>,
                                            <?= htmlspecialchars($addr['barangay']) ?>,
                                            <?= htmlspecialchars($addr['city']) ?>
                                            <?= $addr['province'] ? ', ' . htmlspecialchars($addr['province']) : '' ?>
                                            <?= $addr['zipCode'] ? ' ' . htmlspecialchars($addr['zipCode']) : '' ?>
                                        </p>

                                        <div class="d-flex gap-2 flex-wrap">
                                            <?php if (!$addr['isDefault']): ?>
                                                <a href="accounts.php?setDefault=<?= $addr['addressId'] ?>"
                                                   class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-star me-1"></i>Set Default
                                                </a>
                                            <?php endif; ?>

                                            <a href="accounts.php?deleteAddress=<?= $addr['addressId'] ?>"
                                               onclick="return confirm('Delete this address?');"
                                               class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>

                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-3">No addresses added yet.</p>
                    <?php endif; ?>

                    <!-- ADD ADDRESS FORM -->
                    <div class="border rounded p-3 bg-light">
                        <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-1 text-success"></i>Add New Address</h6>

                        <form method="POST" class="row g-2">

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Label</label>
                                <input type="text" name="label" class="form-control"
                                       placeholder="e.g. Home, Office" value="Home">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Recipient Name <span class="text-danger">*</span></label>
                                <input type="text" name="recipientName" class="form-control"
                                       value="<?= htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']) ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phoneNumber_addr" class="form-control"
                                       value="<?= htmlspecialchars($admin['phoneNumber']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Street <span class="text-danger">*</span></label>
                                <input type="text" name="street" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Barangay <span class="text-danger">*</span></label>
                                <input type="text" name="barangay" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                                <input type="text" name="city" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Province</label>
                                <input type="text" name="province" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Zip Code</label>
                                <input type="text" name="zipCode" class="form-control" maxlength="10">
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="isDefault" id="isDefault">
                                    <label class="form-check-label" for="isDefault">
                                        Set as default address
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" name="addAddress" class="btn btn-success px-4">
                                    <i class="bi bi-plus-circle me-1"></i> Add Address
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-lg-4">

            <!-- PROFILE SUMMARY CARD -->
            <div class="card text-center">
                <div class="card-body">
                    <div class="mb-3">
                        <div class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center"
                             style="width:80px;height:80px;">
                            <span style="font-size:2rem;font-weight:700;color:#fff;font-family:'Nunito',sans-serif;">
                                <?= strtoupper(substr($admin['firstName'], 0, 1) . substr($admin['lastName'], 0, 1)) ?>
                            </span>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-0">
                        <?= htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']) ?>
                    </h5>
                    <p class="text-muted mb-1 small">@<?= htmlspecialchars($admin['username']) ?></p>
                    <span class="badge bg-success mb-3">
                        <i class="bi bi-shield-check me-1"></i>Administrator
                    </span>

                    <ul class="list-group list-group-flush text-start">
                        <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                            <span class="text-muted small"><i class="bi bi-envelope me-2"></i>Email</span>
                            <span class="small text-truncate ms-2" style="max-width:160px;">
                                <?= htmlspecialchars($admin['emailAddress']) ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted small"><i class="bi bi-telephone me-2"></i>Phone</span>
                            <span class="small"><?= htmlspecialchars($admin['phoneNumber']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted small"><i class="bi bi-gender-ambiguous me-2"></i>Gender</span>
                            <span class="small"><?= htmlspecialchars($admin['gender']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted small"><i class="bi bi-calendar me-2"></i>Birthday</span>
                            <span class="small"><?= date("M d, Y", strtotime($admin['birthday'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted small"><i class="bi bi-clock me-2"></i>Member Since</span>
                            <span class="small"><?= date("M d, Y", strtotime($admin['dateCreated'])) ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- QUICK TIPS CARD -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-info-circle me-1"></i> Quick Tips</h5>

                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Keep your email address up to date for notifications.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Use a strong password with at least 8 characters.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Set a default address for faster order processing.
                        </li>
                        <li class="mb-0">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Your username cannot be changed after registration.
                        </li>
                    </ul>
                </div>
            </div>

            <!-- DANGER ZONE -->
            <div class="card border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i> Session
                    </h5>
                    <p class="text-muted small mb-3">
                        You are currently logged in as <strong><?= htmlspecialchars($admin['username']) ?></strong>.
                        Click below to sign out securely.
                    </p>
                    <form action="../../app/controllers/adminController.php" method="post">
                        <button type="submit" name="logoutButton" class="btn btn-outline-danger w-100">
                            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- Password Toggle Script -->
<script>
document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', function () {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
});
</script>

<?php include('./includes/footer.php'); ?>