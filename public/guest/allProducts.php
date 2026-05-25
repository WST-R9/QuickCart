<?php
include_once("../../app/middleware/guest.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$search = trim($_GET['search'] ?? '');
$priceRange = $_GET['price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = "p.status = 'active' AND p.stock > 0";
$params = [];
$types = '';

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
if ($types !== '') {
  $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products p LEFT JOIN categories c ON p.categoryId = c.categoryId WHERE $where");
  $stmt->bind_param($types, ...$params);
} else {
  $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products p WHERE $where");
}
$stmt->execute();
$totalProducts = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$totalPages = max(1, (int) ceil($totalProducts / $perPage));

// Products
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes = $types . 'ii';
$stmt = $conn->prepare(
  "SELECT p.productId, p.name, p.price, p.stock, p.imageUrl, c.name AS categoryName
     FROM products p
     LEFT JOIN categories c ON p.categoryId = c.categoryId
     WHERE $where
     ORDER BY $orderBy
     LIMIT ? OFFSET ?"
);
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$productsResult = $stmt->get_result();
?>

<div class="pagetitle">
  <h1>All Products</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">All Products</li>
    </ol>
  </nav>
</div>

<section class="section">

  <!-- Filter bar -->
  <div class="d-flex align-items-center mb-3 px-3 py-2 bg-white rounded-3 border"
    style="border-color:#d4e8da !important; gap:0;">
    <div class="category-pills flex-grow-1" id="categoryPills" style="min-width:0;">
      <a href="allProducts?sort=<?= urlencode($sort) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
        class="pill <?= $priceRange === '' ? 'active' : '' ?>">All</a>
      <a href="allProducts?price=under50&sort=<?= urlencode($sort) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
        class="pill <?= $priceRange === 'under50' ? 'active' : '' ?>">Under ₱50</a>
      <a href="allProducts?price=50to200&sort=<?= urlencode($sort) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
        class="pill <?= $priceRange === '50to200' ? 'active' : '' ?>">₱50 – ₱200</a>
      <a href="allProducts?price=200to500&sort=<?= urlencode($sort) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
        class="pill <?= $priceRange === '200to500' ? 'active' : '' ?>">₱200 – ₱500</a>
      <a href="allProducts?price=above500&sort=<?= urlencode($sort) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
        class="pill <?= $priceRange === 'above500' ? 'active' : '' ?>">₱500+</a>
    </div>
    <div class="flex-shrink-0 mx-3" style="width:1px; height:28px; background:#d4e8da;"></div>
    <form method="GET" action="" class="d-flex align-items-center gap-2 flex-shrink-0">
      <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
      <?php if ($priceRange): ?><input type="hidden" name="price"
          value="<?= htmlspecialchars($priceRange) ?>"><?php endif; ?>
      <span class="text-muted small fw-semibold text-nowrap">Sort by</span>
      <select name="sort" class="form-select form-select-sm border-0 fw-bold" style="width:auto;"
        onchange="this.form.submit()">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low → High</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A → Z</option>
      </select>
      <?php if ($search || $priceRange || $sort !== 'newest'): ?>
        <a href="allProducts" class="btn btn-sm btn-outline-secondary" title="Clear filters"><i
            class="bi bi-x-circle"></i></a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Login notice -->
  <div class="alert d-flex align-items-center gap-2 mb-3"
    style="background:#fff8e1; border:1px solid #ffe082; color:#5d4037; border-radius:8px;">
    <i class="bi bi-info-circle-fill" style="color:#f59e0b; font-size:18px;"></i>
    <span>
      You're browsing as a guest.
      <a href="/WST-QuickCart/public/login" class="fw-bold" style="color:#005d21;">Login</a> or
      <a href="/WST-QuickCart/public/registration" class="fw-bold" style="color:#005d21;">create an account</a>
      to add items to your cart.
    </span>
  </div>

  <div class="results-meta mb-3">
    Showing <strong><?= $totalProducts ?></strong> product<?= $totalProducts !== 1 ? 's' : '' ?>
    <?php if ($search): ?> for "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
  </div>

  <?php if ($productsResult->num_rows === 0): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state">
          <i class="bi bi-bag-x"></i>
          <h5>No products found</h5>
          <p>Try a different filter or search term.</p>
          <a href="allProducts" class="btn btn-primary mt-2">Clear Filters</a>
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
                <span class="badge bg-warning text-dark product-badge">Low Stock</span>
              <?php endif; ?>
              <a href="productDisplay.php?id=<?= (int) $product['productId'] ?>">
                <img src="../uploads/products/<?= htmlspecialchars($product['imageUrl'] ?? '') ?>"
                  alt="<?= htmlspecialchars($product['name']) ?>"
                  onerror="this.src='user/assets/img/product-placeholder.png'">
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
              <div class="product-stock"><i class="bi bi-box-seam me-1"></i><?= $product['stock'] ?> in stock</div>
              <div class="d-flex gap-2 mt-2 align-items-stretch">
                <a href="#" onclick="showLoginPrompt(); return false;"
                  class="btn-add-cart w-100 text-center text-decoration-none" title="Login to add to cart">
                  <i class="bi bi-cart-plus me-1"></i> Add to Cart
                </a>
                <a href="#" onclick="showLoginPrompt(); return false;" class="btn btn-sm btn-light rounded-circle" style="width:36px;height:36px;padding:0;border:1px solid #dee2e6;
                          flex-shrink:0;display:flex;align-items:center;justify-content:center;"
                  title="Login to wishlist">
                  <i class="bi bi-heart text-muted" style="font-size:13px;"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav class="mt-4 d-flex justify-content-center">
        <ul class="pagination">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link"
              href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&price=<?= urlencode($priceRange) ?>&sort=<?= urlencode($sort) ?>"><i
                class="bi bi-chevron-left"></i></a>
          </li>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link"
                href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&price=<?= urlencode($priceRange) ?>&sort=<?= urlencode($sort) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link"
              href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&price=<?= urlencode($priceRange) ?>&sort=<?= urlencode($sort) ?>"><i
                class="bi bi-chevron-right"></i></a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>