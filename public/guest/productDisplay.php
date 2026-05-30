<?php
include_once("../../app/middleware/guest.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

// Get product ID
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$productId) {
    header('Location: allProducts');
    exit;
}

// Fetch product with category info
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

// Fetch reviews WITH admin replies
$stmtRev = $conn->prepare(
    "SELECT r.*, u.firstName, u.lastName,
            rr.reply AS adminReply, rr.createdAt AS replyCreatedAt,
            au.firstName AS adminFirstName, au.lastName AS adminLastName
     FROM reviews r
     JOIN users u ON r.userId = u.userId
     LEFT JOIN review_replies rr ON rr.reviewId = r.reviewId
     LEFT JOIN users au ON rr.adminId = au.userId
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
        $ratingCounts[(int)$r['rating']]++;
    }
    $avgRating = $sum / $totalReviews;
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
?>

<div class="pagetitle">
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <?php if ($product['parentCatId']): ?>
                <li class="breadcrumb-item">
                    <a href="<?= strtolower(str_replace(' ', '', $product['parentCategoryName'])) ?>">
                        <?= htmlspecialchars($product['parentCategoryName']) ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($product['catId']): ?>
                <li class="breadcrumb-item">
                    <a href="<?= htmlspecialchars($product['slug'] ?? '#') ?>">
                        <?= htmlspecialchars($product['categoryName']) ?>
                    </a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>
</div>

<section class="section">

    <!-- Main Product Card -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="row g-4">

                <!-- Product Image -->
                <div class="col-md-5 col-lg-4">
                    <div class="product-display-img-wrap">
                        <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                            <span class="badge bg-warning text-dark product-display-badge">Low Stock</span>
                        <?php elseif ($product['stock'] == 0): ?>
                            <span class="badge bg-danger product-display-badge">Out of Stock</span>
                        <?php endif; ?>
                        <img id="mainProductImg"
                             src="../uploads/products/<?= htmlspecialchars($product['imageUrl'] ?? '') ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="product-display-img"
                             onerror="this.src='user/assets/img/product-placeholder.png'">
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-md-7 col-lg-8">
                    <!-- Category Badge -->
                    <div class="mb-2">
                        <span class="badge rounded-pill" style="background:#e8f5e9; color:#2e7d32; font-size:12px;">
                            <?= htmlspecialchars($product['categoryName'] ?? 'General') ?>
                        </span>
                    </div>

                    <h2 class="fw-bold mb-1" style="color:#1a1a2e; font-size:1.6rem;">
                        <?= htmlspecialchars($product['name']) ?>
                    </h2>

                    <!-- Rating Summary -->
                    <?php if ($totalReviews > 0): ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="stars-display">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= round($avgRating) ? '-fill' : ($i - $avgRating < 1 ? '-half' : '') ?>"
                                       style="color:#f59e0b; font-size:15px;"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="fw-semibold" style="color:#f59e0b;"><?= number_format($avgRating, 1) ?></span>
                            <span class="text-muted small">(<?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?>)</span>
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
                            <span class="text-muted small">(<?= $product['stock'] ?> in stock)</span>
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

                    <!-- Login Notice -->
                    <div class="alert d-flex align-items-start gap-2 mb-3"
                         style="background:#fff8e1; border:1px solid #ffe082; color:#5d4037; border-radius:8px;">
                        <i class="bi bi-info-circle-fill mt-1" style="color:#f59e0b; font-size:16px; flex-shrink:0;"></i>
                        <span class="small">
                            <a href="/WST-QuickCart/public/login" class="fw-bold" style="color:#005d21;">Login</a>
                            or
                            <a href="/WST-QuickCart/public/registration" class="fw-bold" style="color:#005d21;">create an account</a>
                            to add this item to your cart or wishlist.
                        </span>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="d-flex gap-2 flex-wrap">
                        <button onclick="showLoginPrompt()" class="btn btn-primary btn-lg px-4"
                                style="background:#005d21; border-color:#005d21; border-radius:10px;"
                                <?= $product['stock'] == 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-cart-plus me-2"></i>Add to Cart
                        </button>
                        <button onclick="showLoginPrompt()" class="btn btn-outline-secondary btn-lg"
                                style="border-radius:10px; width:48px;" title="Add to Wishlist">
                            <i class="bi bi-heart"></i>
                        </button>
                        <a href="/WST-QuickCart/public/login" class="btn btn-success btn-lg px-4"
                           style="border-radius:10px; background:#1b5e20; border-color:#1b5e20;">
                            <i class="bi bi-lightning-fill me-2"></i>Buy Now
                        </a>
                    </div>

                    <!-- Share -->
                    <div class="mt-3 d-flex align-items-center gap-2">
                        <span class="text-muted small">Share:</span>
                        <button class="btn btn-sm btn-light rounded-circle" style="width:32px;height:32px;padding:0;"
                                onclick="navigator.clipboard.writeText(window.location.href); this.innerHTML='<i class=\'bi bi-check\' style=\'color:#005d21\'></i>'; setTimeout(()=>this.innerHTML='<i class=\'bi bi-link-45deg\'></i>',2000);"
                                title="Copy link">
                            <i class="bi bi-link-45deg"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Description & Details Tabs -->
    <div class="card mb-4">
        <div class="card-body">
            <ul class="nav nav-tabs" id="productTabs" role="tablist" style="border-bottom:2px solid #e8f5e9;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="desc-tab" data-bs-toggle="tab"
                            data-bs-target="#desc" type="button" role="tab"
                            style="color:#005d21; border:none; border-bottom:2px solid transparent;">
                        <i class="bi bi-file-text me-1"></i>Description
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="details-tab" data-bs-toggle="tab"
                            data-bs-target="#details" type="button" role="tab"
                            style="color:#666; border:none;">
                        <i class="bi bi-info-circle me-1"></i>Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="reviews-tab" data-bs-toggle="tab"
                            data-bs-target="#reviews-section" type="button" role="tab"
                            style="color:#666; border:none;">
                        <i class="bi bi-star me-1"></i>Reviews
                        <?php if ($totalReviews > 0): ?>
                            <span class="badge rounded-pill ms-1"
                                  style="background:#e8f5e9; color:#2e7d32; font-size:11px;"><?= $totalReviews ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-4" id="productTabsContent">

                <!-- Description Tab -->
                <div class="tab-pane fade show active" id="desc" role="tabpanel">
                    <?php if ($product['description']): ?>
                        <p class="text-muted lh-lg" style="font-size:15px;">
                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-muted fst-italic">No description available for this product.</p>
                    <?php endif; ?>
                </div>

                <!-- Details Tab -->
                <div class="tab-pane fade" id="details" role="tabpanel">
                    <div class="row g-3">
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
                                        <td class="text-muted fw-semibold">Status</td>
                                        <td>
                                            <span class="badge rounded-pill"
                                                  style="background:#e8f5e9; color:#2e7d32;">
                                                <?= ucfirst($product['status']) ?>
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

                <!-- Reviews Tab -->
                <div class="tab-pane fade" id="reviews-section" role="tabpanel">

                    <?php if ($totalReviews > 0): ?>
                        <!-- Rating Overview -->
                        <div class="row g-4 mb-4 align-items-center">
                            <div class="col-auto text-center">
                                <div class="display-4 fw-bold" style="color:#1a1a2e;"><?= number_format($avgRating, 1) ?></div>
                                <div class="mb-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= round($avgRating) ? '-fill' : '' ?>"
                                           style="color:#f59e0b;"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-muted small"><?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?></div>
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

                        <!-- Individual Reviews -->
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item py-3" style="border-bottom:1px solid #f0f0f0;">
                                    <div class="d-flex align-items-start gap-3">
                                        <!-- Avatar -->
                                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width:42px; height:42px; background:#e8f5e9; font-weight:700; color:#005d21; font-size:15px;">
                                            <?= strtoupper(substr($review['firstName'], 0, 1)) ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <!-- Reviewer name + stars + date -->
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

                                            <!-- Comment -->
                                            <?php if ($review['comment']): ?>
                                                <p class="mb-1 text-muted" style="font-size:14px; line-height:1.6;">
                                                    <?= nl2br(htmlspecialchars($review['comment'])) ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="mb-1 text-muted fst-italic small">No written review.</p>
                                            <?php endif; ?>

                                            <!-- Review photo -->
                                            <?php if ($review['imageUrl']): ?>
                                                <div class="mt-2">
                                                    <img src="../uploads/reviews/<?= htmlspecialchars($review['imageUrl']) ?>"
                                                         alt="Review photo"
                                                         style="max-width:120px; border-radius:8px; border:1px solid #dee2e6; cursor:pointer;"
                                                         onclick="window.open(this.src)">
                                                </div>
                                            <?php endif; ?>

                                            <!-- Admin Reply -->
                                            <?php if (!empty($review['adminReply'])): ?>
                                                <div class="mt-3 p-3 rounded-3 d-flex gap-2"
                                                     style="background:#f0f7f2; border-left:3px solid #005d21;">
                                                    <i class="bi bi-shield-check-fill mt-1 flex-shrink-0"
                                                       style="color:#005d21; font-size:15px;"></i>
                                                    <div>
                                                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                            <span class="fw-semibold small" style="color:#005d21;">
                                                                <?= htmlspecialchars($review['adminFirstName'] . ' ' . $review['adminLastName']) ?>
                                                            </span>
                                                            <span class="badge rounded-pill"
                                                                  style="background:#e8f5e9; color:#2e7d32; font-size:10px;">
                                                                Store Admin
                                                            </span>
                                                            <span class="text-muted small ms-auto">
                                                                <?= date('M j, Y', strtotime($review['replyCreatedAt'])) ?>
                                                            </span>
                                                        </div>
                                                        <p class="mb-0 small" style="color:#2d4a36; line-height:1.6;">
                                                            <?= nl2br(htmlspecialchars($review['adminReply'])) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-square-text" style="font-size:48px; color:#ccc;"></i>
                            <h6 class="mt-3 text-muted">No Reviews Yet</h6>
                            <p class="text-muted small">Be the first to review this product!</p>
                            <a href="/WST-QuickCart/public/login" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login to Review
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Write a review CTA (guest) -->
                    <div class="mt-4 p-3 rounded-3" style="background:#f8f9fa; border:1px dashed #dee2e6;">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-pencil-square" style="font-size:24px; color:#005d21;"></i>
                            <div>
                                <p class="mb-1 fw-semibold small">Have you tried this product?</p>
                                <p class="mb-0 text-muted small">
                                    <a href="/WST-QuickCart/public/login" style="color:#005d21;" class="fw-bold">Login</a>
                                    to share your experience and help others decide.
                                </p>
                            </div>
                        </div>
                    </div>

                </div><!-- end reviews tab -->

            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Related Products <span>| You might also like</span></h5>
                <div class="row g-3">
                    <?php foreach ($relatedProducts as $rel): ?>
                        <div class="col-6 col-md-4 col-xl-3">
                            <div class="product-card">
                                <div class="product-img-wrap">
                                    <?php if ($rel['stock'] <= 5): ?>
                                        <span class="badge bg-warning text-dark product-badge">Low Stock</span>
                                    <?php endif; ?>
                                    <a href="productDisplay.php?id=<?= (int) $rel['productId'] ?>">
                                        <img src="uploads/products/<?= htmlspecialchars($rel['imageUrl'] ?? '') ?>"
                                             alt="<?= htmlspecialchars($rel['name']) ?>"
                                             onerror="this.src='user/assets/img/product-placeholder.png'">
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
                                    <div class="d-flex gap-2 mt-2 align-items-stretch">
                                        <a href="#" onclick="showLoginPrompt(); return false;"
                                           class="btn-add-cart w-100 text-center text-decoration-none"
                                           title="Login to add to cart">
                                            <i class="bi bi-cart-plus me-1"></i> Add to Cart
                                        </a>
                                        <a href="#" onclick="showLoginPrompt(); return false;"
                                           class="btn btn-sm btn-light rounded-circle"
                                           style="width:36px;height:36px;padding:0;border:1px solid #dee2e6;
                                                  flex-shrink:0;display:flex;align-items:center;justify-content:center;"
                                           title="Login to add to wishlist">
                                            <i class="bi bi-heart text-muted" style="font-size:13px;"></i>
                                        </a>
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

<?php include('includes/footer.php'); ?>
