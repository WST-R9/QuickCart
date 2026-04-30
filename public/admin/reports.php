<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// --------------------
// TOP SELLING PRODUCTS
// --------------------
$topProductsQuery = "SELECT 
    oi.productName,
    SUM(oi.quantity) AS totalSold,
    SUM(oi.quantity * oi.unitPrice) AS totalRevenue
FROM orderitems oi
GROUP BY oi.productName
ORDER BY totalSold DESC
LIMIT 10";

$topProductsResult = mysqli_query($conn, $topProductsQuery);

// --------------------
// TOP CUSTOMERS (MOST SPENT)
// --------------------
$topCustomersQuery = "SELECT 
    CONCAT(u.firstName,' ',u.lastName) AS customerName,
    u.emailAddress,
    COUNT(o.orderId) AS totalOrders,
    SUM(o.totalAmount) AS totalSpent
FROM orders o
JOIN users u ON o.userId = u.userId
GROUP BY o.userId
ORDER BY totalSpent DESC
LIMIT 10";

$topCustomersResult = mysqli_query($conn, $topCustomersQuery);

// --------------------
// TOP DELIVERY LOCATIONS
// --------------------
$topLocationsQuery = "SELECT 
    city,
    province,
    COUNT(*) AS totalDeliveries
FROM shipping
GROUP BY city, province
ORDER BY totalDeliveries DESC
LIMIT 10";

$topLocationsResult = mysqli_query($conn, $topLocationsQuery);
?>

<div class="pagetitle">
  <h1>Reports</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Reports</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">

    <!-- TOP PRODUCTS -->
    <div class="col-lg-12">
      <div class="card top-selling overflow-auto">
        <div class="card-body pb-0">
          <h5 class="card-title">Top Selling Products <span>| All Time</span></h5>

          <table class="table table-borderless">
            <thead>
              <tr>
                <th>Product</th>
                <th>Sold</th>
                <th>Total Revenue</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($topProductsResult)): ?>
                <tr>
                  <td><?= htmlspecialchars($row['productName']) ?></td>
                  <td class="fw-bold"><?= $row['totalSold'] ?></td>
                  <td>₱<?= number_format($row['totalRevenue'], 2) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>

        </div>
      </div>
    </div>

    <!-- TOP CUSTOMERS -->
    <div class="col-lg-6">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Top Customers <span>| Highest Spending</span></h5>

          <table class="table table-borderless">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Orders</th>
                <th>Total Spent</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($topCustomersResult)): ?>
                <tr>
                  <td>
                    <?= htmlspecialchars($row['customerName']) ?>
                    <br>
                    <small class="text-muted"><?= htmlspecialchars($row['emailAddress']) ?></small>
                  </td>
                  <td><?= $row['totalOrders'] ?></td>
                  <td>₱<?= number_format($row['totalSpent'], 2) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>

        </div>
      </div>
    </div>

    <!-- TOP LOCATIONS -->
    <div class="col-lg-6">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Top Delivery Locations</h5>

          <table class="table table-borderless">
            <thead>
              <tr>
                <th>City</th>
                <th>Province</th>
                <th>Deliveries</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($topLocationsResult)): ?>
                <tr>
                  <td><?= htmlspecialchars($row['city']) ?></td>
                  <td><?= htmlspecialchars($row['province']) ?></td>
                  <td><?= $row['totalDeliveries'] ?></td>
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