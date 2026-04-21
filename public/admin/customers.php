<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// ---------------------------
// SUMMARY CARDS QUERIES
// ---------------------------

// Total Customers
$totalCustomersQuery = "SELECT COUNT(*) AS totalCustomers
                        FROM users
                        WHERE role = 'customer'";
$totalCustomersResult = mysqli_query($conn, $totalCustomersQuery);
$totalCustomers = mysqli_fetch_assoc($totalCustomersResult)['totalCustomers'] ?? 0;


// Active Customers (ordered at least once)
$activeCustomersQuery = "SELECT COUNT(DISTINCT u.userId) AS activeCustomers
                         FROM users u
                         JOIN orders o ON u.userId = o.userId
                         WHERE u.role = 'customer'";
$activeCustomersResult = mysqli_query($conn, $activeCustomersQuery);
$activeCustomers = mysqli_fetch_assoc($activeCustomersResult)['activeCustomers'] ?? 0;


// New Customers This Month
$newCustomersQuery = "SELECT COUNT(*) AS newCustomers
                      FROM users
                      WHERE role = 'customer'
                      AND MONTH(dateCreated) = MONTH(CURDATE())
                      AND YEAR(dateCreated) = YEAR(CURDATE())";
$newCustomersResult = mysqli_query($conn, $newCustomersQuery);
$newCustomers = mysqli_fetch_assoc($newCustomersResult)['newCustomers'] ?? 0;


// VIP Customers (spent >= 5000 paid)
$vipCustomersQuery = "SELECT COUNT(*) AS vipCustomers
                      FROM (
                          SELECT u.userId, IFNULL(SUM(p.amount), 0) AS totalSpent
                          FROM users u
                          LEFT JOIN orders o ON u.userId = o.userId
                          LEFT JOIN payments p ON o.orderId = p.orderId AND p.status = 'paid'
                          WHERE u.role = 'customer'
                          GROUP BY u.userId
                          HAVING totalSpent >= 5000
                      ) AS vip_table";
$vipCustomersResult = mysqli_query($conn, $vipCustomersQuery);
$vipCustomers = mysqli_fetch_assoc($vipCustomersResult)['vipCustomers'] ?? 0;


// ---------------------------
// CUSTOMER LIST TABLE QUERY
// ---------------------------
$customersQuery = "SELECT 
                        u.userId,
                        CONCAT(u.firstName, ' ', u.lastName) AS customerName,
                        u.emailAddress,
                        u.phoneNumber,
                        IFNULL(a.city, 'N/A') AS city,
                        u.dateCreated,
                        COUNT(DISTINCT o.orderId) AS totalOrders,
                        IFNULL(SUM(p.amount), 0) AS totalSpent,
                        MAX(o.orderedAt) AS lastOrderDate
                    FROM users u
                    LEFT JOIN addresses a 
                        ON u.userId = a.userId AND a.isDefault = 1
                    LEFT JOIN orders o 
                        ON u.userId = o.userId
                    LEFT JOIN payments p 
                        ON o.orderId = p.orderId AND p.status = 'paid'
                    WHERE u.role = 'customer'
                    GROUP BY u.userId
                    ORDER BY totalSpent DESC";

$customersResult = mysqli_query($conn, $customersQuery);
?>

<div class="pagetitle">
    <h1>Customers</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item active">Customers</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <div class="row">

        <!-- SUMMARY CARDS -->
        <div class="col-lg-12">
            <div class="row">

                <!-- Total Customers -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card customers-card">
                        <div class="card-body">
                            <h5 class="card-title">Customers <span>| Total</span></h5>
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

                <!-- Active Customers -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">Active <span>| Customers</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person-check"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?= $activeCustomers ?></h6>
                                    <span class="text-muted small pt-2 ps-1">Ordered at least once</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Customers -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">New <span>| This Month</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person-plus"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?= $newCustomers ?></h6>
                                    <span class="text-muted small pt-2 ps-1">Recently registered</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VIP Customers -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card info-card">
                        <div class="card-body">
                            <h5 class="card-title">VIP <span>| Customers</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-star-fill"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?= $vipCustomers ?></h6>
                                    <span class="text-muted small pt-2 ps-1">Spent ₱5000+</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- CUSTOMER TABLE -->
        <div class="col-lg-12">
            <div class="card recent-sales overflow-auto">
                <div class="card-body">
                    <h5 class="card-title">Customer List <span>| All Customers</span></h5>

                    <table class="table table-borderless datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>City</th>
                                <th>Total Orders</th>
                                <th>Total Spent</th>
                                <th>Last Order</th>
                                <th>Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (mysqli_num_rows($customersResult) > 0): ?>
                                <?php $count = 1; ?>
                                <?php while ($row = mysqli_fetch_assoc($customersResult)): ?>

                                    <?php
                                    $totalSpent = $row['totalSpent'];
                                    $totalOrders = $row['totalOrders'];
                                    $lastOrderDate = $row['lastOrderDate'];
                                    $dateCreated = $row['dateCreated'];

                                    // Determine Customer Type
                                    $type = "Inactive";
                                    $badge = "bg-secondary";

                                    if ($totalOrders > 0) {
                                        $type = "Active";
                                        $badge = "bg-success";
                                    }

                                    if (strtotime($dateCreated) >= strtotime("-30 days")) {
                                        $type = "New";
                                        $badge = "bg-primary";
                                    }

                                    if ($totalSpent >= 5000) {
                                        $type = "VIP";
                                        $badge = "bg-warning";
                                    }

                                    // Check if new (registered within last 30 days)
                                    if (strtotime($dateCreated) >= strtotime("-30 days")) {
                                        $type = "New";
                                        $badge = "bg-primary";
                                    }
                                    ?>

                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td><?= htmlspecialchars($row['customerName']) ?></td>
                                        <td><?= htmlspecialchars($row['emailAddress']) ?></td>
                                        <td><?= htmlspecialchars($row['phoneNumber']) ?></td>
                                        <td><?= htmlspecialchars($row['city']) ?></td>
                                        <td class="fw-bold"><?= $totalOrders ?></td>
                                        <td>₱<?= number_format($totalSpent, 2) ?></td>

                                        <td>
                                            <?= $lastOrderDate ? date("M d, Y", strtotime($lastOrderDate)) : "No Orders" ?>
                                        </td>

                                        <td>
                                            <span class="badge <?= $badge ?>">
                                                <?= $type ?>
                                            </span>
                                        </td>

                                        <td>
                                            <a href="customersView.php?id=<?= $row['userId'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No customers found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>

                </div>
            </div>
        </div>

    </div>
</section>

<?php include('./includes/footer.php'); ?>