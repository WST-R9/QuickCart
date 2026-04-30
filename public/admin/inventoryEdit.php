<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

if (!isset($_GET['id'])) {
    header("Location: inventory.php");
    exit;
}

$productId = intval($_GET['id']);

$productQuery = "SELECT * FROM products WHERE productId = $productId";
$productResult = mysqli_query($conn, $productQuery);

if (mysqli_num_rows($productResult) == 0) {
    echo "<script>alert('Product not found!'); window.location.href='inventory.php';</script>";
    exit;
}

$product = mysqli_fetch_assoc($productResult);

// Dropdowns
$categoriesResult = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
$suppliersResult = mysqli_query($conn, "SELECT * FROM suppliers ORDER BY name ASC");

// UPDATE PRODUCT
if (isset($_POST['updateProduct'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $slug = mysqli_real_escape_string($conn, $_POST['slug']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $categoryId = !empty($_POST['categoryId']) ? intval($_POST['categoryId']) : "NULL";
    $supplierId = !empty($_POST['supplierId']) ? intval($_POST['supplierId']) : "NULL";
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $updateQuery = "UPDATE products SET
        name='$name',
        slug='$slug',
        description='$description',
        price=$price,
        stock=$stock,
        categoryId=$categoryId,
        supplierId=$supplierId,
        status='$status'
    WHERE productId=$productId";

    mysqli_query($conn, $updateQuery);

    echo "<script>alert('Product updated successfully!'); window.location.href='inventory.php';</script>";
    exit;
}
?>

<div class="pagetitle">
  <h1>Edit Product</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="inventory.php">Inventory</a></li>
      <li class="breadcrumb-item active">Edit</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-6">

      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Update Product</h5>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Product Name</label>
              <input type="text" name="name" class="form-control"
                     value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Slug</label>
              <input type="text" name="slug" class="form-control"
                     value="<?= htmlspecialchars($product['slug']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Price</label>
              <input type="number" step="0.01" name="price" class="form-control"
                     value="<?= htmlspecialchars($product['price']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Stock</label>
              <input type="number" name="stock" class="form-control"
                     value="<?= htmlspecialchars($product['stock']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Category</label>
              <select name="categoryId" class="form-select">
                <option value="">None</option>
                <?php while ($cat = mysqli_fetch_assoc($categoriesResult)): ?>
                  <option value="<?= $cat['categoryId'] ?>"
                    <?= ($product['categoryId'] == $cat['categoryId']) ? "selected" : "" ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Supplier</label>
              <select name="supplierId" class="form-select">
                <option value="">None</option>
                <?php while ($sup = mysqli_fetch_assoc($suppliersResult)): ?>
                  <option value="<?= $sup['supplierId'] ?>"
                    <?= ($product['supplierId'] == $sup['supplierId']) ? "selected" : "" ?>>
                    <?= htmlspecialchars($sup['name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select" required>
                <option value="active" <?= ($product['status'] == "active") ? "selected" : "" ?>>Active</option>
                <option value="inactive" <?= ($product['status'] == "inactive") ? "selected" : "" ?>>Inactive</option>
                <option value="out_of_stock" <?= ($product['status'] == "out_of_stock") ? "selected" : "" ?>>Out of Stock</option>
              </select>
            </div>

            <button type="submit" name="updateProduct" class="btn btn-primary w-100">
              <i class="bi bi-save"></i> Save Changes
            </button>

            <a href="inventory.php" class="btn btn-secondary w-100 mt-2">
              Cancel
            </a>

          </form>

        </div>
      </div>

    </div>
  </div>
</section>

<?php include('./includes/footer.php'); ?>