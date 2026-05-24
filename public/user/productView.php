<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$productId = (int) ($_GET['id'] ?? 0);
$userId    = $_SESSION['authUser']['userId'] ?? 0;

if (!$productId) { header("Location: allProducts"); exit; }

// Fetch product
$stmt = $conn->prepare("
    SELECT p.*, c.name AS categoryName
    FROM products p
    LEFT JOIN categories c ON p.categoryId = c.categoryId
    WHERE p.productId = ? AND p.status = 'active'
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) { $_SESSION['flash']=['type'=>'danger','message'=>'Product not found.']; header("Location: allProducts"); exit; }

// Fetch reviews
$stmt = $conn->prepare("
    SELECT r.*, CONCAT(u.firstName,' ',u.lastName) AS reviewerName
    FROM reviews r
    JOIN users u ON r.userId = u.userId
    WHERE r.productId = ?
    ORDER BY r.createdAt DESC
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$avgRating  = count($reviews) > 0 ? round(array_sum(array_column($reviews,'rating')) / count($reviews), 1) : 0;
$ratingDist = [5=>0,4=>0,3=>0,2=>0,1=>0];
foreach ($reviews as $r) $ratingDist[$r['rating']] = ($ratingDist[$r['rating']] ?? 0) + 1;

// Wishlist check
$inWishlist = false;
if ($userId > 0) {
    $wStmt = $conn->prepare("SELECT wishlistId FROM wishlist WHERE userId=? AND productId=?");
    $wStmt->bind_param("ii", $userId, $productId);
    $wStmt->execute();
    $inWishlist = $wStmt->get_result()->num_rows > 0;
    $wStmt->close();
}

// Related products
$stmt = $conn->prepare("
    SELECT productId, name, price, imageUrl, stock
    FROM products
    WHERE categoryId = ? AND productId != ? AND status='active' AND stock > 0
    LIMIT 4
");
$stmt->bind_param("ii", $product['categoryId'], $productId);
$stmt->execute();
$related = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
  <h1><?=htmlspecialchars($product['name'])?></h1>
  <nav><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index">Home</a></li>
    <li class="breadcrumb-item"><a href="allProducts">Products</a></li>
    <li class="breadcrumb-item active"><?=htmlspecialchars($product['name'])?></li>
  </ol></nav>
</div>

<section class="section">

  <!-- Product Detail -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-4">
        <!-- Image -->
        <div class="col-md-4 text-center">
          <img src="../uploads/products/<?=htmlspecialchars($product['imageUrl']??'')?>"
               alt="<?=htmlspecialchars($product['name'])?>"
               onerror="this.src='assets/img/product-placeholder.png'"
               class="img-fluid rounded" style="max-height:320px;object-fit:contain;">
        </div>
        <!-- Info -->
        <div class="col-md-8">
          <div class="text-muted small mb-1"><?=htmlspecialchars($product['categoryName']??'General')?></div>
          <h3 class="fw-bold mb-2" style="color:#003d16;"><?=htmlspecialchars($product['name'])?></h3>

          <!-- Rating summary -->
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="d-flex">
              <?php for ($s=1;$s<=5;$s++): ?>
                <i class="bi <?=$s<=$avgRating?'bi-star-fill':'($s-0.5<=$avgRating?\'bi-star-half\':\'bi-star\')'?>"
                   style="color:#f59e0b;font-size:14px;"></i>
              <?php endfor; ?>
            </div>
            <span class="fw-bold"><?=$avgRating?></span>
            <span class="text-muted small">(<?=count($reviews)?> review<?=count($reviews)!=1?'s':''?>)</span>
          </div>

          <div class="mb-3">
            <span class="fs-3 fw-bold text-success">₱<?=number_format($product['price'],2)?></span>
          </div>

          <div class="mb-3">
            <?php if ($product['stock'] > 5): ?>
              <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>In Stock (<?=$product['stock']?>)</span>
            <?php elseif ($product['stock'] > 0): ?>
              <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Low Stock (<?=$product['stock']?> left)</span>
            <?php else: ?>
              <span class="badge bg-danger">Out of Stock</span>
            <?php endif; ?>
          </div>

          <?php if (!empty($product['description'])): ?>
            <p class="text-muted mb-4"><?=nl2br(htmlspecialchars($product['description']))?></p>
          <?php endif; ?>

          <!-- Actions -->
          <div class="d-flex gap-2 flex-wrap">
            <?php if ($product['stock'] > 0): ?>
              <form action="../../app/controllers/cartController.php" method="POST">
                <input type="hidden" name="productId" value="<?=$product['productId']?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" name="addToCart" class="btn btn-primary px-4">
                  <i class="bi bi-cart-plus me-1"></i>Add to Cart
                </button>
              </form>
            <?php else: ?>
              <button class="btn btn-secondary px-4" disabled>Out of Stock</button>
            <?php endif; ?>
            <form action="../../app/controllers/wishlistController.php" method="POST">
              <input type="hidden" name="productId" value="<?=$product['productId']?>">
              <button type="submit" name="<?=$inWishlist?'removeFromWishlist':'addToWishlist'?>"
                      class="btn <?=$inWishlist?'btn-danger':'btn-outline-secondary'?> px-3"
                      title="<?=$inWishlist?'Remove from wishlist':'Add to wishlist'?>">
                <i class="bi <?=$inWishlist?'bi-heart-fill':'bi-heart'?>"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Reviews Section -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title"><i class="bi bi-star me-2 text-warning"></i>Customer Reviews</h5>

      <?php if (empty($reviews)): ?>
        <div class="text-center py-4 text-muted">
          <i class="bi bi-star d-block mb-2" style="font-size:32px;"></i>
          <p class="mb-0">No reviews yet. Be the first to review this product!</p>
        </div>
      <?php else: ?>
        <!-- Rating summary bar -->
        <div class="row g-3 mb-4 align-items-center">
          <div class="col-md-3 text-center">
            <div style="font-size:48px;font-weight:700;color:#003d16;"><?=$avgRating?></div>
            <div class="d-flex justify-content-center gap-1 mb-1">
              <?php for($s=1;$s<=5;$s++): ?>
                <i class="bi <?=$s<=$avgRating?'bi-star-fill':'bi-star'?>" style="color:#f59e0b;"></i>
              <?php endfor; ?>
            </div>
            <div class="text-muted small"><?=count($reviews)?> reviews</div>
          </div>
          <div class="col-md-9">
            <?php foreach([5,4,3,2,1] as $star): $cnt=$ratingDist[$star];$pct=count($reviews)>0?round($cnt/count($reviews)*100):0; ?>
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="small text-muted" style="width:14px;"><?=$star?></span>
                <i class="bi bi-star-fill" style="color:#f59e0b;font-size:12px;"></i>
                <div class="flex-grow-1 bg-light rounded-pill" style="height:8px;">
                  <div class="rounded-pill" style="height:8px;width:<?=$pct?>%;background:#f59e0b;"></div>
                </div>
                <span class="small text-muted" style="width:24px;"><?=$cnt?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Review list -->
        <div class="d-flex flex-column gap-3">
          <?php foreach ($reviews as $rev): ?>
            <div class="border rounded-3 p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle d-flex align-items-center justify-content-center"
                       style="width:36px;height:36px;background:#005d21;color:#fff;font-weight:700;font-size:13px;">
                    <?=strtoupper(substr($rev['reviewerName'],0,1))?>
                  </div>
                  <div>
                    <div class="fw-semibold small"><?=htmlspecialchars($rev['reviewerName'])?></div>
                    <div class="d-flex gap-1">
                      <?php for($s=1;$s<=5;$s++): ?>
                        <i class="bi <?=$s<=$rev['rating']?'bi-star-fill':'bi-star'?>" style="color:#f59e0b;font-size:11px;"></i>
                      <?php endfor; ?>
                    </div>
                  </div>
                </div>
                <span class="text-muted small"><?=date('M d, Y',strtotime($rev['createdAt']))?></span>
              </div>
              <?php if ($rev['comment']): ?>
                <p class="mb-2 small"><?=nl2br(htmlspecialchars($rev['comment']))?></p>
              <?php endif; ?>
              <?php if ($rev['imageUrl']): ?>
                <img src="../uploads/reviews/<?=htmlspecialchars($rev['imageUrl'])?>"
                     class="rounded border" style="max-height:100px;object-fit:cover;"
                     onerror="this.style.display='none'">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Related Products -->
  <?php if (!empty($related)): ?>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Related Products</h5>
        <div class="row g-3">
          <?php foreach ($related as $rp): ?>
            <div class="col-6 col-md-3">
              <a href="productView?id=<?=$rp['productId']?>" class="text-decoration-none">
                <div class="product-card">
                  <div class="product-img-wrap">
                    <img src="../uploads/products/<?=htmlspecialchars($rp['imageUrl']??'')?>"
                         alt="<?=htmlspecialchars($rp['name'])?>"
                         onerror="this.src='assets/img/product-placeholder.png'">
                  </div>
                  <div class="product-body">
                    <div class="product-name"><?=htmlspecialchars($rp['name'])?></div>
                    <div class="product-price">₱<?=number_format($rp['price'],2)?></div>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>
