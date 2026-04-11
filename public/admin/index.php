<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');

include_once("../../app/config/config.php");

// ------------------------
// DASHBOARD QUERIES
// ------------------------

// Total Orders Today
$ordersTodayQuery = "SELECT COUNT(*) AS totalOrdersToday 
                     FROM orders 
                     WHERE DATE(orderedAt) = CURDATE()";
$ordersTodayResult = mysqli_query($conn, $ordersTodayQuery);
$ordersToday = mysqli_fetch_assoc($ordersTodayResult)['totalOrdersToday'] ?? 0;

// Revenue This Month (only paid orders)
$revenueMonthQuery = "SELECT IFNULL(SUM(amount), 0) AS revenueThisMonth
                      FROM payments
                      WHERE status = 'paid'
                      AND MONTH(createdAt) = MONTH(CURDATE())
                      AND YEAR(createdAt) = YEAR(CURDATE())";
$revenueMonthResult = mysqli_query($conn, $revenueMonthQuery);
$revenueThisMonth = mysqli_fetch_assoc($revenueMonthResult)['revenueThisMonth'] ?? 0;

// Total Customers
$totalCustomersQuery = "SELECT COUNT(*) AS totalCustomers 
                        FROM users 
                        WHERE role = 'customer'";
$totalCustomersResult = mysqli_query($conn, $totalCustomersQuery);
$totalCustomers = mysqli_fetch_assoc($totalCustomersResult)['totalCustomers'] ?? 0;

// Pending Orders
$pendingOrdersQuery = "SELECT COUNT(*) AS pendingOrders
                       FROM orders
                       WHERE status = 'pending'";
$pendingOrdersResult = mysqli_query($conn, $pendingOrdersQuery);
$pendingOrders = mysqli_fetch_assoc($pendingOrdersResult)['pendingOrders'] ?? 0;

// Low Stock Products (stock <= 5)
$lowStockQuery = "SELECT COUNT(*) AS lowStockCount
                  FROM products
                  WHERE stock <= 5";
$lowStockResult = mysqli_query($conn, $lowStockQuery);
$lowStockCount = mysqli_fetch_assoc($lowStockResult)['lowStockCount'] ?? 0;


// -----------------------------
// RECENT ORDERS TABLE
// -----------------------------
$recentOrdersQuery = "SELECT 
                        o.orderId,
                        o.orderNumber,
                        CONCAT(u.firstName, ' ', u.lastName) AS customerName,
                        o.totalAmount,
                        o.status,
                        o.orderedAt
                      FROM orders o
                      JOIN users u ON o.userId = u.userId
                      ORDER BY o.orderedAt DESC
                      LIMIT 8";
$recentOrdersResult = mysqli_query($conn, $recentOrdersQuery);


// -----------------------------
// TOP SELLING PRODUCTS TABLE
// -----------------------------
$topSellingQuery = "SELECT 
                      p.productId,
                      p.name AS productName,
                      p.price,
                      SUM(oi.quantity) AS totalSold,
                      SUM(oi.quantity * oi.unitPrice) AS totalRevenue
                    FROM orderitems oi
                    JOIN products p ON oi.productId = p.productId
                    GROUP BY p.productId
                    ORDER BY totalSold DESC
                    LIMIT 5";
$topSellingResult = mysqli_query($conn, $topSellingQuery);

?>

<div class="pagetitle">
  <h1>Admin Dashboard</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Dashboard</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">

    <!-- LEFT SIDE -->
    <div class="col-lg-8">
      <div class="row">

        <!-- Total Orders Today -->
        <div class="col-xxl-4 col-md-6">
          <div class="card info-card sales-card">
            <div class="card-body">
              <h5 class="card-title">Orders <span>| Today</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-bag-check"></i>
                </div>
                <div class="ps-3">
                  <h6><?= $ordersToday ?></h6>
                  <span class="text-muted small pt-2 ps-1">Total orders placed today</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Revenue This Month -->
        <div class="col-xxl-4 col-md-6">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">Revenue <span>| This Month</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-cash-coin"></i>
                </div>
                <div class="ps-3">
                  <h6>₱<?= number_format($revenueThisMonth, 2) ?></h6>
                  <span class="text-muted small pt-2 ps-1">Paid transactions this month</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Total Customers -->
        <div class="col-xxl-4 col-xl-12">
          <div class="card info-card customers-card">
            <div class="card-body">
              <h5 class="card-title">Customers <span>| All Time</span></h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-people"></i>
                </div>
                <div class="ps-3">
                  <h6><?= $totalCustomers ?></h6>
                  <span class="text-muted small pt-2 ps-1">Registered customers</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pending Orders -->
        <div class="col-xxl-6 col-md-6">
          <div class="card info-card">
            <div class="card-body">
              <h5 class="card-title">Pending Orders</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="ps-3">
                  <h6><?= $pendingOrders ?></h6>
                  <span class="text-muted small pt-2 ps-1">Orders waiting confirmation</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-xxl-6 col-md-6">
          <div class="card info-card">
            <div class="card-body">
              <h5 class="card-title">Low Stock Products</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="ps-3">
                  <h6><?= $lowStockCount ?></h6>
                  <span class="text-muted small pt-2 ps-1">Products with stock ≤ 5</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-12">
          <div class="card recent-sales overflow-auto">
            <div class="card-body">
              <h5 class="card-title">Recent Orders <span>| Latest</span></h5>

              <table class="table table-borderless datatable">
                <thead>
                  <tr>
                    <th scope="col">Order #</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Total</th>
                    <th scope="col">Status</th>
                    <th scope="col">Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($recentOrdersResult)) : ?>
                    <tr>
                      <td><?= htmlspecialchars($row['orderNumber']) ?></td>
                      <td><?= htmlspecialchars($row['customerName']) ?></td>
                      <td>₱<?= number_format($row['totalAmount'], 2) ?></td>
                      <td>
                        <?php
                        $status = $row['status'];
                        $badge = "bg-secondary";

                        if ($status == "pending") $badge = "bg-warning";
                        elseif ($status == "confirmed") $badge = "bg-primary";
                        elseif ($status == "processing") $badge = "bg-info";
                        elseif ($status == "shipped") $badge = "bg-dark";
                        elseif ($status == "delivered") $badge = "bg-success";
                        elseif ($status == "cancelled") $badge = "bg-danger";
                        ?>

                        <span class="badge <?= $badge ?>">
                          <?= ucfirst($status) ?>
                        </span>
                      </td>
                      <td><?= date("M d, Y", strtotime($row['orderedAt'])) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>

            </div>
          </div>
        </div>

        <!-- Top Selling Products -->
        <div class="col-12">
          <div class="card top-selling overflow-auto">
            <div class="card-body pb-0">
              <h5 class="card-title">Top Selling Products <span>| All Time</span></h5>

              <table class="table table-borderless">
                <thead>
                  <tr>
                    <th scope="col">Product</th>
                    <th scope="col">Price</th>
                    <th scope="col">Sold</th>
                    <th scope="col">Revenue</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($topSellingResult)) : ?>
                    <tr>
                      <td><?= htmlspecialchars($row['productName']) ?></td>
                      <td>₱<?= number_format($row['price'], 2) ?></td>
                      <td class="fw-bold"><?= $row['totalSold'] ?></td>
                      <td>₱<?= number_format($row['totalRevenue'], 2) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>

            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="col-lg-4">

      <!-- Admin System Info -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">System Overview</h5>
          <p class="small text-muted mb-2">
            Welcome to the Admin Panel of QuickCart Online Convenience Store.
          </p>

          <ul class="list-group">
            <li class="list-group-item d-flex justify-content-between align-items-center">
              Total Customers
              <span class="badge bg-primary rounded-pill"><?= $totalCustomers ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              Pending Orders
              <span class="badge bg-warning rounded-pill"><?= $pendingOrders ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              Low Stock Products
              <span class="badge bg-danger rounded-pill"><?= $lowStockCount ?></span>
            </li>
          </ul>

        </div>
      </div>

      <!-- Quick Admin Actions -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Quick Actions</h5>

          <div class="d-grid gap-2">
            <a href="inventory" class="btn btn-success">
              <i class="bi bi-plus-circle"></i> Add New Product
            </a>
            <a href="customers" class="btn btn-primary">
              <i class="bi bi-person-lines-fill"></i> Manage Customers
            </a>
            <a href="sales" class="btn btn-dark">
              <i class="bi bi-receipt"></i> View Sales Report
            </a>
          </div>
        </div>
      </div>

    </div>

  </div>
</section>

<?php
include('./includes/footer.php');
?>