<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

// Orders Query
$ordersQuery = "SELECT 
    o.orderId,
    o.orderNumber,
    CONCAT(u.firstName, ' ', u.lastName) AS customerName,
    o.totalAmount,
    o.status,
    o.orderedAt,
    p.status AS paymentStatus,
    p.method AS paymentMethod
FROM orders o
JOIN users u ON o.userId = u.userId
LEFT JOIN payments p ON o.orderId = p.orderId
ORDER BY o.orderedAt DESC";

$ordersResult = mysqli_query($conn, $ordersQuery);
?>

<div class="pagetitle">
  <h1>Orders</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Orders</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-12">

      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">Orders List <span>| All Orders</span></h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>
              <?php while ($row = mysqli_fetch_assoc($ordersResult)) : ?>
                <?php
                $status = $row['status'];
                $badge = "bg-secondary";

                if ($status == "pending") $badge = "bg-warning";
                elseif ($status == "confirmed") $badge = "bg-primary";
                elseif ($status == "processing") $badge = "bg-info";
                elseif ($status == "shipped") $badge = "bg-dark";
                elseif ($status == "delivered") $badge = "bg-success";
                elseif ($status == "cancelled") $badge = "bg-danger";
                elseif ($status == "refunded") $badge = "bg-secondary";
                ?>

                <tr>
                  <td><?= htmlspecialchars($row['orderNumber']) ?></td>
                  <td><?= htmlspecialchars($row['customerName']) ?></td>
                  <td>₱<?= number_format($row['totalAmount'], 2) ?></td>

                  <td>
                    <span class="badge <?= $badge ?>">
                      <?= ucfirst($status) ?>
                    </span>
                  </td>

                  <td>
                    <?php if ($row['paymentStatus']): ?>
                      <span class="badge bg-success"><?= ucfirst($row['paymentStatus']) ?></span>
                      <br>
                      <small class="text-muted"><?= strtoupper($row['paymentMethod']) ?></small>
                    <?php else: ?>
                      <span class="badge bg-secondary">No Payment</span>
                    <?php endif; ?>
                  </td>

                  <td><?= date("M d, Y", strtotime($row['orderedAt'])) ?></td>

                  <td>
                    <a href="order-view.php?id=<?= $row['orderId'] ?>" class="btn btn-sm btn-primary">
                      <i class="bi bi-eye"></i> View
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