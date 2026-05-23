<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$query   = trim($_GET['search'] ?? '');
$results = [];
$total   = 0;

if (strlen($query) >= 2) {
    $like = '%' . $conn->real_escape_string($query) . '%';

    $stmt = $conn->prepare("
        SELECT p.productId, p.name, p.slug, p.price, p.stock, p.imageUrl,
               c.name AS categoryName
        FROM   products p
        LEFT JOIN categories c ON p.categoryId = c.categoryId
        WHERE  p.status = 'active'
          AND  p.stock  > 0
          AND  (p.name LIKE ? OR p.description LIKE ?)
        ORDER BY
               CASE WHEN p.name LIKE ? THEN 0 ELSE 1 END,
               p.name ASC
        LIMIT 48
    ");

    $startsWith = $query . '%';
    $stmt->bind_param('sss', $like, $like, $startsWith);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $total   = count($results);
    $stmt->close();
}
?>

<div class="pagetitle">
  <h1>Search Results</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="allProducts">Products</a></li>
      <li class="breadcrumb-item active">Search</li>
    </ol>
  </nav>
</div>

<section class="section">

  <?php if ($query === '' || strlen($query) < 2): ?>
    <div class="alert alert-info">Enter at least 2 characters to search.</div>

  <?php elseif ($total === 0): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state">
          <i class="bi bi-bag-x"></i>
          <h5>No products found</h5>
          <p>No results for "<strong><?= htmlspecialchars($query) ?></strong>". Try a different keyword.</p>
          <a href="allProducts" class="btn btn-primary mt-2">
            <i class="bi bi-arrow-left me-1"></i>Browse All Products
          </a>
        </div>
      </div>
    </div>

  <?php else: ?>

    <div class="results-meta mb-3">
      Showing <strong><?= $total ?></strong> result<?= $total !== 1 ? 's' : '' ?>
      for "<strong><?= htmlspecialchars($query) ?></strong>"
    </div>

    <div class="row g-3">
      <?php foreach ($results as $product): ?>
        <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
          <div class="product-card">
            <div class="product-img-wrap">
              <?php if ($product['stock'] <= 5): ?>
                <span class="badge bg-warning text-dark product-badge">Low Stock</span>
              <?php endif; ?>
              <img src="../uploads/products/<?= htmlspecialchars($product['imageUrl'] ?? '') ?>"
                   alt="<?= htmlspecialchars($product['name']) ?>"
                   onerror="this.src='assets/img/product-placeholder.png'">
            </div>
            <div class="product-body">
              <div class="product-category">
                <?= htmlspecialchars($product['categoryName'] ?? 'General') ?>
              </div>
              <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
              <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
              <div class="product-stock">
                <i class="bi bi-box-seam me-1"></i><?= $product['stock'] ?> in stock
              </div>
              <form action="../../app/controllers/cartController.php" method="POST">
                <input type="hidden" name="productId" value="<?= (int) $product['productId'] ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" name="addToCart" class="btn-add-cart">
                  <i class="bi bi-cart-plus me-1"></i>Add to Cart
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>