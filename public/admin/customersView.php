<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// Check if ID exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Customer not found!'); window.location.href='customers.php';</script>";
    exit;
}

$userId = intval($_GET['id']);

// -------------------------------
// FETCH CUSTOMER INFO
// -------------------------------
$customerQuery = "SELECT * FROM users WHERE userId = $userId AND role = 'customer'";
$customerResult = mysqli_query($conn, $customerQuery);

if (mysqli_num_rows($customerResult) == 0) {
    echo "<script>alert('Customer not found!'); window.location.href='customers.php';</script>";
    exit;
}

$customer = mysqli_fetch_assoc($customerResult);

// -------------------------------
// FETCH DEFAULT ADDRESS
// -------------------------------
$addressQuery = "SELECT * FROM addresses 
                 WHERE userId = $userId AND isDefault = 1
                 LIMIT 1";
$addressResult = mysqli_query($conn, $addressQuery);
$defaultAddress = mysqli_fetch_assoc($addressResult);

// -------------------------------
// CUSTOMER ORDER SUMMARY
// -------------------------------

// Total Orders
$totalOrdersQuery = "SELECT COUNT(*) AS totalOrders 
                     FROM orders 
                     WHERE userId = $userId";
$totalOrdersResult = mysqli_query($conn, $totalOrdersQuery);
$totalOrders = mysqli_fetch_assoc($totalOrdersResult)['totalOrders'] ?? 0;

// Paid Orders
$paidOrdersQuery = "SELECT COUNT(*) AS paidOrders
                    FROM orders o
                    JOIN payments p ON o.orderId = p.orderId
                    WHERE o.userId = $userId AND p.status = 'paid'";
$paidOrdersResult = mysqli_query($conn, $paidOrdersQuery);
$paidOrders = mysqli_fetch_assoc($paidOrdersResult)['paidOrders'] ?? 0;

// Total Spent
$totalSpentQuery = "SELECT IFNULL(SUM(p.amount), 0) AS totalSpent
                    FROM orders o
                    JOIN payments p ON o.orderId = p.orderId
                    WHERE o.userId = $userId AND p.status = 'paid'";
$totalSpentResult = mysqli_query($conn, $totalSpentQuery);
$totalSpent = mysqli_fetch_assoc($totalSpentResult)['totalSpent'] ?? 0;

// Last Order Date
$lastOrderQuery = "SELECT MAX(orderedAt) AS lastOrderDate
                   FROM orders
                   WHERE userId = $userId";
$lastOrderResult = mysqli_query($conn, $lastOrderQuery);
$lastOrderDate = mysqli_fetch_assoc($lastOrderResult)['lastOrderDate'] ?? null;

// -------------------------------
// RECENT ORDERS
// -------------------------------
$recentOrdersQuery = "SELECT 
                        o.orderId,
                        o.orderNumber,
                        o.totalAmount,
                        o.status,
                        o.orderedAt,
                        p.status AS paymentStatus,
                        p.method AS paymentMethod
                      FROM orders o
                      LEFT JOIN payments p ON o.orderId = p.orderId
                      WHERE o.userId = $userId
                      ORDER BY o.orderedAt DESC
                      LIMIT 8";
$recentOrdersResult = mysqli_query($conn, $recentOrdersQuery);

// -------------------------------
// TOP BOUGHT PRODUCTS
// -------------------------------
$topProductsQuery = "SELECT 
                        oi.productName,
                        SUM(oi.quantity) AS totalQty,
                        SUM(oi.quantity * oi.unitPrice) AS totalSpent
                     FROM orderitems oi
                     JOIN orders o ON oi.orderId = o.orderId
                     WHERE o.userId = $userId
                     GROUP BY oi.productName
                     ORDER BY totalQty DESC
                     LIMIT 5";
$topProductsResult = mysqli_query($conn, $topProductsQuery);
?>

<div class="pagetitle">
    <h1>Customer Profile</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item"><a href="customers.php">Customers</a></li>
            <li class="breadcrumb-item active">View Customer</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <div class="row">

        <!-- LEFT SIDE -->
        <div class="col-lg-8">

            <!-- CUSTOMER INFO CARD -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Customer Information</h5>

                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Full Name:</div>
                        <div class="col-md-8">
                            <?= htmlspecialchars($customer['firstName'] . " " . $customer['lastName']) ?>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Email:</div>
                        <div class="col-md-8"><?= htmlspecialchars($customer['emailAddress']) ?></div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Username:</div>
                        <div class="col-md-8"><?= htmlspecialchars($customer['username']) ?></div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Phone:</div>
                        <div class="col-md-8"><?= htmlspecialchars($customer['phoneNumber']) ?></div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Gender:</div>
                        <div class="col-md-8"><?= htmlspecialchars($customer['gender']) ?></div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Birthday:</div>
                        <div class="col-md-8"><?= date("M d, Y", strtotime($customer['birthday'])) ?></div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Date Registered:</div>
                        <div class="col-md-8"><?= date("M d, Y", strtotime($customer['dateCreated'])) ?></div>
                    </div>

                </div>
            </div>

            <!-- DEFAULT ADDRESS CARD -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Default Address</h5>

                    <?php if ($defaultAddress): ?>
                        <p class="mb-1"><strong>Recipient:</strong> <?= htmlspecialchars($defaultAddress['recipientName']) ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($defaultAddress['phoneNumber']) ?></p>
                        <p class="mb-1"><strong>Street:</strong> <?= htmlspecialchars($defaultAddress['street']) ?></p>
                        <p class="mb-1"><strong>Barangay:</strong> <?= htmlspecialchars($defaultAddress['barangay']) ?></p>
                        <p class="mb-1"><strong>City:</strong> <?= htmlspecialchars($defaultAddress['city']) ?></p>
                        <p class="mb-1"><strong>Province:</strong> <?= htmlspecialchars($defaultAddress['province']) ?></p>
                        <p class="mb-1"><strong>Zip Code:</strong> <?= htmlspecialchars($defaultAddress['zipCode']) ?></p>
                    <?php else: ?>
                        <p class="text-muted">No default address found.</p>
                    <?php endif; ?>

                </div>
            </div>

            <!-- RECENT ORDERS TABLE -->
            <div class="card recent-sales overflow-auto">
                <div class="card-body">
                    <h5 class="card-title">Recent Orders <span>| Latest</span></h5>

                    <table class="table table-borderless datatable">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recentOrdersResult) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['orderNumber']) ?></td>
                                        <td>₱<?= number_format($order['totalAmount'], 2) ?></td>
                                        <td><?= ucfirst($order['status']) ?></td>
                                        <td>
                                            <?= $order['paymentStatus'] ? ucfirst($order['paymentStatus']) : "No Payment" ?>
                                            <br>
                                            <small class="text-muted"><?= $order['paymentMethod'] ? strtoupper($order['paymentMethod']) : "" ?></small>
                                        </td>
                                        <td><?= date("M d, Y", strtotime($order['orderedAt'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                </div>
            </div>

            <!-- TOP PRODUCTS -->
            <div class="card top-selling overflow-auto">
                <div class="card-body pb-0">
                    <h5 class="card-title">Top Bought Products <span>| Customer History</span></h5>

                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity Bought</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($topProductsResult) > 0): ?>
                                <?php while ($prod = mysqli_fetch_assoc($topProductsResult)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($prod['productName']) ?></td>
                                        <td class="fw-bold"><?= $prod['totalQty'] ?></td>
                                        <td>₱<?= number_format($prod['totalSpent'], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No product history found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>

        <!-- RIGHT SIDE SUMMARY -->
        <div class="col-lg-4">

            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Customer Summary</h5>

                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Orders
                            <span class="badge bg-primary rounded-pill"><?= $totalOrders ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Paid Orders
                            <span class="badge bg-success rounded-pill"><?= $paidOrders ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Spent
                            <span class="badge bg-warning rounded-pill">₱<?= number_format($totalSpent, 2) ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Last Order
                            <span class="badge bg-dark rounded-pill">
                                <?= $lastOrderDate ? date("M d, Y", strtotime($lastOrderDate)) : "None" ?>
                            </span>
                        </li>
                    </ul>

                    <div class="d-grid mt-3">
                        <a href="customers.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Customers
                        </a>
                    </div>

                </div>
            </div>

        </div>

    </div>
</section>

<?php include('./includes/footer.php'); ?>