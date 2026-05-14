<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");
include_once("../../app/helpers/activityLog.php");

// ----------------------
// ADD PRODUCT
// ----------------------
if (isset($_POST['addProduct'])) {
    $uuid = uniqid("prod_");
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $slug = mysqli_real_escape_string($conn, $_POST['slug']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $categoryId = !empty($_POST['categoryId']) ? intval($_POST['categoryId']) : "NULL";
    $supplierId = !empty($_POST['supplierId']) ? intval($_POST['supplierId']) : "NULL";
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $imageUrl = "NULL";

    if (!empty($_FILES['productImage']['name'])) {
        $uploadDir = "../uploads/products/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            echo "<script>alert('Invalid image type.'); window.location.href='inventory.php';</script>";
            exit;
        }

        if ($_FILES['productImage']['size'] > 2 * 1024 * 1024) {
            echo "<script>alert('Image exceeds 2MB limit.'); window.location.href='inventory.php';</script>";
            exit;
        }

        $filename = $uuid . '.' . $ext;
        move_uploaded_file($_FILES['productImage']['tmp_name'], $uploadDir . $filename);
        $imageUrl = "'" . $filename . "'";
    }

    $insertQuery = "INSERT INTO products (uuid, name, slug, description, price, stock, imageUrl, categoryId, supplierId, status)
                    VALUES ('$uuid', '$name', '$slug', '$description', $price, $stock, $imageUrl, $categoryId, $supplierId, '$status')";

    if (mysqli_query($conn, $insertQuery)) {
        echo "<script>alert('Product added successfully!'); window.location.href='inventory.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error adding product. Slug might already exist.');</script>";
    }
  logActivity($conn, $_SESSION['user_id'], 'added_product', 'inventory', $conn->insert_id, $name);

}

// ----------------------
// DELETE PRODUCT
// ----------------------
if (isset($_GET['delete'])) {
    $productId = intval($_GET['delete']);

    // Delete image file if exists
    $imgQuery = mysqli_query($conn, "SELECT imageUrl FROM products WHERE productId = $productId");
    $imgRow = mysqli_fetch_assoc($imgQuery);
    if (!empty($imgRow['imageUrl'])) {
        $imgFile = "../uploads/products/" . $imgRow['imageUrl'];
        if (file_exists($imgFile)) unlink($imgFile);
    }

    mysqli_query($conn, "DELETE FROM products WHERE productId = $productId");
    echo "<script>alert('Product deleted successfully!'); window.location.href='inventory.php';</script>";
    logActivity($conn, $_SESSION['user_id'], 'deleted_product', 'inventory', $productId, $imgRow['imageUrl']);
    exit;
}

// ----------------------
// FETCH PRODUCTS
// ----------------------
$productsQuery = "SELECT 
    p.*,
    c.name AS categoryName,
    s.name AS supplierName
FROM products p
LEFT JOIN categories c ON p.categoryId = c.categoryId
LEFT JOIN suppliers s ON p.supplierId = s.supplierId
ORDER BY p.createdAt DESC";

$productsResult = mysqli_query($conn, $productsQuery);

// FETCH CATEGORY DROPDOWN
$categoriesResult = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");

// FETCH SUPPLIER DROPDOWN
$suppliersResult = mysqli_query($conn, "SELECT * FROM suppliers ORDER BY name ASC");
?>

<div class="pagetitle">
  <h1>Inventory</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Inventory</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">

    <!-- ADD PRODUCT FORM -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Add Product</h5>

          <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
              <label class="form-label">Product Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Slug</label>
              <input type="text" name="slug" class="form-control" required>
              <small class="text-muted">Example: lucky-me-pancit-canton</small>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Price</label>
              <input type="number" name="price" class="form-control" step="0.01" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Stock</label>
              <input type="number" name="stock" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Category</label>
              <select name="categoryId" class="form-select">
                <option value="">None</option>
                <?php while ($cat = mysqli_fetch_assoc($categoriesResult)): ?>
                  <option value="<?= $cat['categoryId'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Supplier</label>
              <select name="supplierId" class="form-select">
                <option value="">None</option>
                <?php while ($sup = mysqli_fetch_assoc($suppliersResult)): ?>
                  <option value="<?= $sup['supplierId'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="out_of_stock">Out of Stock</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Product Image <span class="text-muted">(Optional)</span></label>
              <input type="file" name="productImage" class="form-control" accept="image/*">
              <small class="text-muted">JPG, PNG, WEBP. Max 2MB.</small>
            </div>

            <button type="submit" name="addProduct" class="btn btn-success w-100">
              <i class="bi bi-plus-circle"></i> Add Product
            </button>

          </form>

        </div>
      </div>
    </div>

    <!-- PRODUCT TABLE -->
    <div class="col-lg-8">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Product List</h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>
              <?php while ($row = mysqli_fetch_assoc($productsResult)): ?>
                <?php
                  $badge = "bg-secondary";
                  if ($row['status'] == "active") $badge = "bg-success";
                  elseif ($row['status'] == "inactive") $badge = "bg-dark";
                  elseif ($row['status'] == "out_of_stock") $badge = "bg-danger";
                ?>
                <tr>
                  <td>
                    <?php if (!empty($row['imageUrl'])): ?>
                      <img src="../uploads/products/<?= htmlspecialchars($row['imageUrl']) ?>"
                           style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                    <?php else: ?>
                      <div style="width:48px;height:48px;background:#e6f4ea;border-radius:6px;
                                  display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-image text-muted"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td><?= $row['categoryName'] ? htmlspecialchars($row['categoryName']) : "None" ?></td>
                  <td><?= $row['supplierName'] ? htmlspecialchars($row['supplierName']) : "None" ?></td>
                  <td>₱<?= number_format($row['price'], 2) ?></td>
                  <td><?= $row['stock'] ?></td>

                  <td>
                    <span class="badge <?= $badge ?>">
                      <?= ucfirst(str_replace("_", " ", $row['status'])) ?>
                    </span>
                  </td>

                  <td>
                    <a href="inventory-edit.php?id=<?= $row['productId'] ?>" class="btn btn-sm btn-primary">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a href="inventory.php?delete=<?= $row['productId'] ?>"
                       onclick="return confirm('Delete this product?');"
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