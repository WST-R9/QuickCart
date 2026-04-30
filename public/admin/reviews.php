<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// DELETE REVIEW
if (isset($_GET['delete'])) {
    $reviewId = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM reviews WHERE reviewId = $reviewId");

    echo "<script>alert('Review deleted successfully!'); window.location.href='reviews.php';</script>";
    exit;
}

// FETCH REVIEWS
$reviewsQuery = "SELECT 
    r.reviewId,
    CONCAT(u.firstName, ' ', u.lastName) AS customerName,
    p.name AS productName,
    r.rating,
    r.comment,
    r.createdAt
FROM reviews r
JOIN users u ON r.userId = u.userId
JOIN products p ON r.productId = p.productId
ORDER BY r.createdAt DESC";

$reviewsResult = mysqli_query($conn, $reviewsQuery);
?>

<div class="pagetitle">
  <h1>Reviews</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Reviews</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-12">

      <div class="card recent-sales overflow-auto">
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
                  <td><?= htmlspecialchars($row['customerName']) ?></td>
                  <td><?= htmlspecialchars($row['productName']) ?></td>

                  <td>
                    <span class="badge bg-warning text-dark">
                      <?= $row['rating'] ?> ★
                    </span>
                  </td>

                  <td><?= $row['comment'] ? htmlspecialchars($row['comment']) : "No comment" ?></td>
                  <td><?= date("M d, Y", strtotime($row['createdAt'])) ?></td>

                  <td>
                    <a href="reviews.php?delete=<?= $row['reviewId'] ?>"
                       onclick="return confirm('Delete this review?');"
                       class="btn btn-sm btn-danger">
                      <i class="bi bi-trash"></i>
                    </a>
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