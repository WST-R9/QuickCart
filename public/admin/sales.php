<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// TOTAL SALES
$totalSalesQuery = "SELECT IFNULL(SUM(amount),0) AS totalSales
                    FROM payments
                    WHERE status='paid'";
$totalSalesResult = mysqli_query($conn, $totalSalesQuery);
$totalSales = mysqli_fetch_assoc($totalSalesResult)['totalSales'] ?? 0;

// TOTAL PAID ORDERS
$totalPaidOrdersQuery = "SELECT COUNT(*) AS totalPaidOrders
                         FROM payments
                         WHERE status='paid'";
$totalPaidOrdersResult = mysqli_query($conn, $totalPaidOrdersQuery);
$totalPaidOrders = mysqli_fetch_assoc($totalPaidOrdersResult)['totalPaidOrders'] ?? 0;

// MONTHLY SALES REPORT
$monthlySalesQuery = "SELECT 
    YEAR(createdAt) AS year,
    MONTH(createdAt) AS month,
    COUNT(*) AS totalTransactions,
    SUM(amount) AS totalRevenue
FROM payments
WHERE status='paid'
GROUP BY YEAR(createdAt), MONTH(createdAt)
ORDER BY year DESC, month DESC";

$monthlySalesResult = mysqli_query($conn, $monthlySalesQuery);
?>

<div class="pagetitle">
  <h1>Sales</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Sales</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">

    <!-- TOTAL SALES -->
    <div class="col-lg-6">
      <div class="card info-card revenue-card">
        <div class="card-body">
          <h5 class="card-title">Total Revenue <span>| All Time</span></h5>
          <h6>₱<?= number_format($totalSales, 2) ?></h6>
        </div>
      </div>
    </div>

    <!-- TOTAL PAID TRANSACTIONS -->
    <div class="col-lg-6">
      <div class="card info-card sales-card">
        <div class="card-body">
          <h5 class="card-title">Paid Transactions <span>| All Time</span></h5>
          <h6><?= $totalPaidOrders ?></h6>
        </div>
      </div>
    </div>

    <!-- MONTHLY SALES TABLE -->
    <div class="col-lg-12">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Monthly Sales Summary</h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Month</th>
                <th>Year</th>
                <th>Total Transactions</th>
                <th>Total Revenue</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($monthlySalesResult)): ?>
                <tr>
                  <td><?= date("F", mktime(0, 0, 0, $row['month'], 1)) ?></td>
                  <td><?= $row['year'] ?></td>
                  <td><?= $row['totalTransactions'] ?></td>
                  <td>₱<?= number_format($row['totalRevenue'], 2) ?></td>
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