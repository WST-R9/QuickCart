<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;

// Fetch cart items
$stmt = $conn->prepare("
    SELECT c.cartId, c.quantity,
           p.productId, p.name, p.price, p.stock, p.imageUrl,
           cat.name AS categoryName
    FROM cart c
    JOIN products p ON c.productId = p.productId
    LEFT JOIN categories cat ON p.categoryId = cat.categoryId
    WHERE c.userId = ?
    ORDER BY c.addedAt DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Redirect back if cart is empty
if (empty($cartItems)) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Your cart is empty.'];
    header("Location: cart");
    exit;
}

// Fetch user's saved addresses
$stmt = $conn->prepare("SELECT * FROM addresses WHERE userId = ? ORDER BY isDefault DESC, createdAt DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalAmount = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$totalCount  = array_sum(array_column($cartItems, 'quantity'));

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['placeOrder'])) {
    $addressId     = (int) ($_POST['addressId'] ?? 0);
    $paymentMethod = $_POST['paymentMethod'] ?? 'cod';
    $notes         = trim($_POST['notes'] ?? '');

    // Validate address belongs to user
    $addrStmt = $conn->prepare("SELECT * FROM addresses WHERE addressId = ? AND userId = ?");
    $addrStmt->bind_param("ii", $addressId, $userId);
    $addrStmt->execute();
    $address = $addrStmt->get_result()->fetch_assoc();
    $addrStmt->close();

    if (!$address) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please select a valid delivery address.'];
        header("Location: checkout");
        exit;
    }

    // Validate payment method
    $validMethods = ['cod', 'gcash', 'maya', 'credit_card', 'bank_transfer'];
    if (!in_array($paymentMethod, $validMethods)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid payment method.'];
        header("Location: checkout");
        exit;
    }

    $conn->begin_transaction();

    try {
        // Generate order number: QC-YYYYMMDD-XXXXX
        $orderNumber = 'QC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        // Insert order
        $oStmt = $conn->prepare("
            INSERT INTO orders (orderNumber, userId, totalAmount, status, notes)
            VALUES (?, ?, ?, 'pending', ?)
        ");
        $oStmt->bind_param("sids", $orderNumber, $userId, $totalAmount, $notes);
        $oStmt->execute();
        $orderId = $conn->insert_id;
        $oStmt->close();

        // Insert order items
        foreach ($cartItems as $item) {
            $iStmt = $conn->prepare("
                INSERT INTO orderitems (orderId, productId, productName, quantity, unitPrice)
                VALUES (?, ?, ?, ?, ?)
            ");
            $iStmt->bind_param("iisid", $orderId, $item['productId'], $item['name'], $item['quantity'], $item['price']);
            $iStmt->execute();
            $iStmt->close();

            // Decrement stock
            $sStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE productId = ? AND stock >= ?");
            $sStmt->bind_param("iii", $item['quantity'], $item['productId'], $item['quantity']);
            $sStmt->execute();
            $sStmt->close();
        }

        // Insert payment
        $pStmt = $conn->prepare("
            INSERT INTO payments (orderId, method, status, amount)
            VALUES (?, ?, 'pending', ?)
        ");
        $pStmt->bind_param("isd", $orderId, $paymentMethod, $totalAmount);
        $pStmt->execute();
        $pStmt->close();

        // Insert shipping (snapshot address)
        $shStmt = $conn->prepare("
            INSERT INTO shipping (orderId, addressId, status, recipientName, phoneNumber,
                                  street, barangay, city, province, zipCode)
            VALUES (?, ?, 'preparing', ?, ?, ?, ?, ?, ?, ?)
        ");
        $shStmt->bind_param(
            "iisssssss",
            $orderId,
            $addressId,
            $address['recipientName'],
            $address['phoneNumber'],
            $address['street'],
            $address['barangay'],
            $address['city'],
            $address['province'],
            $address['zipCode']
        );
        $shStmt->execute();
        $shStmt->close();

        // Clear cart
        $cStmt = $conn->prepare("DELETE FROM cart WHERE userId = ?");
        $cStmt->bind_param("i", $userId);
        $cStmt->execute();
        $cStmt->close();

        $conn->commit();

        $_SESSION['flash'] = ['type' => 'success', 'message' => "Order <strong>$orderNumber</strong> placed successfully!"];
        header("Location: orders");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Order failed: ' . $e->getMessage()];
        header("Location: checkout");
        exit;
    }
}
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
  <h1>Checkout</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="cart">Cart</a></li>
      <li class="breadcrumb-item active">Checkout</li>
    </ol>
  </nav>
</div>

<section class="section">
  <form method="POST" action="checkout">
    <div class="row g-4">

      <!-- Left column -->
      <div class="col-lg-8">

        <!-- Delivery Address -->
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-geo-alt me-2 text-success"></i>Delivery Address</h5>

            <?php if (empty($addresses)): ?>
              <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-1"></i>
                You have no saved addresses.
                <a href="addresses" class="alert-link">Add one here</a>.
              </div>
            <?php else: ?>
              <div class="row g-3">
                <?php foreach ($addresses as $addr): ?>
                  <div class="col-md-6">
                    <label class="d-block cursor-pointer">
                      <input type="radio" name="addressId" value="<?= $addr['addressId'] ?>"
                             class="d-none address-radio"
                             <?= $addr['isDefault'] ? 'checked' : '' ?>>
                      <div class="address-card p-3 rounded-3 border h-100 <?= $addr['isDefault'] ? 'border-success bg-light' : '' ?>"
                           style="cursor:pointer; transition:all .2s;">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                          <span class="badge bg-success-subtle text-success fw-semibold">
                            <?= htmlspecialchars($addr['label'] ?? 'Address') ?>
                          </span>
                          <?php if ($addr['isDefault']): ?>
                            <span class="badge bg-success">Default</span>
                          <?php endif; ?>
                        </div>
                        <div class="fw-bold"><?= htmlspecialchars($addr['recipientName']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($addr['phoneNumber']) ?></div>
                        <div class="small mt-1">
                          <?= htmlspecialchars($addr['street']) ?>,
                          <?= htmlspecialchars($addr['barangay']) ?>,
                          <?= htmlspecialchars($addr['city']) ?>
                          <?php if ($addr['province']): ?>, <?= htmlspecialchars($addr['province']) ?><?php endif; ?>
                          <?php if ($addr['zipCode']): ?> <?= htmlspecialchars($addr['zipCode']) ?><?php endif; ?>
                        </div>
                      </div>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
              <a href="addresses" class="btn btn-outline-secondary btn-sm mt-3">
                <i class="bi bi-plus-lg me-1"></i> Add New Address
              </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-credit-card me-2 text-success"></i>Payment Method</h5>
            <div class="row g-3">

              <?php
              $methods = [
                  'cod'           => ['icon' => 'bi-cash-coin',       'label' => 'Cash on Delivery', 'desc' => 'Pay when your order arrives'],
                  'gcash'         => ['icon' => 'bi-phone',            'label' => 'GCash',            'desc' => 'Pay via GCash e-wallet'],
                  'maya'          => ['icon' => 'bi-wallet2',          'label' => 'Maya',             'desc' => 'Pay via Maya e-wallet'],
                  'credit_card'   => ['icon' => 'bi-credit-card-2-front', 'label' => 'Credit Card',  'desc' => 'Visa, Mastercard'],
                  'bank_transfer' => ['icon' => 'bi-bank',             'label' => 'Bank Transfer',    'desc' => 'Online banking transfer'],
              ];
              foreach ($methods as $value => $m):
              ?>
                <div class="col-6 col-md-4">
                  <label class="d-block cursor-pointer">
                    <input type="radio" name="paymentMethod" value="<?= $value ?>"
                           class="d-none payment-radio"
                           <?= $value === 'cod' ? 'checked' : '' ?>>
                    <div class="payment-card p-3 rounded-3 border text-center <?= $value === 'cod' ? 'border-success bg-light' : '' ?>"
                         style="cursor:pointer; transition:all .2s;">
                      <i class="bi <?= $m['icon'] ?> fs-4 text-success mb-1 d-block"></i>
                      <div class="fw-bold small"><?= $m['label'] ?></div>
                      <div class="text-muted" style="font-size:11px;"><?= $m['desc'] ?></div>
                    </div>
                  </label>
                </div>
              <?php endforeach; ?>

            </div>
          </div>
        </div>

        <!-- Order Notes -->
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-chat-left-text me-2 text-success"></i>Order Notes <span class="text-muted fw-normal" style="font-size:13px;">(optional)</span></h5>
            <textarea name="notes" class="form-control" rows="3"
                      placeholder="Special instructions, preferred delivery time, gate codes…"
                      style="border-color:#d4e8da; resize:none;"></textarea>
          </div>
        </div>

      </div>

      <!-- Right column: Order Summary -->
      <div class="col-lg-4">
        <div class="card sticky-top" style="top:80px;">
          <div class="card-body">
            <h5 class="card-title">Order Summary</h5>

            <!-- Items list -->
            <div class="mb-3" style="max-height:260px; overflow-y:auto;">
              <?php foreach ($cartItems as $item): ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                  <img src="../uploads/products/<?= htmlspecialchars($item['imageUrl'] ?? '') ?>"
                       onerror="this.src='assets/img/product-placeholder.png'"
                       style="width:40px; height:40px; object-fit:contain; border-radius:6px; background:#f4f9f5; padding:2px; flex-shrink:0;">
                  <div class="flex-grow-1 min-width-0">
                    <div class="small fw-semibold text-truncate"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="text-muted" style="font-size:11px;">x<?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></div>
                  </div>
                  <div class="small fw-bold flex-shrink-0">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                </div>
              <?php endforeach; ?>
            </div>

            <hr style="border-color:#d4e8da;">

            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted small">Subtotal (<?= $totalCount ?> item<?= $totalCount !== 1 ? 's' : '' ?>)</span>
              <span class="fw-semibold small">₱<?= number_format($totalAmount, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3">
              <span class="text-muted small">Shipping</span>
              <span class="text-success fw-semibold small">Free</span>
            </div>
            <hr style="border-color:#d4e8da;">
            <div class="d-flex justify-content-between mb-4">
              <span class="fw-bold">Total</span>
              <span class="fw-bold text-success fs-5" style="font-family:'Nunito',sans-serif;">
                ₱<?= number_format($totalAmount, 2) ?>
              </span>
            </div>

            <?php if (!empty($addresses)): ?>
              <button type="submit" name="placeOrder" class="btn btn-primary w-100 mb-2" style="font-size:15px; padding:10px;">
                <i class="bi bi-bag-check me-1"></i> Place Order
              </button>
            <?php else: ?>
              <button type="button" class="btn btn-secondary w-100 mb-2" disabled>
                <i class="bi bi-bag-check me-1"></i> Place Order
              </button>
            <?php endif; ?>
            <a href="cart" class="btn btn-outline-secondary w-100 btn-sm">
              <i class="bi bi-arrow-left me-1"></i> Back to Cart
            </a>

          </div>
        </div>
      </div>

    </div>
  </form>
</section>

<script>
// Highlight selected address card
document.querySelectorAll('.address-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.address-card').forEach(c => {
            c.classList.remove('border-success', 'bg-light');
        });
        radio.closest('label').querySelector('.address-card').classList.add('border-success', 'bg-light');
    });
});

// Highlight selected payment card
document.querySelectorAll('.payment-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.payment-card').forEach(c => {
            c.classList.remove('border-success', 'bg-light');
        });
        radio.closest('label').querySelector('.payment-card').classList.add('border-success', 'bg-light');
    });
});
</script>

<?php include('includes/footer.php'); ?>