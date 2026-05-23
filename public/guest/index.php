<?php
include_once("../../app/middleware/guest.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// Featured Products
$featuredResult = mysqli_query(
    $conn,
    "SELECT p.productId, p.name, p.price, p.stock, p.imageUrl,
            c.name AS categoryName
     FROM products p
     LEFT JOIN categories c ON p.categoryId = c.categoryId
     WHERE p.status = 'active' AND p.stock > 0
     ORDER BY p.createdAt DESC
     LIMIT 8"
);
?>

<div class="pagetitle">
  <h1>Welcome to QuickCart</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item active">Home</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row">

    <!-- LEFT COLUMN -->
    <div class="col-lg-8">

      <!-- Hero Banner -->
      <div class="shop-hero">
        <h2>Fresh groceries &amp; essentials 🛒</h2>
        <p>Browse our wide selection of products. Login or create an account to start shopping!</p>
        <div class="d-flex gap-2 mt-3 flex-wrap">
          <a href="/WST-QuickCart/public/login"
             class="btn btn-light fw-bold" style="color:#005d21;">
            <i class="bi bi-box-arrow-in-right me-1"></i> Login
          </a>
          <a href="/WST-QuickCart/public/registration"
             class="btn fw-bold" style="background:rgba(255,255,255,0.2); color:#fff; border:1.5px solid #fff;">
            <i class="bi bi-person-plus me-1"></i> Create Account
          </a>
        </div>
      </div>

      <!-- Featured Products -->
      <div class="card mt-2">
        <div class="card-body">
          <h5 class="card-title">Featured Products <span>| Latest Arrivals</span></h5>

          <!-- Login notice -->
          <div class="alert d-flex align-items-center gap-2 mb-3"
               style="background:#fff8e1; border:1px solid #ffe082; color:#5d4037; border-radius:8px;">
            <i class="bi bi-info-circle-fill" style="color:#f59e0b; font-size:18px;"></i>
            <span>
              <a href="/WST-QuickCart/public/login" class="fw-bold" style="color:#005d21;">Login</a>
              or
              <a href="/WST-QuickCart/public/registration" class="fw-bold" style="color:#005d21;">register</a>
              to add items to your cart and checkout.
            </span>
          </div>

          <div class="row g-3">
            <?php if (mysqli_num_rows($featuredResult) === 0): ?>
              <div class="col-12">
                <div class="empty-state">
                  <i class="bi bi-bag-x"></i>
                  <h5>No products yet</h5>
                  <p>Check back soon – we're stocking up!</p>
                </div>
              </div>
            <?php else: ?>
              <?php while ($product = mysqli_fetch_assoc($featuredResult)): ?>
                <div class="col-6 col-md-4 col-xl-3">
                  <div class="product-card">
                    <div class="product-img-wrap">
                      <?php if ($product['stock'] <= 5): ?>
                        <span class="product-badge bg-warning text-dark">Low Stock</span>
                      <?php endif; ?>
                      <img src="../uploads/products/<?= htmlspecialchars($product['imageUrl'] ?? '') ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>"
                        onerror="this.src='../user/assets/img/product-placeholder.png'">
                    </div>
                    <div class="product-body">
                      <div class="product-category">
                        <?= htmlspecialchars($product['categoryName'] ?? 'General') ?>
                      </div>
                      <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                      <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                      <div class="d-flex gap-2 mt-2 align-items-stretch">
                        <!-- Redirect to login instead of adding to cart -->
                        <a href="#" onclick="showLoginPrompt(); return false;"
                           class="btn-add-cart w-100 text-center text-decoration-none"
                           title="Login to add to cart">
                          <i class="bi bi-cart-plus me-1"></i> Add to Cart
                        </a>
                        <a href="#" onclick="showLoginPrompt(); return false;"
                           class="btn btn-sm btn-light rounded-circle"
                           style="width:36px; height:36px; padding:0; border:1px solid #dee2e6;
                                  flex-shrink:0; display:flex; align-items:center; justify-content:center;"
                           title="Login to add to wishlist">
                          <i class="bi bi-heart text-muted" style="font-size:13px;"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php endif; ?>
          </div>

          <div class="text-center mt-3">
            <a href="allProducts" class="btn btn-outline-primary">
              <i class="bi bi-grid me-1"></i> View All Products
            </a>
          </div>
        </div>
      </div>

    </div><!-- End Left Column -->

    <!-- RIGHT COLUMN -->
    <div class="col-lg-4">

      <!-- Login / Register CTA Card -->
      <div class="card text-center">
        <div class="card-body py-4">
          <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
               style="width:72px; height:72px; background:#e8f5e9;">
            <i class="bi bi-person" style="font-size:32px; color:#005d21;"></i>
          </div>
          <h5 class="fw-bold mb-1" style="color:#003d16;">You're browsing as a guest</h5>
          <p class="text-muted small mb-3">Login or create an account to unlock the full QuickCart experience.</p>
          <a href="/WST-QuickCart/public/login" class="btn btn-primary w-100 mb-2">
            <i class="bi bi-box-arrow-in-right me-1"></i> Login
          </a>
          <a href="/WST-QuickCart/public/registration" class="btn btn-outline-primary w-100">
            <i class="bi bi-person-plus me-1"></i> Create Account
          </a>
        </div>
      </div>

      <!-- Why Register Card -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Why create an account?</h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex align-items-center gap-2 px-0">
              <i class="bi bi-cart-check text-success"></i>
              <span class="small">Add items to cart &amp; checkout</span>
            </li>
            <li class="list-group-item d-flex align-items-center gap-2 px-0">
              <i class="bi bi-heart text-danger"></i>
              <span class="small">Save products to your wishlist</span>
            </li>
            <li class="list-group-item d-flex align-items-center gap-2 px-0">
              <i class="bi bi-truck text-primary"></i>
              <span class="small">Track your orders in real-time</span>
            </li>
            <li class="list-group-item d-flex align-items-center gap-2 px-0">
              <i class="bi bi-star text-warning"></i>
              <span class="small">Leave product reviews</span>
            </li>
            <li class="list-group-item d-flex align-items-center gap-2 px-0">
              <i class="bi bi-headset text-info"></i>
              <span class="small">Access customer support</span>
            </li>
          </ul>
        </div>
      </div>

    </div><!-- End Right Column -->

  </div>
</section>

<?php include('./includes/footer.php'); ?>
