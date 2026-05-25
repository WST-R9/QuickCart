<?php
// categoryPage.php — included by each category page, expects $catId, $catTitle, $catIcon

$userId = $_SESSION['authUser']['userId'] ?? 0;

$search = trim($_GET['search'] ?? '');
$priceRange = $_GET['price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = "p.status = 'active' AND p.stock > 0 AND (p.categoryId = ? OR c.parentId = ?)";
$params = [$catId, $catId];
$types = 'ii';

if ($search !== '') {
  $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
  $like = "%$search%";
  $params[] = $like;
  $params[] = $like;
  $types .= 'ss';
}

switch ($priceRange) {
  case 'under50':
    $where .= " AND p.price < 50";
    break;
  case '50to200':
    $where .= " AND p.price BETWEEN 50 AND 200";
    break;
  case '200to500':
    $where .= " AND p.price BETWEEN 200 AND 500";
    break;
  case 'above500':
    $where .= " AND p.price > 500";
    break;
}

$orderBy = match ($sort) {
  'price_asc' => 'p.price ASC',
  'price_desc' => 'p.price DESC',
  'name_asc' => 'p.name ASC',
  default => 'p.createdAt DESC',
};

$priceLabels = [
  'under50' => 'Under ₱50',
  '50to200' => '₱50 – ₱200',
  '200to500' => '₱200 – ₱500',
  'above500' => '₱500+',
];

// Count
$countSql = "SELECT COUNT(*) AS total
             FROM products p
             LEFT JOIN categories c ON p.categoryId = c.categoryId
             WHERE $where";
$stmt = $conn->prepare($countSql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalProducts = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = max(1, (int) ceil($totalProducts / $perPage));

// Products
$sql = "SELECT p.productId, p.name, p.price, p.stock, p.imageUrl,
               c.name AS categoryName
        FROM products p
        LEFT JOIN categories c ON p.categoryId = c.categoryId
        WHERE $where
        GROUP BY p.productId
        ORDER BY $orderBy
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$allParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($types . 'ii', ...$allParams);
$stmt->execute();
$productsResult = $stmt->get_result();

// Wishlist IDs for heart state
$wishlistIds = [];
$wStmt = $conn->prepare("SELECT productId FROM wishlist WHERE userId = ?");
$wStmt->bind_param("i", $userId);
$wStmt->execute();
foreach ($wStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $w) {
  $wishlistIds[] = $w['productId'];
}
$wStmt->close();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<div class="pagetitle">
  <h1><?= htmlspecialchars($catTitle) ?></h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($catTitle) ?></li>
    </ol>
  </nav>
</div>

<section class="section">

  <!-- Filter bar -->
  <div class="d-flex align-items-center mb-3 px-3 py-2 bg-white rounded-3 border"
    style="border-color:#d4e8da !important; gap:0;">

    <div class="category-pills flex-grow-1" id="categoryPills" style="min-width:0;">
      <a href="<?= $currentPage ?>?sort=<?= urlencode($sort) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
        class="pill <?= $priceRange === '' ? 'active' : '' ?>">All</a>
      <a href="<?= $currentPage ?>?price=under50&sort=<?= urlencode($sort) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
        class="pill <?= $priceRange === 'under50' ? 'active' : '' ?>">Under ₱50</a>
      <a href="<?= $currentPage ?>?price=50to200&sort=<?= urlencode($sort) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
        class="pill <?= $priceRange === '50to200' ? 'active' : '' ?>">₱50 – ₱200</a>
      <a href="<?= $currentPage ?>?price=200to500&sort=<?= urlencode($sort) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
        class="pill <?= $priceRange === '200to500' ? 'active' : '' ?>">₱200 – ₱500</a>
      <a href="<?= $currentPage ?>?price=above500&sort=<?= urlencode($sort) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
        class="pill <?= $priceRange === 'above500' ? 'active' : '' ?>">₱500+</a>
    </div>

    <div class="flex-shrink-0 mx-3" style="width:1px; height:28px; background:#d4e8da;"></div>

    <form method="GET" action="" class="d-flex align-items-center gap-2 flex-shrink-0">
      <?php if ($search): ?>
        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
      <?php endif; ?>
      <?php if ($priceRange): ?>
        <input type="hidden" name="price" value="<?= htmlspecialchars($priceRange) ?>">
      <?php endif; ?>
      <span class="text-muted small fw-semibold text-nowrap">Sort by</span>
      <select name="sort" class="form-select form-select-sm border-0 fw-bold" style="width:auto; box-shadow:none;"
        onchange="this.form.submit()">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low → High</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A → Z</option>
      </select>
      <?php if ($search || $priceRange || $sort !== 'newest'): ?>
        <a href="<?= $currentPage ?>" class="btn btn-sm btn-outline-secondary flex-shrink-0" title="Clear filters">
          <i class="bi bi-x-circle"></i>
        </a>
      <?php endif; ?>
    </form>

  </div>

  <!-- Results meta -->
  <div class="results-meta mb-3">
    Showing <strong><?= $totalProducts ?></strong> product<?= $totalProducts !== 1 ? 's' : '' ?>
    <?php if ($search): ?> for "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
    <?php if ($priceRange && isset($priceLabels[$priceRange])): ?>
      in <strong><?= $priceLabels[$priceRange] ?></strong>
    <?php endif; ?>
  </div>

  <!-- Product Grid -->
  <?php if ($productsResult->num_rows === 0): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state">
          <i class="bi bi-bag-x"></i>
          <h5>No products found</h5>
          <p>Try a different filter or search term.</p>
          <a href="<?= $currentPage ?>" class="btn btn-primary mt-2 d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Back to <?= htmlspecialchars($catTitle) ?>
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php while ($product = $productsResult->fetch_assoc()):
        $inWishlist = in_array($product['productId'], $wishlistIds);
        ?>
        <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
          <div class="product-card">
            <div class="product-img-wrap">
              <?php if ($product['stock'] <= 5): ?>
                <span class="badge bg-warning text-dark product-badge">Low Stock</span>
              <?php endif; ?>
              <a href="productDisplay.php?id=<?= (int) $product['productId'] ?>">
                <img src="../uploads/products/<?= htmlspecialchars($product['imageUrl'] ?? '') ?>"
                  alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='assets/img/product-placeholder.png'">
              </a>
            </div>
            <div class="product-body">
              <div class="product-category"><?= htmlspecialchars($product['categoryName'] ?? 'General') ?></div>
              <div class="product-name">
                <a href="productDisplay.php?id=<?= (int) $product['productId'] ?>"
                  style="text-decoration:none; color:inherit;">
                  <?= htmlspecialchars($product['name']) ?>
                </a>
              </div>
              <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
              <div class="product-stock">
                <i class="bi bi-box-seam me-1"></i><?= $product['stock'] ?> in stock
              </div>
              <div class="d-flex gap-2 mt-2 align-items-stretch">
                <form action="../../app/controllers/cartController.php" method="POST" class="flex-grow-1">
                  <input type="hidden" name="productId" value="<?= (int) $product['productId'] ?>">
                  <input type="hidden" name="quantity" value="1">
                  <button type="submit" name="addToCart" class="btn-add-cart w-100">
                    Add to Cart
                  </button>
                </form>
                <form action="../../app/controllers/wishlistController.php" method="POST" class="d-flex align-items-center">
                  <input type="hidden" name="productId" value="<?= $product['productId'] ?>">
                  <button type="submit" name="<?= $inWishlist ? 'removeFromWishlist' : 'addToWishlist' ?>"
                    class="btn btn-sm btn-light rounded-circle wishlist-btn <?= $inWishlist ? 'wishlisted' : '' ?>"
                    style="width:36px; height:36px; padding:0; border:1px solid #dee2e6; flex-shrink:0;"
                    title="<?= $inWishlist ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                    <i class="bi <?= $inWishlist ? 'bi-heart-fill text-danger' : 'bi-heart text-muted' ?>"
                      style="font-size:13px;"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav class="mt-4 d-flex justify-content-center">
        <ul class="pagination">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link"
              href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&price=<?= urlencode($priceRange) ?>&sort=<?= urlencode($sort) ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          <?php
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
          if ($start > 1): ?>
            <li class="page-item">
              <a class="page-link"
                href="?page=1&search=<?= urlencode($search) ?>&price=<?= urlencode($priceRange) ?>&sort=<?= urlencode($sort) ?>">1</a>
            </li>
            <?php if ($start > 2): ?>
              <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
          <?php endif; ?>
          <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link"
                href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&price=<?= urlencode($priceRange) ?>&sort=<?= urlencode($sort) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
              <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item">
              <a class="page-link"
                href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&price=<?= urlencode($priceRange) ?>&sort=<?= urlencode($sort) ?>"><?= $totalPages ?></a>
            </li>
          <?php endif; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link"
              href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&price=<?= urlencode($priceRange) ?>&sort=<?= urlencode($sort) ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
      <p class="text-center text-muted small">Page <?= $page ?> of <?= $totalPages ?></p>
    <?php endif; ?>
  <?php endif; ?>

</section>