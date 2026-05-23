<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$userId = $_SESSION['authUser']['userId'] ?? 0;

$stmt = $conn->prepare("
    SELECT w.wishlistId, p.productId, p.name, p.price, p.stock, p.imageUrl,
           c.name AS categoryName
    FROM wishlist w
    JOIN products p ON w.productId = p.productId
    LEFT JOIN categories c ON p.categoryId = c.categoryId
    WHERE w.userId = ?
    ORDER BY w.addedAt DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

  <?php if (empty($items)): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state">
          <i class="bi bi-heart"></i>
          <h5>Your wishlist is empty</h5>
          <p>Save products you love and come back to them later.</p>
          <a href="allProducts" class="btn btn-primary mt-2">
            <i class="bi bi-bag me-1"></i> Browse Products
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <p class="text-muted mb-0"><?= count($items) ?> saved item<?= count($items) !== 1 ? 's' : '' ?></p>
      <a href="allProducts" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-bag me-1"></i> Continue Shopping
      </a>
    </div>

    <div class="row g-3">
      <?php foreach ($items as $product): ?>
        <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
          <div class="product-card">
            <div class="product-img-wrap">
              <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                <span class="badge bg-warning text-dark product-badge">Low Stock</span>
              <?php elseif ($product['stock'] === 0): ?>
                <span class="badge bg-secondary product-badge">Out of Stock</span>
              <?php endif; ?>

              <!-- Remove from wishlist (filled heart) -->
              <form action="../../app/controllers/wishlistController.php" method="POST"
                    style="position:absolute; top:8px; right:8px; z-index:2;">
                <input type="hidden" name="productId" value="<?= $product['productId'] ?>">
                <button type="submit" name="removeFromWishlist"
                        class="btn btn-sm btn-light rounded-circle"
                        style="width:32px; height:32px; padding:0; border:none; background:rgba(255,255,255,0.9); box-shadow:0 1px 4px rgba(0,0,0,0.12);"
                        title="Remove from wishlist">
                  <i class="bi bi-heart-fill text-danger" style="font-size:13px;"></i>
                </button>
              </form>

              <img src="../uploads/products/<?= htmlspecialchars($product['imageUrl'] ?? '') ?>"
                   alt="<?= htmlspecialchars($product['name']) ?>"
                   onerror="this.src='assets/img/product-placeholder.png'">
            </div>
            <div class="product-body">
              <div class="product-category"><?= htmlspecialchars($product['categoryName'] ?? 'General') ?></div>
              <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
              <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
              <div class="product-stock">
                <i class="bi bi-box-seam me-1"></i><?= $product['stock'] ?> in stock
              </div>
              <?php if ($product['stock'] > 0): ?>
                <form action="../../app/controllers/cartController.php" method="POST">
                  <input type="hidden" name="productId" value="<?= (int) $product['productId'] ?>">
                  <input type="hidden" name="quantity" value="1">
                  <button type="submit" name="addToCart" class="btn-add-cart">
                    <i class="bi bi-cart-plus me-1"></i> Add to Cart
                  </button>
                </form>
              <?php else: ?>
                <button class="btn-add-cart" disabled style="opacity:0.5; cursor:not-allowed;">
                  Out of Stock
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>