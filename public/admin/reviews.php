<?php
include_once("../../app/middleware/admin.php");
include_once("../../app/config/config.php");

if (isset($_GET['delete'])) {
    $reviewId = intval($_GET['delete']);
    $conn->query("DELETE FROM reviews WHERE reviewId=$reviewId");
    $_SESSION['flash'] = ['type'=>'success','message'=>'Review deleted.'];
    header("Location: reviews"); exit;
}

include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');

$reviewsResult = mysqli_query($conn, "
    SELECT r.reviewId, CONCAT(u.firstName,' ',u.lastName) AS customerName,
           p.name AS productName, r.rating, r.comment, r.imageUrl, r.createdAt
    FROM reviews r
    JOIN users    u ON r.userId    = u.userId
    JOIN products p ON r.productId = p.productId
    ORDER BY r.createdAt DESC
");

// Average per product (for summary)
$avgResult = mysqli_query($conn,"SELECT p.name, ROUND(AVG(r.rating),1) AS avg, COUNT(*) AS cnt FROM reviews r JOIN products p ON r.productId=p.productId GROUP BY r.productId ORDER BY avg DESC LIMIT 5");
?>

<div class="pagetitle">
  <h1>Reviews</h1>
  <nav><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index">Home</a></li>
    <li class="breadcrumb-item active">Reviews</li>
  </ol></nav>
</div>

<section class="section">
<div class="row">

  <!-- Top-rated products -->
  <div class="col-lg-4 mb-3">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title">Top Rated Products</h5>
        <?php while ($row = mysqli_fetch_assoc($avgResult)): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <span class="small"><?=htmlspecialchars($row['name'])?></span>
            <div class="d-flex align-items-center gap-1">
              <?php for($s=1;$s<=5;$s++): ?><i class="bi <?=$s<=$row['avg']?'bi-star-fill':'bi-star'?>" style="color:#f59e0b;font-size:11px;"></i><?php endfor; ?>
              <span class="small fw-bold ms-1"><?=$row['avg']?></span>
              <span class="text-muted small">(<?=$row['cnt']?>)</span>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Customer Reviews</h5>
        <table class="table table-borderless datatable">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Product</th>
              <th>Rating</th>
              <th>Comment</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($reviewsResult)): ?>
              <tr>
                <td><?=htmlspecialchars($row['customerName'])?></td>
                <td><?=htmlspecialchars($row['productName'])?></td>
                <td>
                  <div class="d-flex align-items-center gap-1">
                    <?php for($s=1;$s<=5;$s++): ?><i class="bi <?=$s<=$row['rating']?'bi-star-fill':'bi-star'?>" style="color:#f59e0b;font-size:11px;"></i><?php endfor; ?>
                    <span class="small fw-bold ms-1"><?=$row['rating']?></span>
                  </div>
                </td>
                <td class="text-muted small"><?=$row['comment']?htmlspecialchars(mb_strimwidth($row['comment'],0,50,'…')):'No comment'?></td>
                <td class="text-muted small"><?=date("M d, Y",strtotime($row['createdAt']))?></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="reviewsView?id=<?=$row['reviewId']?>" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i></a>
                    <a href="reviews?delete=<?=$row['reviewId']?>" onclick="return confirm('Delete this review?');" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
</section>

<?php include('./includes/footer.php'); ?>
