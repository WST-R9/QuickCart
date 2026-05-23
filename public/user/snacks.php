<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$userId = $_SESSION['authUser']['userId'] ?? 0;

// ----------------------------------------
// GET SNACKS CATEGORY ID
// ----------------------------------------
$catResult = mysqli_query($conn, "SELECT categoryId, name FROM categories WHERE name = 'Snacks' LIMIT 1");
$category  = mysqli_fetch_assoc($catResult);
$catId     = $category['categoryId'] ?? null;
$catName   = $category['name'] ?? 'Snacks';

if (!$catId) $catId = 0;

// ----------------------------------------
// FILTERS
// ----------------------------------------
$search  = trim($_GET['search'] ?? '');
$sort    = $_GET['sort'] ?? 'newest';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

// ----------------------------------------
// BUILD QUERY
// ----------------------------------------
$where  = "p.status = 'active' AND p.stock > 0";
$params = [];
$types  = '';

if ($search !== '') {
    $where   .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

if ($catId > 0) {
    $where   .= " AND (p.categoryId = ? OR c2.parentId = ?)";
    $params[] = $catId;
    $params[] = $catId;
    $types   .= 'ii';
}

$orderBy = match ($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name_asc'   => 'p.name ASC',
    default      => 'p.createdAt DESC',
};

// Count
$countSql = "SELECT COUNT(*) AS total
             FROM products p
             LEFT JOIN categories c  ON p.categoryId = c.categoryId
             LEFT JOIN categories c2 ON c.categoryId  = c2.categoryId
             WHERE $where";
$stmt = $conn->prepare($countSql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalProducts = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = max(1, (int)ceil($totalProducts / $perPage));

// Products
$sql = "SELECT p.productId, p.name, p.price, p.stock, p.imageUrl,
               c.name AS categoryName
        FROM products p
        LEFT JOIN categories c  ON p.categoryId = c.categoryId
        LEFT JOIN categories c2 ON c.categoryId  = c2.categoryId
        WHERE $where
        GROUP BY p.productId
        ORDER BY $orderBy
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes  = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$productsResult = $stmt->get_result();

// Cart count
$stmt2 = $conn->prepare("SELECT IFNULL(SUM(quantity), 0) AS cartCount FROM cart WHERE userId = ?");
$stmt2->bind_param("i", $userId);
$stmt2->execute();
$cartCount = (int)$stmt2->get_result()->fetch_assoc()['cartCount'];
$stmt2->close();
?>

<div class="pagetitle">
  <h1><?= htmlspecialchars($catName) ?></h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($catName) ?></li>
    </ol>
  </nav>
</div>

<section class="section">

  <!-- Search + Sort bar -->
  <div class="filter-bar">
    <form method="GET" action="" class="row g-2 align-items-center">
      <div class="col-12 col-md-5">
        <div class="input-group">
          <span class="input-group-text filter-bar__search-icon">
            <i class="bi bi-search text-muted"></i>
          </span>
          <input type="text" name="search" class="form-control filter-bar__input"
                 placeholder="Search snacks &amp; beverages…"
                 value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>
      <div class="col-6 col-md-3">
        <select name="sort" class="form-select filter-bar__select">
          <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest First</option>
          <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: Low → High</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
          <option value="name_asc"   <?= $sort === 'name_asc'   ? 'selected' : '' ?>>Name: A → Z</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-funnel me-1"></i> Filter
        </button>
      </div>
      <?php if ($search || $sort !== 'newest'): ?>
        <div class="col-12 col-md-2">
          <a href="snacks" class="btn btn-outline-secondary w-100 btn-clear-filter">
            <i class="bi bi-x-circle me-1"></i> Clear
          </a>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- Results meta -->
  <div class="results-meta">
    Showing <strong><?= $totalProducts ?></strong> product<?= $totalProducts !== 1 ? 's' : '' ?>
    <?php if ($search): ?> for "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
  </div>

  <!-- Product Grid -->
  <?php if ($productsResult->num_rows === 0): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state">
          <i class="bi bi-bag-x"></i>
          <h5>No products found</h5>
          <p>Try a different search term or check back soon.</p>
          <a href="snacks" class="btn btn-primary mt-2">
            <i class="bi bi-arrow-left me-1"></i> Back to Snacks
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php while ($product = $productsResult->fetch_assoc()): ?>
        <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
          <div class="product-card">
            <div class="product-img-wrap">
              <?php if ($product['stock'] <= 5): ?>
                <span class="bg-warning text-dark product-badge">Low Stock</span>
              <?php endif; ?>
              <img src="../uploads/products/<?= htmlspecialchars($product['imageUrl'] ?? '') ?>"
                   alt="<?= htmlspecialchars($product['name']) ?>"
                   onerror="this.src='assets/img/product-placeholder.png'">
            </div>
            <div class="product-body">
              <div class="product-category">
                <?= htmlspecialchars($product['categoryName'] ?? 'Snacks') ?>
              </div>
              <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
              <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
              <div class="product-stock">
                <i class="bi bi-box-seam me-1"></i><?= $product['stock'] ?> in stock
              </div>
              <form action="../../app/controllers/cartController.php" method="POST">
                <input type="hidden" name="productId" value="<?= (int)$product['productId'] ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" name="addToCart" class="btn-add-cart">
                  <i class="bi bi-cart-plus me-1"></i> Add to Cart
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav class="mt-4 d-flex justify-content-center flex-wrap gap-2">
        <ul class="pagination">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          <?php
          $start = max(1, $page - 2);
          $end   = min($totalPages, $page + 2);
          if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
          <?php endif; ?>
          <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>"><?= $totalPages ?></a></li>
          <?php endif; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul>
        <p class="text-center text-muted small w-100">Page <?= $page ?> of <?= $totalPages ?></p>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>