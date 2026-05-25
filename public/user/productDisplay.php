<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$userId = $_SESSION['authUser']['userId'] ?? 0;

// Get product ID
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$productId) {
    header('Location: allProducts');
    exit;
}

// Fetch product with category + supplier
$stmt = $conn->prepare(
    "SELECT p.*, c.name AS categoryName, c.categoryId AS catId, c.parentId,
            pc.name AS parentCategoryName, pc.categoryId AS parentCatId,
            s.name AS supplierName
     FROM products p
     LEFT JOIN categories c ON p.categoryId = c.categoryId
     LEFT JOIN categories pc ON c.parentId = pc.categoryId
     LEFT JOIN suppliers s ON p.supplierId = s.supplierId
     WHERE p.productId = ? AND p.status = 'active'"
);
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: allProducts');
    exit;
}

// Fetch reviews
$stmtRev = $conn->prepare(
    "SELECT r.*, u.firstName, u.lastName
     FROM reviews r
     JOIN users u ON r.userId = u.userId
     WHERE r.productId = ?
     ORDER BY r.createdAt DESC"
);
$stmtRev->bind_param('i', $productId);
$stmtRev->execute();
$reviews = $stmtRev->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtRev->close();

// Rating stats
$totalReviews = count($reviews);
$avgRating = 0;
$ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
if ($totalReviews > 0) {
    $sum = 0;
    foreach ($reviews as $r) {
        $sum += $r['rating'];
        $ratingCounts[(int) $r['rating']]++;
    }
    $avgRating = $sum / $totalReviews;
}

// Check if user already wishlisted this product
$wStmt = $conn->prepare("SELECT wishlistId FROM wishlist WHERE userId = ? AND productId = ?");
$wStmt->bind_param('ii', $userId, $productId);
$wStmt->execute();
$inWishlist = (bool) $wStmt->get_result()->fetch_assoc();
$wStmt->close();

// Check if user has a delivered order containing this product (eligible to review)
$eligibleStmt = $conn->prepare(
    "SELECT o.orderId FROM orders o
     JOIN orderitems oi ON o.orderId = oi.orderId
     WHERE o.userId = ? AND oi.productId = ? AND o.status = 'delivered'
     LIMIT 1"
);
$eligibleStmt->bind_param('ii', $userId, $productId);
$eligibleStmt->execute();
$eligibleOrder = $eligibleStmt->get_result()->fetch_assoc();
$eligibleStmt->close();

// Check if user already reviewed this product
$userReview = null;
if ($eligibleOrder) {
    $rCheckStmt = $conn->prepare(
        "SELECT * FROM reviews WHERE userId = ? AND productId = ? LIMIT 1"
    );
    $rCheckStmt->bind_param('ii', $userId, $productId);
    $rCheckStmt->execute();
    $userReview = $rCheckStmt->get_result()->fetch_assoc();
    $rCheckStmt->close();
}

// Related products (same category, exclude current)
$stmtRel = $conn->prepare(
    "SELECT p.productId, p.name, p.price, p.stock, p.imageUrl, c.name AS categoryName
     FROM products p
     LEFT JOIN categories c ON p.categoryId = c.categoryId
     WHERE p.status = 'active' AND p.stock > 0
       AND p.categoryId = ? AND p.productId != ?
     ORDER BY RAND()
     LIMIT 4"
);
$stmtRel->bind_param('ii', $product['categoryId'], $productId);
$stmtRel->execute();
$relatedProducts = $stmtRel->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtRel->close();

// Wishlist IDs for related products heart state
$wishlistIds = [];
$wAllStmt = $conn->prepare("SELECT productId FROM wishlist WHERE userId = ?");
$wAllStmt->bind_param("i", $userId);
$wAllStmt->execute();
foreach ($wAllStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $w) {
    $wishlistIds[] = $w['productId'];
}
$wAllStmt->close();

// Stock status helper
$stockStatus = 'In Stock';
$stockClass = 'text-success';
if ($product['stock'] == 0) {
    $stockStatus = 'Out of Stock';
    $stockClass = 'text-danger';
} elseif ($product['stock'] <= 5) {
    $stockStatus = 'Low Stock (' . $product['stock'] . ' left)';
    $stockClass = 'text-warning';
}

// Flash messages
$successMsg = $_SESSION['success'] ?? '';
$errorMsg   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="pagetitle">
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <?php if ($product['parentCatId']): ?>
                <li class="breadcrumb-item"><?= htmlspecialchars($product['parentCategoryName']) ?></li>
            <?php endif; ?>
            <?php if ($product['catId']): ?>
                <li class="breadcrumb-item"><?= htmlspecialchars($product['categoryName']) ?></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>
</div>

<section class="section">

    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <?= htmlspecialchars($successMsg) ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= htmlspecialchars($errorMsg) ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ======== Main Product Card ======== -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="row g-4">

                <!-- Product Image -->
                <div class="col-md-5 col-lg-4">
                    <div class="product-display-img-wrap">
                        <?php if ($product['stock'] > 0 && $product['stock'] <= 5): ?>
                            <span class="badge bg-warning text-dark product-display-badge">Low Stock</span>
                        <?php elseif ($product['stock'] == 0): ?>
                            <span class="badge bg-danger product-display-badge">Out of Stock</span>
                        <?php endif; ?>
                        <img src="../uploads/products/<?= htmlspecialchars($product['imageUrl'] ?? '') ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="product-display-img"
                             onerror="this.src='assets/img/product-placeholder.png'">
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-md-7 col-lg-8">

                    <!-- Category Badge -->
                    <div class="mb-2">
                        <span class="badge rounded-pill"
                              style="background:#e8f5e9; color:#2e7d32; font-size:12px;">
                            <?= htmlspecialchars($product['categoryName'] ?? 'General') ?>
                        </span>
                    </div>

                    <h2 class="fw-bold mb-1" style="color:#1a1a2e; font-size:1.6rem;">
                        <?= htmlspecialchars($product['name']) ?>
                    </h2>

                    <!-- Rating Summary -->
                    <?php if ($totalReviews > 0): ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= round($avgRating) ? '-fill' : '' ?>"
                                       style="color:#f59e0b; font-size:15px;"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="fw-semibold" style="color:#f59e0b;"><?= number_format($avgRating, 1) ?></span>
                            <span class="text-muted small">(<?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?>)</span>
                            <a href="#" onclick="document.getElementById('reviews-tab').click(); return false;"
                               class="small" style="color:#005d21;">See all</a>
                        </div>
                    <?php else: ?>
                        <div class="mb-2">
                            <span class="text-muted small"><i class="bi bi-star me-1"></i>No reviews yet</span>
                        </div>
                    <?php endif; ?>

                    <!-- Price -->
                    <div class="mb-3">
                        <span class="product-display-price">&#8369;<?= number_format($product['price'], 2) ?></span>
                    </div>

                    <!-- Stock -->
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam <?= $stockClass ?>"></i>
                        <span class="fw-semibold small <?= $stockClass ?>"><?= $stockStatus ?></span>
                        <?php if ($product['stock'] > 5): ?>
                            <span class="text-muted small">(<?= $product['stock'] ?> units available)</span>
                        <?php endif; ?>
                    </div>

                    <!-- Supplier -->
                    <?php if ($product['supplierName']): ?>
                        <div class="mb-3 small text-muted">
                            <i class="bi bi-building me-1"></i>
                            Supplied by <strong><?= htmlspecialchars($product['supplierName']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <hr style="border-color:#e8f5e9;">

                    <!-- Quantity + Add to Cart -->
                    <?php if ($product['stock'] > 0): ?>
                        <form action="../../app/controllers/cartController.php" method="POST"
                              class="d-flex align-items-center gap-3 flex-wrap mb-3">
                            <input type="hidden" name="productId" value="<?= (int) $productId ?>">

                            <!-- Quantity Stepper -->
                            <div class="d-flex align-items-center border rounded-3 overflow-hidden"
                                 style="border-color:#d4e8da !important;">
                                <button type="button" class="btn btn-light border-0 px-3 py-2 qty-btn"
                                        data-action="dec" style="font-size:18px; line-height:1;">&#8722;</button>
                                <input type="number" name="quantity" id="qtyInput" value="1"
                                       min="1" max="<?= $product['stock'] ?>"
                                       class="form-control border-0 text-center fw-bold"
                                       style="width:60px; box-shadow:none; font-size:15px;">
                                <button type="button" class="btn btn-light border-0 px-3 py-2 qty-btn"
                                        data-action="inc" style="font-size:18px; line-height:1;">+</button>
                            </div>

                            <button type="submit" name="addToCart"
                                    class="btn btn-primary btn-lg px-4"
                                    style="background:#005d21; border-color:#005d21; border-radius:10px;">
                                <i class="bi bi-cart-plus me-2"></i>Add to Cart
                            </button>

                            <a href="checkout?buyNow=<?= $productId ?>"
                               class="btn btn-success btn-lg px-4"
                               style="border-radius:10px; background:#1b5e20; border-color:#1b5e20;">
                                <i class="bi bi-lightning-fill me-2"></i>Buy Now
                            </a>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            This product is currently out of stock.
                        </div>
                    <?php endif; ?>

                    <!-- Wishlist + Share -->
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <form action="../../app/controllers/wishlistController.php" method="POST">
                            <input type="hidden" name="productId" value="<?= $productId ?>">
                            <button type="submit"
                                    name="<?= $inWishlist ? 'removeFromWishlist' : 'addToWishlist' ?>"
                                    class="btn btn-sm d-flex align-items-center gap-2"
                                    style="border:1px solid <?= $inWishlist ? '#e53935' : '#dee2e6' ?>;
                                           color:<?= $inWishlist ? '#e53935' : '#666' ?>;
                                           border-radius:8px; padding:6px 14px;">
                                <i class="bi <?= $inWishlist ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                <span class="small fw-semibold">
                                    <?= $inWishlist ? 'Wishlisted' : 'Add to Wishlist' ?>
                                </span>
                            </button>
                        </form>

                        <button class="btn btn-sm btn-light d-flex align-items-center gap-2"
                                style="border-radius:8px; border:1px solid #dee2e6;"
                                onclick="navigator.clipboard.writeText(window.location.href);
                                         this.innerHTML='<i class=\'bi bi-check2\' style=\'color:#005d21\'></i><span class=\'small fw-semibold\' style=\'color:#005d21\'>Copied!</span>';
                                         setTimeout(()=>this.innerHTML='<i class=\'bi bi-link-45deg\'></i><span class=\'small fw-semibold\'>Copy Link</span>',2000);">
                            <i class="bi bi-link-45deg"></i>
                            <span class="small fw-semibold">Copy Link</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ======== Tabs ======== -->
    <div class="card mb-4">
        <div class="card-body">
            <ul class="nav nav-tabs" id="productTabs" role="tablist"
                style="border-bottom:2px solid #e8f5e9;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="desc-tab"
                            data-bs-toggle="tab" data-bs-target="#desc" type="button" role="tab">
                        <i class="bi bi-file-text me-1"></i>Description
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="details-tab"
                            data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                        <i class="bi bi-info-circle me-1"></i>Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="reviews-tab"
                            data-bs-toggle="tab" data-bs-target="#reviews-section" type="button" role="tab">
                        <i class="bi bi-star me-1"></i>Reviews
                        <?php if ($totalReviews > 0): ?>
                            <span class="badge rounded-pill ms-1"
                                  style="background:#e8f5e9; color:#2e7d32; font-size:11px;">
                                <?= $totalReviews ?>
                            </span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-4" id="productTabsContent">

                <!-- Description -->
                <div class="tab-pane fade show active" id="desc" role="tabpanel">
                    <?php if ($product['description']): ?>
                        <p class="text-muted lh-lg" style="font-size:15px;">
                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-muted fst-italic">No description available.</p>
                    <?php endif; ?>
                </div>

                <!-- Details -->
                <div class="tab-pane fade" id="details" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <td class="text-muted fw-semibold" style="width:40%;">Product ID</td>
                                        <td><?= $product['productId'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Category</td>
                                        <td><?= htmlspecialchars($product['categoryName'] ?? '&#8212;') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Supplier</td>
                                        <td><?= htmlspecialchars($product['supplierName'] ?? '&#8212;') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Availability</td>
                                        <td>
                                            <span class="badge rounded-pill <?= $product['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $product['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Stock</td>
                                        <td><?= $product['stock'] ?> units</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Price</td>
                                        <td class="fw-bold" style="color:#005d21;">
                                            &#8369;<?= number_format($product['price'], 2) ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Added</td>
                                        <td><?= date('F j, Y', strtotime($product['createdAt'])) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Reviews -->
                <div class="tab-pane fade" id="reviews-section" role="tabpanel">

                    <!-- Write a Review (eligible + not yet reviewed) -->
                    <?php if ($eligibleOrder && !$userReview): ?>
                        <div class="card mb-4" style="border:1.5px solid #d4e8da; border-radius:12px;">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3" style="color:#005d21;">
                                    <i class="bi bi-pencil-square me-2"></i>Write a Review
                                </h6>
                                <form action="../../app/controllers/reviewController.php" method="POST"
                                      enctype="multipart/form-data">
                                    <input type="hidden" name="productId" value="<?= $productId ?>">
                                    <input type="hidden" name="orderId"   value="<?= $eligibleOrder['orderId'] ?>">

                                    <!-- Star Picker -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">
                                            Your Rating <span class="text-danger">*</span>
                                        </label>
                                        <div class="d-flex gap-1" id="starPicker">
                                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                                <i class="bi bi-star star-pick" data-val="<?= $s ?>"
                                                   style="font-size:28px; color:#ddd; cursor:pointer; transition:color .15s;"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" name="rating" id="ratingInput" value="">
                                        <div class="invalid-feedback" id="ratingError" style="display:none;">
                                            Please select a star rating.
                                        </div>
                                    </div>

                                    <!-- Comment -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Comment (optional)</label>
                                        <textarea name="comment" rows="3" class="form-control"
                                                  style="border-color:#d4e8da; border-radius:8px;"
                                                  placeholder="Share your experience with this product&#8230;"></textarea>
                                    </div>

                                    <!-- Photo -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Photo (optional)</label>
                                        <input type="file" name="reviewImage" accept="image/*"
                                               class="form-control" style="border-color:#d4e8da; border-radius:8px;">
                                    </div>

                                    <button type="submit" name="submitReview" id="submitReviewBtn"
                                            class="btn btn-primary"
                                            style="background:#005d21; border-color:#005d21; border-radius:8px;">
                                        <i class="bi bi-send me-1"></i> Submit Review
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php elseif ($userReview): ?>
                        <div class="alert d-flex align-items-center gap-2 mb-4"
                             style="background:#e8f5e9; border:1px solid #a5d6a7; color:#1b5e20; border-radius:8px;">
                            <i class="bi bi-check-circle-fill"></i>
                            You have already reviewed this product. Thank you for your feedback!
                        </div>
                    <?php endif; ?>

                    <!-- Rating Overview -->
                    <?php if ($totalReviews > 0): ?>
                        <div class="row g-4 mb-4 align-items-center">
                            <div class="col-auto text-center">
                                <div class="display-4 fw-bold" style="color:#1a1a2e;">
                                    <?= number_format($avgRating, 1) ?>
                                </div>
                                <div class="mb-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= round($avgRating) ? '-fill' : '' ?>"
                                           style="color:#f59e0b;"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-muted small">
                                    <?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?>
                                </div>
                            </div>
                            <div class="col">
                                <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
                                    <?php $pct = $totalReviews > 0 ? ($ratingCounts[$star] / $totalReviews) * 100 : 0; ?>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="text-muted small" style="width:14px;"><?= $star ?></span>
                                        <i class="bi bi-star-fill" style="color:#f59e0b; font-size:11px;"></i>
                                        <div class="progress flex-grow-1" style="height:8px; border-radius:10px;">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width:<?= $pct ?>%; background:#f59e0b; border-radius:10px;"></div>
                                        </div>
                                        <span class="text-muted small" style="width:24px;"><?= $ratingCounts[$star] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <hr style="border-color:#e8f5e9;">

                        <!-- Review List -->
                        <?php foreach ($reviews as $review): ?>
                            <div class="py-3" style="border-bottom:1px solid #f0f0f0;">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:42px; height:42px; background:#e8f5e9;
                                                font-weight:700; color:#005d21; font-size:15px;">
                                        <?= strtoupper(substr($review['firstName'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <span class="fw-semibold" style="color:#1a1a2e;">
                                                <?= htmlspecialchars($review['firstName'] . ' ' . $review['lastName']) ?>
                                            </span>
                                            <div>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"
                                                       style="color:#f59e0b; font-size:12px;"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="badge rounded-pill"
                                                  style="background:#fff3e0; color:#e65100; font-size:11px;">
                                                <?= $review['rating'] ?>/5
                                            </span>
                                            <span class="text-muted small ms-auto">
                                                <?= date('M j, Y', strtotime($review['createdAt'])) ?>
                                            </span>
                                        </div>
                                        <?php if ($review['comment']): ?>
                                            <p class="mb-1 text-muted" style="font-size:14px; line-height:1.6;">
                                                <?= nl2br(htmlspecialchars($review['comment'])) ?>
                                            </p>
                                        <?php else: ?>
                                            <p class="mb-1 text-muted fst-italic small">No written review.</p>
                                        <?php endif; ?>
                                        <?php if ($review['imageUrl']): ?>
                                            <div class="mt-2">
                                                <img src="../uploads/reviews/<?= htmlspecialchars($review['imageUrl']) ?>"
                                                     alt="Review photo"
                                                     style="max-width:120px; border-radius:8px;
                                                            border:1px solid #dee2e6; cursor:pointer;"
                                                     onclick="window.open(this.src)">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-square-text" style="font-size:48px; color:#ccc;"></i>
                            <h6 class="mt-3 text-muted">No Reviews Yet</h6>
                            <p class="text-muted small">
                                <?= ($eligibleOrder && !$userReview)
                                    ? "You've purchased this item &#8212; be the first to review it above!"
                                    : "Purchase this product to leave a review." ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Not eligible notice -->
                    <?php if (!$eligibleOrder && !$userReview): ?>
                        <div class="mt-3 p-3 rounded-3"
                             style="background:#f8f9fa; border:1px dashed #dee2e6;">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-bag-check" style="font-size:24px; color:#005d21;"></i>
                                <p class="mb-0 small text-muted">
                                    Only customers who have purchased and received this product can leave a review.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div><!-- end reviews tab -->

            </div>
        </div>
    </div>

    <!-- ======== Related Products ======== -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Related Products <span>| You might also like</span></h5>
                <div class="row g-3">
                    <?php foreach ($relatedProducts as $rel):
                        $relInWishlist = in_array($rel['productId'], $wishlistIds);
                        ?>
                        <div class="col-6 col-md-4 col-xl-3">
                            <div class="product-card">
                                <div class="product-img-wrap">
                                    <?php if ($rel['stock'] <= 5): ?>
                                        <span class="badge bg-warning text-dark product-badge">Low Stock</span>
                                    <?php endif; ?>
                                    <a href="productDisplay.php?id=<?= (int) $rel['productId'] ?>">
                                        <img src="../uploads/products/<?= htmlspecialchars($rel['imageUrl'] ?? '') ?>"
                                             alt="<?= htmlspecialchars($rel['name']) ?>"
                                             onerror="this.src='assets/img/product-placeholder.png'">
                                    </a>
                                </div>
                                <div class="product-body">
                                    <div class="product-category">
                                        <?= htmlspecialchars($rel['categoryName'] ?? 'General') ?>
                                    </div>
                                    <div class="product-name">
                                        <a href="productDisplay.php?id=<?= (int) $rel['productId'] ?>"
                                           style="text-decoration:none; color:inherit;">
                                            <?= htmlspecialchars($rel['name']) ?>
                                        </a>
                                    </div>
                                    <div class="product-price">&#8369;<?= number_format($rel['price'], 2) ?></div>
                                    <div class="product-stock">
                                        <i class="bi bi-box-seam me-1"></i><?= $rel['stock'] ?> in stock
                                    </div>
                                    <div class="d-flex gap-2 mt-2 align-items-stretch">
                                        <form action="../../app/controllers/cartController.php" method="POST"
                                              class="flex-grow-1">
                                            <input type="hidden" name="productId" value="<?= (int) $rel['productId'] ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" name="addToCart" class="btn-add-cart w-100">
                                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                                            </button>
                                        </form>
                                        <form action="../../app/controllers/wishlistController.php" method="POST"
                                              class="d-flex align-items-center">
                                            <input type="hidden" name="productId" value="<?= $rel['productId'] ?>">
                                            <button type="submit"
                                                    name="<?= $relInWishlist ? 'removeFromWishlist' : 'addToWishlist' ?>"
                                                    class="btn btn-sm btn-light rounded-circle wishlist-btn <?= $relInWishlist ? 'wishlisted' : '' ?>"
                                                    style="width:36px;height:36px;padding:0;border:1px solid #dee2e6;flex-shrink:0;"
                                                    title="<?= $relInWishlist ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                                                <i class="bi <?= $relInWishlist ? 'bi-heart-fill text-danger' : 'bi-heart text-muted' ?>"
                                                   style="font-size:13px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</section>

<style>
.product-display-img-wrap {
    position: relative;
    background: #f8fdf9;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #e8f5e9;
    aspect-ratio: 1/1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.product-display-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 16px;
    transition: transform 0.3s ease;
}
.product-display-img:hover { transform: scale(1.04); }
.product-display-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 2;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
}
.product-display-price {
    font-size: 2rem;
    font-weight: 800;
    color: #005d21;
    letter-spacing: -0.5px;
}
.nav-tabs .nav-link.active {
    color: #005d21 !important;
    border-bottom: 2px solid #005d21 !important;
    background: transparent;
}
.nav-tabs .nav-link:hover { color: #005d21 !important; }
.star-pick { transition: color .15s; }
.star-pick:hover { color: #f59e0b !important; }
</style>

<script>
// Quantity stepper
document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById('qtyInput');
        const max   = parseInt(input.max) || 99;
        let val     = parseInt(input.value) || 1;
        val = btn.dataset.action === 'inc' ? Math.min(val + 1, max) : Math.max(val - 1, 1);
        input.value = val;
    });
});

// Star rating picker
(function () {
    const stars  = document.querySelectorAll('.star-pick');
    const hidden = document.getElementById('ratingInput');
    if (!stars.length) return;

    function paint(n) {
        stars.forEach((s, i) => {
            s.classList.toggle('bi-star-fill', i < n);
            s.classList.toggle('bi-star',      i >= n);
            s.style.color = i < n ? '#f59e0b' : '#ddd';
        });
    }

    stars.forEach((s, idx) => {
        s.addEventListener('mouseenter', () => paint(idx + 1));
        s.addEventListener('mouseleave', () => paint(parseInt(hidden.value) || 0));
        s.addEventListener('click', () => {
            hidden.value = idx + 1;
            paint(idx + 1);
            document.getElementById('ratingError').style.display = 'none';
        });
    });

    // Validate star rating before submit
    const form = document.getElementById('submitReviewBtn');
    if (form) {
        form.closest('form').addEventListener('submit', function (e) {
            if (!hidden.value) {
                e.preventDefault();
                document.getElementById('ratingError').style.display = 'block';
                document.getElementById('starPicker').scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
})();
</script>

<?php include('includes/footer.php'); ?>