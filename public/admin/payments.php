<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");
include_once("../../app/helpers/badges.php");

$paymentsQuery = "SELECT 
    p.paymentId,
    p.orderId,
    o.orderNumber,
    o.status AS orderStatus,
    CONCAT(u.firstName, ' ', u.lastName) AS customerName,
    p.method,
    p.status,
    p.amount,
    p.referenceNumber,
    p.createdAt
FROM payments p
JOIN orders o ON p.orderId = o.orderId
JOIN users u ON o.userId = u.userId
ORDER BY p.createdAt DESC";

$paymentsResult = mysqli_query($conn, $paymentsQuery);
?>

<div class="pagetitle">
  <h1>Payments</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Payments</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-12">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Payment Transactions</h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Method</th>
                <th>Order Status</th>
                <th>Payment Status</th>
                <th>Amount</th>
                <th>Reference</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($paymentsResult)): ?>
                <?php $isCancelled = in_array($row['orderStatus'], ['cancelled', 'refunded']); ?>
                <tr class="<?= $isCancelled ? 'text-muted' : '' ?>">
                  <td>
                    <a href="paymentsView.php?id=<?= $row['paymentId'] ?>">
                      <?= htmlspecialchars($row['orderNumber']) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($row['customerName']) ?></td>
                  <td><?= strtoupper(str_replace('_', ' ', $row['method'])) ?></td>
                  <td><span class="badge <?= orderBadge($row['orderStatus']) ?>"><?= ucfirst($row['orderStatus']) ?></span></td>
                  <td><span class="badge <?= paymentBadge($row['status']) ?>"><?= ucfirst($row['status']) ?></span></td>
                  <td>₱<?= number_format($row['amount'], 2) ?></td>
                  <td><?= $row['referenceNumber'] ? htmlspecialchars($row['referenceNumber']) : '<span class="text-muted">N/A</span>' ?></td>
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