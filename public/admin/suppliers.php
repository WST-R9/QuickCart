<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// ADD SUPPLIER
if (isset($_POST['addSupplier'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $contactName = mysqli_real_escape_string($conn, $_POST['contactName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $insertQuery = "INSERT INTO suppliers (name, contactName, email, phone, address)
                    VALUES ('$name', '$contactName', '$email', '$phone', '$address')";

    mysqli_query($conn, $insertQuery);

    echo "<script>alert('Supplier added successfully!'); window.location.href='suppliers.php';</script>";
    exit;
}

// DELETE SUPPLIER
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    mysqli_query($conn, "DELETE FROM suppliers WHERE supplierId = $deleteId");

    echo "<script>alert('Supplier deleted successfully!'); window.location.href='suppliers.php';</script>";
    exit;
}

// FETCH SUPPLIERS
$suppliersQuery = "SELECT * FROM suppliers ORDER BY createdAt DESC";
$suppliersResult = mysqli_query($conn, $suppliersQuery);
?>

<div class="pagetitle">
  <h1>Suppliers</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Suppliers</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">

    <!-- ADD SUPPLIER -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Add Supplier</h5>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Supplier Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Contact Person</label>
              <input type="text" name="contactName" class="form-control">
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control">
            </div>

            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control">
            </div>

            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control"></textarea>
            </div>

            <button type="submit" name="addSupplier" class="btn btn-success w-100">
              <i class="bi bi-plus-circle"></i> Add Supplier
            </button>
          </form>

        </div>
      </div>
    </div>

    <!-- SUPPLIERS TABLE -->
    <div class="col-lg-8">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Supplier List</h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Date Added</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>
              <?php while ($row = mysqli_fetch_assoc($suppliersResult)): ?>
                <tr>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td><?= $row['contactName'] ? htmlspecialchars($row['contactName']) : "N/A" ?></td>
                  <td><?= $row['email'] ? htmlspecialchars($row['email']) : "N/A" ?></td>
                  <td><?= $row['phone'] ? htmlspecialchars($row['phone']) : "N/A" ?></td>
                  <td><?= date("M d, Y", strtotime($row['createdAt'])) ?></td>

                  <td>
                    <a href="suppliers-edit.php?id=<?= $row['supplierId'] ?>" class="btn btn-sm btn-primary">
                      <i class="bi bi-pencil"></i>
                    </a>

                    <a href="suppliers.php?delete=<?= $row['supplierId'] ?>"
                       onclick="return confirm('Are you sure you want to delete this supplier?');"
                       class="btn btn-sm btn-danger">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>

          </table>

        </div>
      </div>
    </div>

  </div>
</section>

<?php include('./includes/footer.php'); ?>