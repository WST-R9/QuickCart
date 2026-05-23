<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$userId = $_SESSION['authUser']['userId'] ?? 0;

// ----------------------------------------
// GET RICE & GRAINS CATEGORY ID
// ----------------------------------------
$catResult = mysqli_query($conn, "SELECT categoryId, name FROM categories WHERE name = 'Rice & Grains' LIMIT 1");
$category  = mysqli_fetch_assoc($catResult);
$catId     = $category['categoryId'] ?? null;
$catName   = $category['name'] ?? 'Rice & Grains';

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

  <div class="filter-bar">
    <form method="GET" action="" class="row g-2 align-items-center">
      <div class="col-12 col-md-5">
        <div class="input-group">
          <span class="input-group-text filter-bar__search-icon">
            <i class="bi bi-search text-muted"></i>
          </span>
          <input type="text" name="search" class="form-control filter-bar__input"
                 placeholder="Search rice &amp; grains…"
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
          <a href="riceGrains" class="btn btn-outline-secondary w-100">
            <i class="bi bi-x-circle me-1"></i> Clear
          </a>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <div class="results-meta">
    Showing <strong><?= $totalProducts ?></strong> product<?= $totalProducts !== 1 ? 's' : '' ?>
    <?php if ($search): ?> for "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
  </div>

  <?php if ($productsResult->num_rows === 0): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state">
          <i class="bi bi-basket2"></i>
          <h5>Coming Soon</h5>
          <p>We're sourcing the finest rice and grain products — check back soon!</p>
          <a href="allProducts" class="btn btn-primary mt-2">
            <i class="bi bi-shop me-1"></i> Browse All Products
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
                <?= htmlspecialchars($product['categoryName'] ?? 'Rice & Grains') ?>
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
  <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>