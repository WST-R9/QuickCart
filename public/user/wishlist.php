<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$userId  = $_SESSION['authUser']['userId'] ?? 0;
$success = '';

// ----------------------------------------
// HANDLE REMOVE FROM WISHLIST
// ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['removeWishlist'])) {
    $productId = (int)($_POST['productId'] ?? 0);
    if ($productId > 0) {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE userId = ? AND productId = ?");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $stmt->close();
        $success = 'Item removed from your wishlist.';
    }
}

// ----------------------------------------
// HANDLE MOVE TO CART
// ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['moveToCart'])) {
    $productId = (int)($_POST['productId'] ?? 0);
    if ($productId > 0) {
        // Add to cart (or increment if exists)
        $stmt = $conn->prepare(
            "INSERT INTO cart (userId, productId, quantity)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE quantity = quantity + 1"
        );
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $stmt->close();

        // Remove from wishlist
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE userId = ? AND productId = ?");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $stmt->close();

        $success = 'Item moved to your cart!';
    }
}

// ----------------------------------------
// FETCH WISHLIST ITEMS
// ----------------------------------------
$stmt = $conn->prepare(
    "SELECT w.wishlistId, p.productId, p.name, p.price, p.stock, p.imageUrl, p.status,
            c.name AS categoryName
     FROM wishlist w
     JOIN products p ON w.productId = p.productId
     LEFT JOIN categories c ON p.categoryId = c.categoryId
     WHERE w.userId = ?
     ORDER BY w.addedAt DESC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$wishlistResult = $stmt->get_result();
$stmt->close();
?>

<div class="pagetitle">
  <h1>My Wishlist</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Wishlist</li>
    </ol>
  </nav>
</div>

<section class="section">

  <?php if ($success): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <?php if ($wishlistResult->num_rows === 0): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state">
          <i class="bi bi-heart"></i>
          <h5>Your wishlist is empty</h5>
          <p>Save products you love and come back to them anytime.</p>
          <a href="allProducts" class="btn btn-primary mt-2">
            <i class="bi bi-shop me-1"></i> Browse Products
          </a>
        </div>
      </div>
    </div>

  <?php else: ?>

    <!-- Results meta -->
    <div class="results-meta">
      <strong><?= $wishlistResult->num_rows ?></strong>
      item<?= $wishlistResult->num_rows !== 1 ? 's' : '' ?> saved
    </div>

    <div class="row g-3">
      <?php while ($item = $wishlistResult->fetch_assoc()):
        $outOfStock = $item['stock'] <= 0 || $item['status'] !== 'active';
        $lowStock   = !$outOfStock && $item['stock'] <= 5;
      ?>
        <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
          <div class="product-card">
            <div class="product-img-wrap">
              <?php if ($outOfStock): ?>
                <span class="bg-danger text-white product-badge">Out of Stock</span>
              <?php elseif ($lowStock): ?>
                <span class="bg-warning text-dark product-badge">Low Stock</span>
              <?php endif; ?>
              <img src="../uploads/products/<?= htmlspecialchars($item['imageUrl'] ?? '') ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   onerror="this.src='assets/img/product-placeholder.png'"
                   style="<?= $outOfStock ? 'opacity:.6;' : '' ?>">
            </div>
            <div class="product-body">
              <div class="product-category">
                <?= htmlspecialchars($item['categoryName'] ?? 'General') ?>
              </div>
              <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
              <div class="product-price">₱<?= number_format($item['price'], 2) ?></div>
              <div class="product-stock">
                <i class="bi bi-box-seam me-1"></i>
                <?= $outOfStock ? 'Out of stock' : $item['stock'].' in stock' ?>
              </div>

              <!-- Move to Cart -->
              <?php if (!$outOfStock): ?>
                <form action="" method="POST" class="mb-1">
                  <input type="hidden" name="productId" value="<?= (int)$item['productId'] ?>">
                  <button type="submit" name="moveToCart" class="btn-add-cart">
                    <i class="bi bi-cart-plus me-1"></i> Move to Cart
                  </button>
                </form>
              <?php else: ?>
                <button class="btn-add-cart" disabled style="opacity:.5;cursor:not-allowed;">
                  <i class="bi bi-cart-x me-1"></i> Unavailable
                </button>
              <?php endif; ?>

              <!-- Remove from Wishlist -->
              <form action="" method="POST">
                <input type="hidden" name="productId" value="<?= (int)$item['productId'] ?>">
                <button type="submit" name="removeWishlist"
                        class="btn btn-sm btn-outline-danger w-100 mt-1"
                        onclick="return confirm('Remove this item from your wishlist?')">
                  <i class="bi bi-heart-break me-1"></i> Remove
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

    <div class="text-center mt-4">
      <a href="allProducts" class="btn btn-outline-primary">
        <i class="bi bi-shop me-1"></i> Continue Shopping
      </a>
    </div>

  <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>