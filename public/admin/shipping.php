<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");
include_once("../../app/helpers/badges.php");

$shippingQuery = "SELECT 
    s.shippingId,
    s.orderId,
    o.orderNumber,
    o.status AS orderStatus,
    CONCAT(u.firstName, ' ', u.lastName) AS customerName,
    s.courier,
    s.trackingNumber,
    s.status,
    s.city,
    s.province,
    s.createdAt
FROM shipping s
JOIN orders o ON s.orderId = o.orderId
JOIN users u ON o.userId = u.userId
ORDER BY s.createdAt DESC";

$shippingResult = mysqli_query($conn, $shippingQuery);
?>

<div class="pagetitle">
  <h1>Shipping</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Shipping</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-12">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Shipping Records</h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Courier</th>
                <th>Tracking</th>
                <th>Order Status</th>
                <th>Shipping Status</th>
                <th>Location</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($shippingResult)): ?>
                <?php $isCancelled = in_array($row['orderStatus'], ['cancelled', 'refunded']); ?>
                <tr class="<?= $isCancelled ? 'text-muted' : '' ?>">
                  <td>
                    <a href="shippingView.php?id=<?= $row['shippingId'] ?>">
                      <?= htmlspecialchars($row['orderNumber']) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($row['customerName']) ?></td>
                  <td><?= $row['courier'] ? htmlspecialchars($row['courier']) : '<span class="text-muted">N/A</span>' ?></td>
                  <td><?= $row['trackingNumber'] ? htmlspecialchars($row['trackingNumber']) : '<span class="text-muted">N/A</span>' ?></td>
                  <td><span class="badge <?= orderBadge($row['orderStatus']) ?>"><?= ucfirst($row['orderStatus']) ?></span></td>
                  <td><span class="badge <?= shippingBadge($row['status']) ?>"><?= ucfirst(str_replace('_', ' ', $row['status'])) ?></span></td>
                  <td><?= htmlspecialchars($row['city'] . ', ' . $row['province']) ?></td>
                  <td><?= date("M d, Y", strtotime($row['createdAt'])) ?></td>
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