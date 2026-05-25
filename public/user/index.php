<?php
include_once("../../app/middleware/user.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;
$fullName = $_SESSION['authUser']['fullName'] ?? 'User';
$firstName = explode(' ', trim($fullName))[0];

// ----------------------------------------
// USER ACCOUNT SUMMARY
// ----------------------------------------

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM orders WHERE userId = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalOrders = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM orders WHERE userId = ? AND status = 'pending'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$pendingOrders = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare(
  "SELECT IFNULL(SUM(p.amount), 0) AS totalSpent
     FROM payments p
     JOIN orders o ON p.orderId = o.orderId
     WHERE o.userId = ? AND p.status = 'paid'"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalSpent = (float) $stmt->get_result()->fetch_assoc()['totalSpent'];
$stmt->close();

$stmt = $conn->prepare("SELECT IFNULL(SUM(quantity), 0) AS cartCount FROM cart WHERE userId = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartCount = (int) $stmt->get_result()->fetch_assoc()['cartCount'];
$stmt->close();

// ----------------------------------------
// RECENT ORDERS (last 5)
// ----------------------------------------
$stmt = $conn->prepare(
  "SELECT orderId, orderNumber, totalAmount, status, orderedAt
     FROM orders
     WHERE userId = ?
     ORDER BY orderedAt DESC
     LIMIT 5"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentOrders = $stmt->get_result();

// ----------------------------------------
// FEATURED PRODUCTS — active, in-stock, newest 8
// ----------------------------------------
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

// ----------------------------------------
// TOP-LEVEL CATEGORIES for filter pills
// ----------------------------------------
$catsResult = mysqli_query(
  $conn,
  "SELECT categoryId, name
     FROM categories
     WHERE parentId IS NULL
     ORDER BY name ASC"
);

// ----------------------------------------
// WISHLIST IDs for heart state
// ----------------------------------------
$wishlistIds = [];
$wStmt = $conn->prepare("SELECT productId FROM wishlist WHERE userId = ?");
$wStmt->bind_param("i", $userId);
$wStmt->execute();
foreach ($wStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $w) {
  $wishlistIds[] = $w['productId'];
}
$wStmt->close();
?>

<div class="pagetitle">
  <h1>My Dashboard</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Dashboard</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row">

    <!-- ========= LEFT COLUMN ========= -->
    <div class="col-lg-8">

      <!-- Hero Banner -->
      <div class="shop-hero">
        <h2>Welcome back, <?= htmlspecialchars($firstName) ?>! 👋</h2>
        <p>Fresh groceries &amp; essentials, delivered fast. What do you need today?</p>
        <a href="allProducts" class="btn btn-light mt-3 fw-bold" style="color:#005d21;">
          <i class="bi bi-shop me-1"></i> Shop Now
        </a>
      </div>

      <!-- Stat Cards -->
      <div class="row g-3 mb-2">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-bag-check"></i></div>
            <div>
              <div class="stat-label">Total Orders</div>
              <div class="stat-value"><?= $totalOrders ?></div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
            <div>
              <div class="stat-label">Pending</div>
              <div class="stat-value"><?= $pendingOrders ?></div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-cash-coin"></i></div>
            <div>
              <div class="stat-label">Total Spent</div>
              <div class="stat-value" style="font-size:16px;">₱<?= number_format($totalSpent, 0) ?></div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-cart3"></i></div>
            <div>
              <div class="stat-label">In Cart</div>
              <div class="stat-value"><?= $cartCount ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Featured Products -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Featured Products <span>| Latest Arrivals</span></h5>

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
              <?php while ($product = mysqli_fetch_assoc($featuredResult)):
                $inWishlist = in_array($product['productId'], $wishlistIds);
                ?>
                <div class="col-6 col-md-4 col-xl-3">
                  <div class="product-card">
                    <div class="product-img-wrap">
                      <?php if ($product['stock'] <= 5): ?>
                        <span class="product-badge bg-warning text-dark">Low Stock</span>
                      <?php endif; ?>
                      <a href="productDisplay.php?id=<?= (int) $product['productId'] ?>">
                        <img src="../uploads/products/<?= htmlspecialchars($product['imageUrl'] ?? '') ?>"
                          alt="<?= htmlspecialchars($product['name']) ?>"
                          onerror="this.src='assets/img/product-placeholder.png'">
                      </a>
                    </div>
                    <div class="product-body">
                      <div class="product-category">
                        <?= htmlspecialchars($product['categoryName'] ?? 'General') ?>
                      </div>
                      <div class="product-name">
                        <a href="productDisplay.php?id=<?= (int) $product['productId'] ?>"
                          style="text-decoration:none; color:inherit;">
                          <?= htmlspecialchars($product['name']) ?>
                        </a>
                      </div>
                      <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                      <div class="d-flex gap-2 mt-2 align-items-stretch">
                        <form action="../../app/controllers/cartController.php" method="POST" class="flex-grow-1">
                          <input type="hidden" name="productId" value="<?= (int) $product['productId'] ?>">
                          <input type="hidden" name="quantity" value="1">
                          <button type="submit" name="addToCart" class="btn-add-cart w-100">
                            Add to Cart
                          </button>
                        </form>
                        <form action="../../app/controllers/wishlistController.php" method="POST"
                          class="d-flex align-items-center">
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
            <?php endif; ?>
          </div>

          <div class="text-center mt-3">
            <a href="allProducts" class="btn btn-outline-primary">
              <i class="bi bi-grid me-1"></i> View All Products
            </a>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Recent Orders <span>| Latest</span></h5>

          <?php if ($recentOrders->num_rows === 0): ?>
            <div class="empty-state">
              <i class="bi bi-bag-x"></i>
              <h5>No orders yet</h5>
              <p>Start shopping and your orders will appear here.</p>
              <a href="allProducts" class="btn btn-primary mt-2">Browse Products</a>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-borderless orders-table align-middle">
                <thead>
                  <tr>
                    <th>Order #</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($order = $recentOrders->fetch_assoc()):
                    $s = $order['status'];
                    $badgeMap = [
                      'pending' => 'bg-warning text-dark',
                      'confirmed' => 'bg-primary',
                      'processing' => 'bg-info text-dark',
                      'shipped' => 'bg-dark',
                      'delivered' => 'bg-success',
                      'cancelled' => 'bg-danger',
                      'refunded' => 'bg-secondary',
                    ];
                    $badge = $badgeMap[$s] ?? 'bg-secondary';
                    ?>
                    <tr>
                      <td class="fw-bold"><?= htmlspecialchars($order['orderNumber']) ?></td>
                      <td>₱<?= number_format($order['totalAmount'], 2) ?></td>
                      <td><span class="badge <?= $badge ?>"><?= ucfirst($s) ?></span></td>
                      <td class="text-muted small">
                        <?= date("M d, Y", strtotime($order['orderedAt'])) ?>
                      </td>
                      <td>
                        <a href="orderView?id=<?= (int) $order['orderId'] ?>"
                          class="btn btn-sm btn-outline-primary">View</a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
            <div class="text-end">
              <a href="orders" class="btn btn-sm btn-outline-primary">View All Orders</a>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- End Left Column -->

    <!-- ========= RIGHT COLUMN ========= -->
    <div class="col-lg-4">

      <!-- Account Summary Card -->
      <?php
      $displayName = htmlspecialchars($fullName);
      $nameParts = explode(' ', trim($fullName));
      $displayInitials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1)) ?: 'U';
      $displayUsername = htmlspecialchars($_SESSION['authUser']['username'] ?? '');
      ?>
      <div class="card">
        <div class="card-body text-center pt-4">
          <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:72px; height:72px; background:#005d21; font-size:26px; font-weight:700;
                      color:#fff; font-family:'Nunito',sans-serif;">
            <?= $displayInitials ?>
          </div>
          <h5 class="fw-bold mb-0" style="color:#003d16;"><?= $displayName ?></h5>
          <p class="text-muted small mb-3">@<?= $displayUsername ?></p>
          <a href="accounts" class="btn btn-outline-primary btn-sm w-100">
            <i class="bi bi-person-gear me-1"></i> Edit Profile
          </a>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Quick Actions</h5>
          <div class="d-grid gap-2">
            <a href="allProducts" class="btn btn-primary">
              <i class="bi bi-shop me-1"></i> Browse Products
            </a>
            <a href="cart" class="btn btn-outline-primary">
              <i class="bi bi-cart3 me-1"></i> View Cart
              <?php if ($cartCount > 0): ?>
                <span class="badge bg-danger ms-1"><?= $cartCount ?></span>
              <?php endif; ?>
            </a>
            <a href="orders" class="btn btn-outline-primary">
              <i class="bi bi-bag-check me-1"></i> My Orders
            </a>
            <a href="track" class="btn btn-outline-primary">
              <i class="bi bi-truck me-1"></i> Track an Order
            </a>
            <a href="tickets" class="btn btn-outline-primary">
              <i class="bi bi-headset me-1"></i> Get Support
            </a>
          </div>
        </div>
      </div>

      <!-- Order Status Legend -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Order Statuses</h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="small">Pending</span>
              <span class="badge bg-warning text-dark">Awaiting confirmation</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="small">Confirmed</span>
              <span class="badge bg-primary">Accepted</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="small">Processing</span>
              <span class="badge bg-info text-dark">Being packed</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="small">Shipped</span>
              <span class="badge bg-dark">On the way</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="small">Delivered</span>
              <span class="badge bg-success">Completed</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="small">Cancelled</span>
              <span class="badge bg-danger">Cancelled</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="small">Refunded</span>
              <span class="badge bg-secondary">Refunded</span>
            </li>
          </ul>
        </div>
      </div>

    </div><!-- End Right Column -->

  </div>
</section>

<?php include('./includes/footer.php'); ?>