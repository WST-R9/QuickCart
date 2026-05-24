<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// TOTAL SALES (All Time)
$totalSalesQuery = "SELECT IFNULL(SUM(amount),0) AS totalSales FROM payments WHERE status='paid'";
$totalSales = mysqli_fetch_assoc(mysqli_query($conn, $totalSalesQuery))['totalSales'] ?? 0;

// TOTAL PAID ORDERS (All Time)
$totalPaidOrdersQuery = "SELECT COUNT(*) AS totalPaidOrders FROM payments WHERE status='paid'";
$totalPaidOrders = mysqli_fetch_assoc(mysqli_query($conn, $totalPaidOrdersQuery))['totalPaidOrders'] ?? 0;

// DAILY SALES (last 30 days)
$dailySalesQuery = "SELECT 
    DATE(createdAt) AS saleDate,
    COUNT(*) AS totalTransactions,
    SUM(amount) AS totalRevenue
FROM payments
WHERE status='paid' AND createdAt >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(createdAt)
ORDER BY saleDate DESC";
$dailySalesResult = mysqli_query($conn, $dailySalesQuery);

// WEEKLY SALES (last 12 weeks)
$weeklySalesQuery = "SELECT 
    YEAR(createdAt) AS year,
    WEEK(createdAt, 1) AS week,
    MIN(DATE(createdAt)) AS weekStart,
    MAX(DATE(createdAt)) AS weekEnd,
    COUNT(*) AS totalTransactions,
    SUM(amount) AS totalRevenue
FROM payments
WHERE status='paid' AND createdAt >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
GROUP BY YEAR(createdAt), WEEK(createdAt, 1)
ORDER BY year DESC, week DESC";
$weeklySalesResult = mysqli_query($conn, $weeklySalesQuery);

// MONTHLY SALES
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

// YEARLY SALES
$yearlySalesQuery = "SELECT 
    YEAR(createdAt) AS year,
    COUNT(*) AS totalTransactions,
    SUM(amount) AS totalRevenue
FROM payments
WHERE status='paid'
GROUP BY YEAR(createdAt)
ORDER BY year DESC";
$yearlySalesResult = mysqli_query($conn, $yearlySalesQuery);
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

    <!-- SALES REPORT TABS -->
    <div class="col-lg-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Sales Report</h5>

          <!-- Tabs -->
          <ul class="nav nav-tabs nav-tabs-bordered" id="salesTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab">Daily</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="weekly-tab" data-bs-toggle="tab" data-bs-target="#weekly" type="button" role="tab">Weekly</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab">Monthly</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="yearly-tab" data-bs-toggle="tab" data-bs-target="#yearly" type="button" role="tab">Yearly</button>
            </li>
          </ul>

          <div class="tab-content pt-3" id="salesTabsContent">

            <!-- DAILY TAB -->
            <div class="tab-pane fade show active" id="daily" role="tabpanel">
              <p class="text-muted small">Showing paid transactions from the last 30 days.</p>
              <table class="table table-borderless datatable">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Total Transactions</th>
                    <th>Total Revenue</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $i = 1;
                  while ($row = mysqli_fetch_assoc($dailySalesResult)): ?>
                    <tr>
                      <td><?= $i++ ?></td>
                      <td><?= date("F j, Y", strtotime($row['saleDate'])) ?></td>
                      <td><?= $row['totalTransactions'] ?></td>
                      <td>₱<?= number_format($row['totalRevenue'], 2) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>

            <!-- WEEKLY TAB -->
            <div class="tab-pane fade" id="weekly" role="tabpanel">
              <p class="text-muted small">Showing paid transactions grouped by week (last 12 weeks).</p>
              <table class="table table-borderless datatable">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Week</th>
                    <th>Period</th>
                    <th>Total Transactions</th>
                    <th>Total Revenue</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $i = 1;
                  while ($row = mysqli_fetch_assoc($weeklySalesResult)): ?>
                    <tr>
                      <td><?= $i++ ?></td>
                      <td>Week <?= $row['week'] ?>, <?= $row['year'] ?></td>
                      <td><?= date("M j", strtotime($row['weekStart'])) ?> – <?= date("M j, Y", strtotime($row['weekEnd'])) ?></td>
                      <td><?= $row['totalTransactions'] ?></td>
                      <td>₱<?= number_format($row['totalRevenue'], 2) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>

            <!-- MONTHLY TAB -->
            <div class="tab-pane fade" id="monthly" role="tabpanel">
              <p class="text-muted small">Showing all paid transactions grouped by month.</p>
              <table class="table table-borderless datatable">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Month</th>
                    <th>Year</th>
                    <th>Total Transactions</th>
                    <th>Total Revenue</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $i = 1;
                  while ($row = mysqli_fetch_assoc($monthlySalesResult)): ?>
                    <tr>
                      <td><?= $i++ ?></td>
                      <td><?= date("F", mktime(0, 0, 0, $row['month'], 1)) ?></td>
                      <td><?= $row['year'] ?></td>
                      <td><?= $row['totalTransactions'] ?></td>
                      <td>₱<?= number_format($row['totalRevenue'], 2) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>

            <!-- YEARLY TAB -->
            <div class="tab-pane fade" id="yearly" role="tabpanel">
              <p class="text-muted small">Showing all paid transactions grouped by year.</p>
              <table class="table table-borderless datatable">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Year</th>
                    <th>Total Transactions</th>
                    <th>Total Revenue</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $i = 1;
                  while ($row = mysqli_fetch_assoc($yearlySalesResult)): ?>
                    <tr>
                      <td><?= $i++ ?></td>
                      <td><?= $row['year'] ?></td>
                      <td><?= $row['totalTransactions'] ?></td>
                      <td>₱<?= number_format($row['totalRevenue'], 2) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>

          </div><!-- end tab-content -->
        </div>
      </div>
    </div>

  </div>
</section>

<?php include('./includes/footer.php'); ?>