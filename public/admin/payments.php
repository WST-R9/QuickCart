<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

$paymentsQuery = "SELECT 
    p.paymentId,
    p.orderId,
    o.orderNumber,
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
                <th>Status</th>
                <th>Amount</th>
                <th>Reference</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($paymentsResult)): ?>
                <?php
                $badge = "bg-secondary";
                if ($row['status'] == "paid") $badge = "bg-success";
                elseif ($row['status'] == "pending") $badge = "bg-warning";
                elseif ($row['status'] == "failed") $badge = "bg-danger";
                elseif ($row['status'] == "refunded") $badge = "bg-dark";
                ?>

                <tr>
                  <td><?= htmlspecialchars($row['orderNumber']) ?></td>
                  <td><?= htmlspecialchars($row['customerName']) ?></td>
                  <td><?= strtoupper($row['method']) ?></td>
                  <td><span class="badge <?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                  <td>₱<?= number_format($row['amount'], 2) ?></td>
                  <td><?= $row['referenceNumber'] ? htmlspecialchars($row['referenceNumber']) : "N/A" ?></td>
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