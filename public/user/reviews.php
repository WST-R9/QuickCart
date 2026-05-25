<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;

// --- Handle Edit (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editReview'])) {
    $reviewId = (int)($_POST['reviewId'] ?? 0);
    $rating   = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment  = trim($_POST['comment'] ?? '');

    // Verify ownership + fetch review date for 7-day check
    $stmt = $conn->prepare("SELECT reviewId, imageUrl, createdAt FROM reviews WHERE reviewId = ? AND userId = ?");
    $stmt->bind_param('ii', $reviewId, $userId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        $_SESSION['reviewMsg'] = ['type' => 'danger', 'text' => 'Review not found.'];
        header('Location: reviews');
        exit;
    }

    // Server-side 7-day guard
    $reviewedAt  = new DateTime($existing['createdAt']);
    $now         = new DateTime();
    $daysDiff    = (int)$now->diff($reviewedAt)->days;

    if ($daysDiff > 7) {
        $_SESSION['reviewMsg'] = ['type' => 'danger', 'text' => 'Reviews can only be edited within 7 days of submission.'];
        header('Location: reviews');
        exit;
    }

    // Handle image upload
    $newImageUrl = null;
    if (!empty($_FILES['review_image']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['review_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed) && $_FILES['review_image']['size'] <= 5 * 1024 * 1024) {
            $uploadDir = '../uploads/reviews/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newImageUrl = 'review_edit_' . $reviewId . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['review_image']['tmp_name'], $uploadDir . $newImageUrl);
        }
    }

    $stmt = $conn->prepare("
        UPDATE reviews
        SET rating   = ?,
            comment  = ?,
            imageUrl = IF(? IS NOT NULL, ?, imageUrl)
        WHERE reviewId = ? AND userId = ?
    ");
    $stmt->bind_param('isssii', $rating, $comment, $newImageUrl, $newImageUrl, $reviewId, $userId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['reviewMsg'] = ['type' => 'success', 'text' => 'Review updated successfully.'];
    header('Location: reviews');
    exit;
}

// --- Fetch reviews ---
$stmt = $conn->prepare("
    SELECT r.reviewId, r.rating, r.comment, r.imageUrl, r.createdAt,
           COALESCE(oi.productName, p.name) AS productName,
           p.imageUrl   AS productImage,
           o.orderNumber,
           o.orderId
    FROM   reviews r
    JOIN   orders       o  ON r.orderId   = o.orderId
    LEFT JOIN orderitems oi ON oi.orderId  = o.orderId AND oi.productId = r.productId
    LEFT JOIN products   p  ON r.productId = p.productId
    WHERE  r.userId = ?
    ORDER  BY r.createdAt DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Compute editability per review ---
$now = new DateTime();
foreach ($reviews as &$r) {
    $reviewedAt    = new DateTime($r['createdAt']);
    $daysDiff      = (int)$now->diff($reviewedAt)->days;
    $r['canEdit']  = $daysDiff <= 7;
    $r['daysLeft'] = max(0, 7 - $daysDiff);
}
unset($r);

$flashMsg = $_SESSION['reviewMsg'] ?? null;
unset($_SESSION['reviewMsg']);

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
  <h1>My Reviews</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">My Reviews</li>
    </ol>
  </nav>
</div>

<section class="section">

  <?php if ($flashMsg): ?>
    <div class="alert alert-<?= $flashMsg['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
      <i class="bi bi-<?= $flashMsg['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
      <?= htmlspecialchars($flashMsg['text']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($reviews)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="bi bi-star text-muted" style="font-size:2rem;"></i>
        <p class="text-muted mt-3">You haven't reviewed any products yet.</p>
        <a href="orders" class="btn btn-success">View My Orders</a>
      </div>
    </div>
  <?php else: ?>

    <div class="card">
      <div class="card-body pt-3">
        <h6 class="card-title"><?= count($reviews) ?> Review<?= count($reviews) !== 1 ? 's' : '' ?></h6>

        <?php foreach ($reviews as $r): ?>
          <div class="d-flex gap-3 py-3 border-bottom align-items-start">

            <img src="../uploads/products/<?= htmlspecialchars($r['productImage'] ?? '') ?>"
                 onerror="this.src='assets/img/product-placeholder.png'"
                 class="rounded flex-shrink-0"
                 style="width:60px;height:60px;object-fit:cover;">

            <div class="flex-grow-1 min-width-0">
              <div class="fw-semibold text-truncate">
                <?= htmlspecialchars($r['productName'] ?? 'Unknown Product') ?>
              </div>
              <div class="text-muted small mb-1">
                Order: <a href="orderView?id=<?= $r['orderId'] ?>" class="text-success">
                  <?= htmlspecialchars($r['orderNumber']) ?>
                </a>
              </div>
              <div class="text-warning mb-1" style="font-size:15px;">
                <?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?>
                <span class="text-muted small ms-1"><?= $r['rating'] ?>/5</span>
              </div>
              <?php if ($r['comment']): ?>
                <p class="mb-1 small text-truncate" style="max-width:480px;">
                  <?= htmlspecialchars($r['comment']) ?>
                </p>
              <?php else: ?>
                <p class="mb-1 small text-muted fst-italic">No comment left.</p>
              <?php endif; ?>
              <?php if ($r['imageUrl']): ?>
                <img src="../uploads/reviews/<?= htmlspecialchars($r['imageUrl']) ?>"
                     class="rounded mt-1"
                     style="width:48px;height:48px;object-fit:cover;cursor:pointer;"
                     onclick="openImagePreview('../uploads/reviews/<?= htmlspecialchars($r['imageUrl']) ?>')">
              <?php endif; ?>
              <div class="d-flex align-items-center gap-2 mt-1">
                <span class="text-muted" style="font-size:11px;">
                  Reviewed on <?= date('M d, Y', strtotime($r['createdAt'])) ?>
                </span>
                <?php if ($r['canEdit']): ?>
                  <span class="badge rounded-pill"
                        style="font-size:10px;background:#d1f0db;color:#005d21;">
                    <?= $r['daysLeft'] === 0 ? 'Last day to edit' : $r['daysLeft'] . 'd left to edit' ?>
                  </span>
                <?php else: ?>
                  <span class="badge rounded-pill"
                        style="font-size:10px;background:#f0f0f0;color:#888;">
                    Edit window closed
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Actions -->
            <div class="d-flex flex-column gap-2 flex-shrink-0">
              <button class="btn btn-sm btn-outline-secondary"
                      onclick="openReviewViewModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">
                <i class="bi bi-eye me-1"></i>View
              </button>

              <?php if ($r['canEdit']): ?>
                <button class="btn btn-sm btn-outline-success"
                        onclick="openReviewEditModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">
                  <i class="bi bi-pencil me-1"></i>Edit
                </button>
              <?php else: ?>
                <button class="btn btn-sm btn-outline-secondary"
                        disabled
                        data-bs-toggle="tooltip"
                        title="Edit window has closed (7-day limit)">
                  <i class="bi bi-lock me-1"></i>Edit
                </button>
              <?php endif; ?>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    </div>

  <?php endif; ?>
</section>

<!-- ===== VIEW MODAL ===== -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-star me-2 text-warning"></i>Review Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex gap-3 mb-3">
          <img id="viewProductImg" src="" class="rounded"
               style="width:64px;height:64px;object-fit:cover;">
          <div>
            <div class="fw-semibold" id="viewProductName"></div>
            <div class="text-muted small" id="viewOrderNumber"></div>
            <div class="text-warning mt-1" id="viewStars" style="font-size:18px;"></div>
          </div>
        </div>
        <div class="mb-2">
          <span class="text-muted small fw-semibold">Comment</span>
          <p class="mb-0 mt-1" id="viewComment" style="white-space:pre-wrap;"></p>
        </div>
        <div id="viewImageWrap" class="mt-3 d-none">
          <span class="text-muted small fw-semibold">Review Photo</span><br>
          <img id="viewReviewImg" src="" class="rounded mt-1"
               style="max-width:100%;max-height:220px;object-fit:contain;cursor:pointer;"
               onclick="openImagePreview(this.src)">
        </div>
        <div class="text-muted mt-3" style="font-size:11px;" id="viewDate"></div>

        <!-- Edit window notice shown inside view modal -->
        <div id="viewEditNotice" class="mt-2 d-none">
          <span class="badge rounded-pill"
                style="font-size:11px;background:#f0f0f0;color:#888;">
            <i class="bi bi-lock me-1"></i>Edit window closed
          </span>
        </div>
      </div>
      <div class="modal-footer">
        <!-- Hidden when canEdit is false -->
        <button class="btn btn-success btn-sm d-none" id="viewToEditBtn">
          <i class="bi bi-pencil me-1"></i>Edit This Review
        </button>
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== EDIT MODAL ===== -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-pencil me-2 text-success"></i>Edit Review
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="reviews" enctype="multipart/form-data">
        <input type="hidden" name="editReview" value="1">
        <input type="hidden" name="reviewId" id="editReviewId">
        <div class="modal-body">

          <!-- Product info (read-only) -->
          <div class="d-flex gap-3 mb-3 p-2 rounded" style="background:#f8f9fa;">
            <img id="editProductImg" src="" class="rounded flex-shrink-0"
                 style="width:52px;height:52px;object-fit:cover;">
            <div>
              <div class="fw-semibold small" id="editProductName"></div>
              <div class="text-muted" style="font-size:11px;" id="editOrderNumber"></div>
            </div>
          </div>

          <!-- Star rating -->
          <div class="mb-3">
            <label class="form-label fw-semibold">
              Rating <span class="text-danger">*</span>
            </label>
            <div class="d-flex gap-1" id="starPicker">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <span class="star-btn" data-val="<?= $s ?>"
                      style="font-size:28px;cursor:pointer;color:#ccc;transition:color .15s;">★</span>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="editRating" value="5">
          </div>

          <!-- Comment -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Comment</label>
            <textarea name="comment" id="editComment" class="form-control" rows="4"
                      placeholder="Share your experience…" maxlength="1000"></textarea>
            <div class="text-end text-muted mt-1" style="font-size:11px;">
              <span id="commentCharCount">0</span>/1000
            </div>
          </div>

          <!-- Review photo -->
          <div class="mb-2">
            <label class="form-label fw-semibold">Review Photo</label>

            <!-- Current photo preview (shown if one exists) -->
            <div id="editCurrentPhotoWrap" class="mb-2 d-none">
              <p class="text-muted mb-1" style="font-size:11px;">Current photo:</p>
              <img id="editCurrentPhoto" src="" class="rounded border"
                   style="max-height:100px;object-fit:cover;cursor:pointer;"
                   onclick="openImagePreview(this.src)">
            </div>

            <!-- File input — reuses review-img-input class so DOMContentLoaded listener picks it up -->
            <input type="file"
                   name="review_image"
                   id="editReviewImage"
                   class="form-control form-control-sm review-img-input"
                   accept="image/jpeg,image/png,image/webp"
                   data-preview="editReviewPreview">

            <!-- New photo preview -->
            <div id="editReviewPreview" class="mt-1 d-none">
              <img src="#" alt="Preview" class="rounded border"
                   style="max-height:80px;object-fit:cover;">
            </div>

            <div class="form-text">
              Upload a new photo to replace the existing one (optional, max 5MB).
            </div>
          </div>

          <!-- Days-left notice inside modal -->
          <div id="editDaysNotice" class="mt-1"></div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success btn-sm">
            <i class="bi bi-check-lg me-1"></i>Save Changes
          </button>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== IMAGE PREVIEW MODAL ===== -->
<div class="modal fade" id="imgPreviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body p-0 text-center">
        <img id="imgPreviewSrc" src="" style="max-width:100%;border-radius:8px;">
      </div>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>