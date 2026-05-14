<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");
include_once("../../app/helpers/activityLog.php");

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

    $imageSet = "";

    if (!empty($_FILES['productImage']['name'])) {
        $uploadDir = "../uploads/products/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            echo "<script>alert('Invalid image type.'); window.location.href='inventory-edit.php?id=$productId';</script>";
            exit;
        }

        if ($_FILES['productImage']['size'] > 2 * 1024 * 1024) {
            echo "<script>alert('Image exceeds 2MB limit.'); window.location.href='inventory-edit.php?id=$productId';</script>";
            exit;
        }

        // Delete old image if exists
        if (!empty($product['imageUrl'])) {
            $oldFile = "../uploads/products/" . $product['imageUrl'];
            if (file_exists($oldFile)) unlink($oldFile);
        }

        $filename = $product['uuid'] . '.' . $ext;
        move_uploaded_file($_FILES['productImage']['tmp_name'], $uploadDir . $filename);
        $imageSet = ", imageUrl='$filename'";
    }

    $updateQuery = "UPDATE products SET
        name='$name',
        slug='$slug',
        description='$description',
        price=$price,
        stock=$stock,
        categoryId=$categoryId,
        supplierId=$supplierId,
        status='$status'
        $imageSet
    WHERE productId=$productId";

    mysqli_query($conn, $updateQuery);
    echo "<script>alert('Product updated successfully!'); window.location.href='inventory.php';</script>";
    logActivity($conn, $_SESSION['user_id'], 'updated_product', 'inventory', $productId, $name);
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

          <form method="POST" enctype="multipart/form-data">
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

            <div class="mb-3">
              <label class="form-label">Product Image <span class="text-muted">(Optional)</span></label>

              <?php if (!empty($product['imageUrl'])): ?>
                <div class="mb-2 d-flex align-items-center gap-3">
                  <img src="../uploads/products/<?= htmlspecialchars($product['imageUrl']) ?>"
                       style="width:80px;height:80px;object-fit:cover;border-radius:8px;">
                  <small class="text-muted">Current image. Upload a new one to replace it.</small>
                </div>
              <?php endif; ?>

              <input type="file" name="productImage" class="form-control" accept="image/*">
              <small class="text-muted">JPG, PNG, WEBP. Max 2MB. Leave blank to keep current.</small>
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