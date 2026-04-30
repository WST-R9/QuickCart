<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

if (!isset($_GET['id'])) {
    header("Location: suppliers.php");
    exit;
}

$supplierId = intval($_GET['id']);

$supplierQuery = "SELECT * FROM suppliers WHERE supplierId = $supplierId";
$supplierResult = mysqli_query($conn, $supplierQuery);

if (mysqli_num_rows($supplierResult) == 0) {
    echo "<script>alert('Supplier not found!'); window.location.href='suppliers.php';</script>";
    exit;
}

$supplier = mysqli_fetch_assoc($supplierResult);

// UPDATE SUPPLIER
if (isset($_POST['updateSupplier'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $contactName = mysqli_real_escape_string($conn, $_POST['contactName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $updateQuery = "UPDATE suppliers 
                    SET name='$name', contactName='$contactName', email='$email', phone='$phone', address='$address'
                    WHERE supplierId=$supplierId";

    mysqli_query($conn, $updateQuery);

    echo "<script>alert('Supplier updated successfully!'); window.location.href='suppliers.php';</script>";
    exit;
}
?>

<div class="pagetitle">
  <h1>Edit Supplier</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="suppliers.php">Suppliers</a></li>
      <li class="breadcrumb-item active">Edit</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-6">

      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Update Supplier</h5>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Supplier Name</label>
              <input type="text" name="name" class="form-control"
                     value="<?= htmlspecialchars($supplier['name']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Contact Person</label>
              <input type="text" name="contactName" class="form-control"
                     value="<?= htmlspecialchars($supplier['contactName']) ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control"
                     value="<?= htmlspecialchars($supplier['email']) ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control"
                     value="<?= htmlspecialchars($supplier['phone']) ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control"><?= htmlspecialchars($supplier['address']) ?></textarea>
            </div>

            <button type="submit" name="updateSupplier" class="btn btn-primary w-100">
              <i class="bi bi-save"></i> Save Changes
            </button>

            <a href="suppliers.php" class="btn btn-secondary w-100 mt-2">
              Cancel
            </a>
          </form>

        </div>
      </div>

    </div>
  </div>
</section>

<?php include('./includes/footer.php'); ?>