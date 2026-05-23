<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Review not found!'); window.location.href='reviews.php';</script>";
    exit;
}

$reviewId = intval($_GET['id']);

// REVIEW INFO
$reviewQuery = "SELECT 
    r.*,
    CONCAT(u.firstName, ' ', u.lastName) AS customerName,
    u.emailAddress,
    u.userId,
    p.name AS productName,
    p.productId,
    p.price,
    p.status AS productStatus
FROM reviews r
JOIN users u ON r.userId = u.userId
JOIN products p ON r.productId = p.productId
WHERE r.reviewId = $reviewId
LIMIT 1";

$reviewResult = mysqli_query($conn, $reviewQuery);

if (mysqli_num_rows($reviewResult) == 0) {
    echo "<script>alert('Review not found!'); window.location.href='reviews.php';</script>";
    exit;
}

$review = mysqli_fetch_assoc($reviewResult);

// LINKED ORDER (if any)
$orderInfo = null;
if ($review['orderId']) {
    $orderQuery = "SELECT orderId, orderNumber, status, totalAmount, orderedAt
                   FROM orders WHERE orderId = {$review['orderId']} LIMIT 1";
    $orderResult = mysqli_query($conn, $orderQuery);
    $orderInfo = mysqli_fetch_assoc($orderResult);
}

// DELETE REVIEW
if (isset($_POST['deleteReview'])) {
    mysqli_query($conn, "DELETE FROM reviews WHERE reviewId = $reviewId");
    $_SESSION['message'] = "Review deleted successfully.";
    $_SESSION['code']    = "success";
    header("Location: reviews.php");
    exit;
}

// Star rendering helper
function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating
            ? '<i class="bi bi-star-fill text-warning"></i>'
            : '<i class="bi bi-star text-muted"></i>';
    }
    return $stars;
}
?>

<div class="pagetitle">
    <h1>Review Details</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item"><a href="reviews">Reviews</a></li>
            <li class="breadcrumb-item active">View Review</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <div class="row">

        <!-- LEFT -->
        <div class="col-lg-8">

            <!-- REVIEW CARD -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Review</h5>

                    <!-- Stars -->
                    <div class="mb-3">
                        <span class="fs-4"><?= renderStars($review['rating']) ?></span>
                        <span class="ms-2 fw-bold fs-5"><?= $review['rating'] ?> / 5</span>
                    </div>

                    <!-- Comment -->
                    <div class="p-3 rounded" style="background:#f4f9f5; border:1px solid #d4e8da;">
                        <?php if (!empty($review['comment'])): ?>
                            <p class="mb-0" style="font-size:15px; line-height:1.7;">
                                "<?= nl2br(htmlspecialchars($review['comment'])) ?>"
                            </p>
                        <?php else: ?>
                            <p class="text-muted mb-0 fst-italic">No comment provided.</p>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted small mt-2">
                        <i class="bi bi-clock me-1"></i>
                        Reviewed on <?= date("M d, Y h:i A", strtotime($review['createdAt'])) ?>
                    </p>

                </div>
            </div>

            <!-- PRODUCT INFO -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Reviewed Product</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Product Name</p>
                            <p class="fw-semibold"><?= htmlspecialchars($review['productName']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Price</p>
                            <p class="fw-semibold">₱<?= number_format($review['price'], 2) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Status</p>
                            <?php
                            $badge = match($review['productStatus']) {
                                'active'       => 'bg-success',
                                'inactive'     => 'bg-dark',
                                'out_of_stock' => 'bg-danger',
                                default        => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $badge ?>">
                                <?= ucfirst(str_replace('_', ' ', $review['productStatus'])) ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <a href="inventory-edit.php?id=<?= $review['productId'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box me-1"></i> View Product
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            <!-- LINKED ORDER (if verified purchase) -->
            <?php if ($orderInfo): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Verified Purchase <span>| Linked Order</span></h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Order Number</p>
                            <p class="fw-semibold">
                                <a href="order-view.php?id=<?= $orderInfo['orderId'] ?>">
                                    <?= htmlspecialchars($orderInfo['orderNumber']) ?>
                                </a>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Order Status</p>
                            <p class="fw-semibold"><?= ucfirst($orderInfo['status']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Total Amount</p>
                            <p class="fw-semibold">₱<?= number_format($orderInfo['totalAmount'], 2) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted small">Ordered At</p>
                            <p class="fw-semibold"><?= date("M d, Y", strtotime($orderInfo['orderedAt'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Purchase Verification</h5>
                    <p class="text-muted mb-0">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        This review is not linked to a specific order.
                    </p>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- RIGHT -->
        <div class="col-lg-4">

            <!-- REVIEWER INFO -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Reviewer</h5>

                    <!-- Avatar -->
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                             style="width:48px;height:48px;background:#005d21;flex-shrink:0;">
                            <span style="color:#fff;font-weight:700;font-size:16px;">
                                <?php
                                $parts = explode(' ', $review['customerName']);
                                echo strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
                                ?>
                            </span>
                        </div>
                        <div>
                            <p class="mb-0 fw-semibold"><?= htmlspecialchars($review['customerName']) ?></p>
                            <p class="mb-0 text-muted small"><?= htmlspecialchars($review['emailAddress']) ?></p>
                        </div>
                    </div>

                    <a href="customersView.php?id=<?= $review['userId'] ?>" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-person me-1"></i> View Customer Profile
                    </a>
                </div>
            </div>

            <!-- RATING SUMMARY -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Rating Summary</h5>

                    <div class="text-center mb-3">
                        <div class="display-4 fw-bold text-success"><?= $review['rating'] ?></div>
                        <div><?= renderStars($review['rating']) ?></div>
                        <p class="text-muted small mt-1">out of 5 stars</p>
                    </div>

                </div>
            </div>

            <!-- DANGER ZONE -->
            <div class="card border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i> Danger Zone
                    </h5>
                    <p class="text-muted small mb-3">
                        Deleting this review is permanent and cannot be undone.
                    </p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this review?');">
                        <button type="submit" name="deleteReview" class="btn btn-danger w-100">
                            <i class="bi bi-trash me-1"></i> Delete Review
                        </button>
                    </form>
                </div>
            </div>

            <div class="d-grid">
                <a href="reviews" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Reviews
                </a>
            </div>

        </div>

    </div>
</section>

<?php include('./includes/footer.php'); ?>