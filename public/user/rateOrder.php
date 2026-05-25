<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;
$orderId = intval($_GET['id'] ?? 0);

// ── Fetch order (must belong to user & be delivered/refunded) ─────────────
$stmt = $conn->prepare("
    SELECT o.orderId, o.orderNumber, o.status, o.orderedAt,
           s.receivedAt
    FROM   orders  o
    LEFT JOIN shipping s ON o.orderId = s.orderId
    WHERE  o.orderId = ? AND o.userId = ?
    LIMIT 1
");
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Order not found.'];
    header("Location: orders");
    exit;
}

if (!in_array($order['status'], ['delivered', 'refunded'])) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'You can only rate delivered or refunded orders.'];
    header("Location: orderView?id=$orderId");
    exit;
}

// ── 7-day guard (blocks both page load AND POST submission) ───────────────
$windowAnchor = !empty($order['receivedAt']) ? $order['receivedAt'] : $order['orderedAt'];
$daysSince    = (int)(new DateTime())->diff(new DateTime($windowAnchor))->days;
if ($daysSince > 7) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'The 7-day window for rating has expired.'];
    header("Location: orderView?id=$orderId");
    exit;
}

// ── Fetch ratable items (skip already-reviewed products) ─────────────────
$stmt = $conn->prepare("
    SELECT oi.orderItemId, oi.productId, oi.productName, oi.quantity,
           pr.imageUrl,
           r.reviewId, r.rating, r.comment, r.imageUrl AS reviewImage
    FROM   orderitems oi
    LEFT JOIN products pr ON oi.productId = pr.productId
    LEFT JOIN reviews   r ON r.productId = oi.productId
                          AND r.userId   = ?
                          AND r.orderId  = ?
    WHERE  oi.orderId = ?
      AND  oi.productId IS NOT NULL
");
$stmt->bind_param('iii', $userId, $orderId, $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$items) {
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'No products to rate for this order.'];
    header("Location: orderView?id=$orderId");
    exit;
}

// ── Handle POST ───────────────────────────────────────────────────────────
$errors = [];
$success = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitRatings'])) {
    $ratings = $_POST['rating'] ?? [];
    $comments = $_POST['comment'] ?? [];

    foreach ($items as $item) {
        $pid = $item['productId'];
        $rating = intval($ratings[$pid] ?? 0);
        $comment = trim($comments[$pid] ?? '');

        if ($rating < 1 || $rating > 5)
            continue; // skip unrated

        // Handle per-product image upload
        $reviewImage = null;
        $fileKey = 'review_img_' . $pid;
        if (!empty($_FILES[$fileKey]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed) && $_FILES[$fileKey]['size'] <= 5 * 1024 * 1024) {
                $uploadDir = '../uploads/reviews/';
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0755, true);
                $reviewImage = 'review_' . $pid . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $reviewImage);
            }
        }

        // Upsert review — only overwrite imageUrl if a new one was uploaded
        $ups = $conn->prepare("
            INSERT INTO reviews (userId, productId, orderId, rating, comment, imageUrl)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                rating   = VALUES(rating),
                comment  = VALUES(comment),
                imageUrl = IF(VALUES(imageUrl) IS NOT NULL, VALUES(imageUrl), imageUrl)
        ");
        $ups->bind_param('iiiiss', $userId, $pid, $orderId, $rating, $comment, $reviewImage);
        $ups->execute();
        $ups->close();
        $success++;

        // Notify admin of new review
        include_once("../../app/helpers/notifications.php");
        $reviewId = $conn->insert_id;
        $authUser = $_SESSION['authUser'] ?? [];
        $customerName = trim(
            ($authUser['firstName'] ?? $authUser['fullName'] ?? 'Customer')
            . ' ' .
            ($authUser['lastName'] ?? '')
        );
        notifyAdminNewReview($conn, $reviewId, $item['productName'], $customerName, $rating);
    }

    if ($success > 0) {
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Thanks for your review! $success product(s) rated."];
    } else {
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Please select a star rating for at least one product.'];
    }
    header("Location: orderView?id=$orderId");
    exit;
}

$allRated = !empty($items) && count(array_filter($items, fn($i) => $i['reviewId'])) === count($items);

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
    <h1>Rate Your Order</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item"><a href="orders">My Orders</a></li>
            <li class="breadcrumb-item"><a href="orderView?id=<?= $orderId ?>">Order Details</a></li>
            <li class="breadcrumb-item active">Rate Products</li>
        </ol>
    </nav>
</div>

<section class="section">

    <?php if ($allRated): ?>
        <!-- All rated already -->
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <span style="
                    display:inline-flex;align-items:center;justify-content:center;
                    width:72px;height:72px;border-radius:50%;
                    background:#d1f0db;font-size:2rem;">
                        <i class="bi bi-check-circle-fill text-success"></i>
                    </span>
                </div>
                <h5 class="fw-bold mb-1">All Products Reviewed!</h5>
                <p class="text-muted mb-4">You've already rated all products in this order. Thank you for your feedback!</p>
                <a href="orderView?id=<?= $orderId ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Order
                </a>
            </div>
        </div>

    <?php else: ?>

        <!-- Intro banner -->
        <div class="rate-hero mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rate-hero-icon">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <div class="rate-hero-title">How was your order?</div>
                    <div class="rate-hero-sub">
                        Order <strong><?= htmlspecialchars($order['orderNumber']) ?></strong> &mdash;
                        tap the stars to rate each item
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" id="rateForm" enctype="multipart/form-data" action="rateOrder?id=<?= $orderId ?>">

            <?php foreach ($items as $idx => $item):
                $alreadyRated = !empty($item['reviewId']);
                $existingRating = intval($item['rating'] ?? 0);
                $existingComment = $item['comment'] ?? '';
                $pid = $item['productId'];
                ?>
                <div class="card rate-card <?= $alreadyRated ? 'already-rated' : '' ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">

                            <!-- Product image -->
                            <div class="flex-shrink-0">
                                <?php if (!empty($item['imageUrl'])): ?>
                                    <img src="../uploads/products/<?= htmlspecialchars($item['imageUrl']) ?>"
                                        onerror="this.src='assets/img/product-placeholder.png'" class="rate-img rounded">
                                <?php else: ?>
                                    <div class="rate-img rounded bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image text-muted fs-4"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Review form -->
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <div class="fw-bold rate-product-name"><?= htmlspecialchars($item['productName']) ?></div>
                                    <?php if ($alreadyRated): ?>
                                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>Reviewed</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Star rating -->
                                <div class="star-rating mb-3" data-pid="<?= $pid ?>">
                                    <?php for ($s = 5; $s >= 1; $s--): ?>
                                        <input type="radio" id="star_<?= $pid ?>_<?= $s ?>" name="rating[<?= $pid ?>]"
                                            value="<?= $s ?>" <?= ($existingRating === $s) ? 'checked' : '' ?>>
                                        <label for="star_<?= $pid ?>_<?= $s ?>" title="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                    <?php endfor; ?>
                                    <span class="star-hint ms-2"></span>
                                </div>

                                <!-- Comment -->
                                <div class="mb-3">
                                    <textarea name="comment[<?= $pid ?>]" rows="2" class="form-control"
                                        placeholder="Share your thoughts about this product (optional)…"><?= htmlspecialchars($existingComment) ?></textarea>
                                </div>

                                <!-- Review photo upload -->
                                <div>
                                    <?php if (!empty($item['reviewImage'])): ?>
                                        <div class="mb-2">
                                            <img src="../uploads/reviews/<?= htmlspecialchars($item['reviewImage']) ?>"
                                                onerror="this.style.display='none'" class="rounded border"
                                                style="max-height:100px;object-fit:cover;">
                                            <div class="form-text">Current photo</div>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="review_img_<?= $pid ?>"
                                        class="form-control form-control-sm review-img-input"
                                        accept="image/jpeg,image/png,image/webp" data-preview="review_preview_<?= $pid ?>">
                                    <div id="review_preview_<?= $pid ?>" class="mt-1 d-none">
                                        <img src="#" alt="Preview" class="rounded border"
                                            style="max-height:80px;object-fit:cover;">
                                    </div>
                                    <div class="form-text">
                                        <?= $alreadyRated ? 'Upload a new photo to replace the existing one (optional, max 5MB)' : 'Attach a photo (optional, max 5MB)' ?>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="d-flex gap-2 mt-1 mb-4">
                <button type="submit" name="submitRatings" class="btn btn-success px-4">
                    <i class="bi bi-send me-1"></i> Submit Reviews
                </button>
                <a href="orderView?id=<?= $orderId ?>" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>

        </form>
    <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>