<?php
session_start();
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

$query = trim($_GET['query'] ?? '');
$error = "";

$results = [
    'orders'    => [],
    'products'  => [],
    'customers' => [],
    'categories'=> [],
    'suppliers' => [],
    'payments'  => [],
    'shipping'  => [],
    'reviews'   => [],
];

if (strlen($query) >= 2) {
    try {
        $searchTerm = "%" . $query . "%";

        // ORDERS
        $stmt = $conn->prepare("
            SELECT o.*, u.firstName, u.lastName
            FROM orders o
            LEFT JOIN users u ON o.userId = u.userId
            WHERE o.orderNumber LIKE ?
               OR o.status LIKE ?
            ORDER BY o.orderedAt DESC
            LIMIT 20
        ");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $results['orders'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // PRODUCTS
        $stmt = $conn->prepare("
            SELECT p.*, c.name AS categoryName
            FROM products p
            LEFT JOIN categories c ON p.categoryId = c.categoryId
            WHERE p.name LIKE ?
               OR p.description LIKE ?
            ORDER BY p.name
            LIMIT 20
        ");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $results['products'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // CUSTOMERS
        $stmt = $conn->prepare("
            SELECT *
            FROM users
            WHERE role = 'customer'
              AND (firstName LIKE ? OR lastName LIKE ? OR emailAddress LIKE ? OR phoneNumber LIKE ?)
            ORDER BY firstName
            LIMIT 20
        ");
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $results['customers'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // CATEGORIES
        $stmt = $conn->prepare("
            SELECT c1.*, c2.name AS parentName
            FROM categories c1
            LEFT JOIN categories c2 ON c1.parentId = c2.categoryId
            WHERE c1.name LIKE ?
               OR c1.description LIKE ?
               OR c1.slug LIKE ?
            ORDER BY c1.name
            LIMIT 20
        ");
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $results['categories'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // SUPPLIERS
        $stmt = $conn->prepare("
            SELECT *
            FROM suppliers
            WHERE name LIKE ?
               OR contactName LIKE ?
               OR email LIKE ?
               OR phone LIKE ?
            ORDER BY name
            LIMIT 20
        ");
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $results['suppliers'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // PAYMENTS
        $stmt = $conn->prepare("
            SELECT p.*, o.orderNumber, CONCAT(u.firstName, ' ', u.lastName) AS customerName
            FROM payments p
            JOIN orders o ON p.orderId = o.orderId
            JOIN users u ON o.userId = u.userId
            WHERE o.orderNumber LIKE ?
               OR p.method LIKE ?
               OR p.status LIKE ?
               OR p.referenceNumber LIKE ?
            ORDER BY p.createdAt DESC
            LIMIT 20
        ");
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $results['payments'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // SHIPPING
        $stmt = $conn->prepare("
            SELECT s.*, o.orderNumber, CONCAT(u.firstName, ' ', u.lastName) AS customerName
            FROM shipping s
            JOIN orders o ON s.orderId = o.orderId
            JOIN users u ON o.userId = u.userId
            WHERE o.orderNumber LIKE ?
               OR s.trackingNumber LIKE ?
               OR s.courier LIKE ?
               OR s.city LIKE ?
               OR s.status LIKE ?
            ORDER BY s.createdAt DESC
            LIMIT 20
        ");
        $stmt->bind_param("sssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $results['shipping'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // REVIEWS
        $stmt = $conn->prepare("
            SELECT r.*, 
                   CONCAT(u.firstName, ' ', u.lastName) AS customerName,
                   p.name AS productName
            FROM reviews r
            JOIN users u ON r.userId = u.userId
            JOIN products p ON r.productId = p.productId
            WHERE p.name LIKE ?
               OR u.firstName LIKE ?
               OR u.lastName LIKE ?
               OR r.comment LIKE ?
            ORDER BY r.createdAt DESC
            LIMIT 20
        ");
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $results['reviews'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        $error = "Search failed: " . $e->getMessage();
    }
}

$totalResults = array_sum(array_map('count', $results));
?>

<div class="pagetitle">
  <h1>Search Results</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Search</li>
    </ol>
  </nav>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (strlen($query) < 2): ?>
  <div class="alert alert-info">Please enter at least 2 characters to search.</div>

<?php elseif ($totalResults === 0): ?>
  <div class="alert alert-warning">No results found for "<strong><?= htmlspecialchars($query) ?></strong>"</div>

<?php else: ?>
  <div class="alert alert-success">
    Found <strong><?= $totalResults ?></strong> result(s) for "<strong><?= htmlspecialchars($query) ?></strong>"
  </div>

  <!-- ORDERS -->
  <?php if (!empty($results['orders'])): ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">
        <i class="bi bi-bag-check me-2"></i>Orders
        <span>(<?= count($results['orders']) ?>)</span>
      </h5>
      <div class="table-responsive">
        <table class="table table-borderless table-hover">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results['orders'] as $row): ?>
            <?php
              $badge = "bg-secondary";
              if ($row['status'] == "pending")   $badge = "bg-warning";
              elseif ($row['status'] == "confirmed")  $badge = "bg-primary";
              elseif ($row['status'] == "processing") $badge = "bg-info";
              elseif ($row['status'] == "shipped")    $badge = "bg-dark";
              elseif ($row['status'] == "delivered")  $badge = "bg-success";
              elseif ($row['status'] == "cancelled")  $badge = "bg-danger";
            ?>
            <tr>
              <td><?= htmlspecialchars($row['orderNumber']) ?></td>
              <td><?= htmlspecialchars(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? '')) ?></td>
              <td>₱<?= number_format($row['totalAmount'], 2) ?></td>
              <td><span class="badge <?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
              <td><?= date('M d, Y', strtotime($row['orderedAt'])) ?></td>
              <td>
                <a href="ordersView.php?id=<?= $row['orderId'] ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- PRODUCTS -->
  <?php if (!empty($results['products'])): ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">
        <i class="bi bi-boxes me-2"></i>Products
        <span>(<?= count($results['products']) ?>)</span>
      </h5>
      <div class="table-responsive">
        <table class="table table-borderless table-hover">
          <thead>
            <tr>
              <th>Image</th>
              <th>Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results['products'] as $row): ?>
            <?php
              $badge = "bg-secondary";
              if ($row['status'] == "active")       $badge = "bg-success";
              elseif ($row['status'] == "inactive")      $badge = "bg-dark";
              elseif ($row['status'] == "out_of_stock")  $badge = "bg-danger";
            ?>
            <tr>
              <td>
                <?php if (!empty($row['imageUrl'])): ?>
                  <img src="../uploads/products/<?= htmlspecialchars($row['imageUrl']) ?>"
                       style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                <?php else: ?>
                  <div style="width:40px;height:40px;background:#e6f4ea;border-radius:6px;
                              display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-image text-muted"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['categoryName'] ?? 'None') ?></td>
              <td>₱<?= number_format($row['price'], 2) ?></td>
              <td><?= $row['stock'] ?></td>
              <td>
                <span class="badge <?= $badge ?>">
                  <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                </span>
              </td>
              <td>
                <a href="inventory-edit.php?id=<?= $row['productId'] ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- CUSTOMERS -->
  <?php if (!empty($results['customers'])): ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">
        <i class="bi bi-people me-2"></i>Customers
        <span>(<?= count($results['customers']) ?>)</span>
      </h5>
      <div class="table-responsive">
        <table class="table table-borderless table-hover">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Joined</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results['customers'] as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['firstName'] . ' ' . $row['lastName']) ?></td>
              <td><?= htmlspecialchars($row['emailAddress']) ?></td>
              <td><?= htmlspecialchars($row['phoneNumber']) ?></td>
              <td><?= date('M d, Y', strtotime($row['dateCreated'])) ?></td>
              <td>
                <a href="customersView.php?id=<?= $row['userId'] ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- CATEGORIES -->
  <?php if (!empty($results['categories'])): ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">
        <i class="bi bi-tags me-2"></i>Categories
        <span>(<?= count($results['categories']) ?>)</span>
      </h5>
      <div class="table-responsive">
        <table class="table table-borderless table-hover">
          <thead>
            <tr>
              <th>Name</th>
              <th>Slug</th>
              <th>Parent</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results['categories'] as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['slug']) ?></td>
              <td><?= $row['parentName'] ? htmlspecialchars($row['parentName']) : 'None' ?></td>
              <td>
                <a href="categories-edit.php?id=<?= $row['categoryId'] ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- SUPPLIERS -->
  <?php if (!empty($results['suppliers'])): ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">
        <i class="bi bi-truck-flatbed me-2"></i>Suppliers
        <span>(<?= count($results['suppliers']) ?>)</span>
      </h5>
      <div class="table-responsive">
        <table class="table table-borderless table-hover">
          <thead>
            <tr>
              <th>Name</th>
              <th>Contact</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results['suppliers'] as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['contactName'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($row['phone'] ?? 'N/A') ?></td>
              <td>
                <a href="suppliers-edit.php?id=<?= $row['supplierId'] ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- PAYMENTS -->
  <?php if (!empty($results['payments'])): ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">
        <i class="bi bi-credit-card me-2"></i>Payments
        <span>(<?= count($results['payments']) ?>)</span>
      </h5>
      <div class="table-responsive">
        <table class="table table-borderless table-hover">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Method</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results['payments'] as $row): ?>
            <?php
              $badge = "bg-secondary";
              if ($row['status'] == "paid")     $badge = "bg-success";
              elseif ($row['status'] == "pending")   $badge = "bg-warning";
              elseif ($row['status'] == "failed")    $badge = "bg-danger";
              elseif ($row['status'] == "refunded")  $badge = "bg-dark";
            ?>
            <tr>
              <td><?= htmlspecialchars($row['orderNumber']) ?></td>
              <td><?= htmlspecialchars($row['customerName']) ?></td>
              <td><?= strtoupper($row['method']) ?></td>
              <td><span class="badge <?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
              <td>₱<?= number_format($row['amount'], 2) ?></td>
              <td>
                <a href="paymentsView.php?id=<?= $row['paymentId'] ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- SHIPPING -->
  <?php if (!empty($results['shipping'])): ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">
        <i class="bi bi-truck me-2"></i>Shipping
        <span>(<?= count($results['shipping']) ?>)</span>
      </h5>
      <div class="table-responsive">
        <table class="table table-borderless table-hover">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Courier</th>
              <th>Tracking #</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results['shipping'] as $row): ?>
            <?php
              $badge = "bg-secondary";
              if ($row['status'] == "preparing")        $badge = "bg-warning";
              elseif ($row['status'] == "shipped")           $badge = "bg-primary";
              elseif ($row['status'] == "out_for_delivery")  $badge = "bg-info";
              elseif ($row['status'] == "delivered")         $badge = "bg-success";
              elseif ($row['status'] == "returned")          $badge = "bg-danger";
            ?>
            <tr>
              <td><?= htmlspecialchars($row['orderNumber']) ?></td>
              <td><?= htmlspecialchars($row['customerName']) ?></td>
              <td><?= htmlspecialchars($row['courier'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($row['trackingNumber'] ?? 'N/A') ?></td>
              <td>
                <span class="badge <?= $badge ?>">
                  <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                </span>
              </td>
              <td>
                <a href="shippingView.php?id=<?= $row['shippingId'] ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- REVIEWS -->
  <?php if (!empty($results['reviews'])): ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">
        <i class="bi bi-star me-2"></i>Reviews
        <span>(<?= count($results['reviews']) ?>)</span>
      </h5>
      <div class="table-responsive">
        <table class="table table-borderless table-hover">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Product</th>
              <th>Rating</th>
              <th>Comment</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results['reviews'] as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['customerName']) ?></td>
              <td><?= htmlspecialchars($row['productName']) ?></td>
              <td>
                <span class="badge bg-warning text-dark">
                  <?= $row['rating'] ?> ★
                </span>
              </td>
              <td><?= $row['comment'] ? htmlspecialchars(substr($row['comment'], 0, 60)) . (strlen($row['comment']) > 60 ? '...' : '') : 'No comment' ?></td>
              <td><?= date('M d, Y', strtotime($row['createdAt'])) ?></td>
              <td>
                <a href="reviewsView.php?id=<?= $row['reviewId'] ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

<?php endif; ?>

<?php include('./includes/footer.php'); ?>