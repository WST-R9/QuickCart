<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

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

$totalAmount = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$totalCount = array_sum(array_column($cartItems, 'quantity'));
?>

<div class="pagetitle">
    <h1>My Cart</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index">Home</a></li>
            <li class="breadcrumb-item active">Cart</li>
        </ol>
    </nav>
</div>

<section class="section">

    <?php if (empty($cartItems)): ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-cart-x"></i>
                    <h5>Your cart is empty</h5>
                    <p>Browse our products and add something you like!</p>
                    <a href="allProducts" class="btn btn-primary mt-2 d-inline-flex align-items-center">
                        <i class="bi bi-bag me-1"></i> Browse Products
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="row g-4">

            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            Cart Items
                            <span>(<?= $totalCount ?> item<?= $totalCount !== 1 ? 's' : '' ?>)</span>
                        </h5>

                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-row d-flex align-items-center gap-3 py-3 border-bottom"
                                id="cartRow_<?= $item['cartId'] ?>">

                                <!-- Image -->
                                <div class="flex-shrink-0">
                                    <img src="../uploads/products/<?= htmlspecialchars($item['imageUrl'] ?? '') ?>"
                                        alt="<?= htmlspecialchars($item['name']) ?>"
                                        onerror="this.src='assets/img/product-placeholder.png'"
                                        style="width:72px; height:72px; object-fit:contain; border-radius:10px; background:#f4f9f5; padding:4px;">
                                </div>

                                <!-- Info -->
                                <div class="flex-grow-1 min-width-0">
                                    <div class="text-muted small mb-1">
                                        <?= htmlspecialchars($item['categoryName'] ?? 'General') ?></div>
                                    <div class="fw-700 text-dark"
                                        style="font-family:'Nunito',sans-serif; font-size:15px; line-height:1.3;">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </div>
                                    <div class="text-success fw-bold mt-1">₱<?= number_format($item['price'], 2) ?> each</div>
                                </div>

                                <!-- Qty controls -->
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <button class="qty-btn"
                                        onclick="updateQty(<?= $item['cartId'] ?>, -1, <?= $item['stock'] ?>)">−</button>
                                    <span class="qty-value fw-bold"
                                        id="qty_<?= $item['cartId'] ?>"><?= $item['quantity'] ?></span>
                                    <button class="qty-btn"
                                        onclick="updateQty(<?= $item['cartId'] ?>, 1, <?= $item['stock'] ?>)">+</button>
                                </div>

                                <!-- Subtotal -->
                                <div class="flex-shrink-0 text-end" style="min-width:80px;">
                                    <div class="fw-bold text-dark" style="font-family:'Nunito',sans-serif; font-size:15px;"
                                        id="sub_<?= $item['cartId'] ?>">
                                        ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:11px;">subtotal</div>
                                </div>

                                <!-- Remove -->
                                <button class="flex-shrink-0 btn btn-sm btn-outline-danger"
                                    onclick="removeItem(<?= $item['cartId'] ?>)" title="Remove">
                                    <i class="bi bi-trash3"></i>
                                </button>

                            </div>
                        <?php endforeach; ?>

                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <a href="allProducts" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i> Continue Shopping
                            </a>
                            <button class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                                <i class="bi bi-trash3 me-1"></i> Clear Cart
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal (<?= $totalCount ?>
                                item<?= $totalCount !== 1 ? 's' : '' ?>)</span>
                            <span class="fw-bold" id="grandTotal">₱<?= number_format($totalAmount, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Shipping</span>
                            <span class="text-success fw-semibold">Free</span>
                        </div>
                        <hr style="border-color:#d4e8da;">
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold" style="font-size:16px;">Total</span>
                            <span class="fw-bold text-success" style="font-size:18px; font-family:'Nunito',sans-serif;"
                                id="grandTotal2">₱<?= number_format($totalAmount, 2) ?></span>
                        </div>

                        <a href="checkout" class="btn btn-primary w-100 mb-2" style="font-size:15px; padding:10px;">
                            <i class="bi bi-bag-check me-1"></i> Proceed to Checkout
                        </a>
                        <a href="allProducts" class="btn btn-outline-secondary w-100 btn-sm">
                            <i class="bi bi-bag me-1"></i> Continue Shopping
                        </a>

                    </div>
                </div>

                <!-- Stock warning -->
                <div id="stockWarning" class="alert alert-warning d-none mt-2" style="font-size:13px;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <span id="stockWarningMsg"></span>
                </div>
            </div>

        </div>
    <?php endif; ?>

</section>

<script>
    const prices = {
        <?php foreach ($cartItems as $item): ?>
      <?= $item['cartId'] ?>: <?= $item['price'] ?>,
        <?php endforeach; ?>
    };

    function updateQty(cartId, delta, maxStock) {
        const qtyEl = document.getElementById('qty_' + cartId);
        const subEl = document.getElementById('sub_' + cartId);
        let qty = parseInt(qtyEl.textContent) + delta;

        if (qty < 1) { removeItem(cartId); return; }
        if (qty > maxStock) {
            showStockWarning('Only ' + maxStock + ' in stock for this item.');
            return;
        }

        qtyEl.textContent = qty;
        subEl.textContent = '₱' + (prices[cartId] * qty).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        recalcTotal();

        fetch('../../app/controllers/cartController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'updateQty=1&cartId=' + cartId + '&quantity=' + qty + '&ajax=1'
        });
    }

    function removeItem(cartId) {
        const row = document.getElementById('cartRow_' + cartId);
        row.style.transition = 'opacity 0.3s';
        row.style.opacity = '0';
        setTimeout(() => {
            row.remove();
            recalcTotal();
            if (document.querySelectorAll('.cart-row').length === 0) location.reload();
        }, 300);

        fetch('../../app/controllers/cartController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'removeItem=1&cartId=' + cartId + '&ajax=1'
        });
    }

    function clearCart() {
        if (!confirm('Remove all items from your cart?')) return;
        fetch('../../app/controllers/cartController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'clearCart=1&userId=<?= $userId ?>&ajax=1'
        }).then(() => location.reload());
    }

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('.cart-row').forEach(row => {
            const cartId = row.id.replace('cartRow_', '');
            const qty = parseInt(document.getElementById('qty_' + cartId).textContent);
            total += prices[cartId] * qty;
        });
        const fmt = '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        document.getElementById('grandTotal').textContent = fmt;
        document.getElementById('grandTotal2').textContent = fmt;
    }

    function showStockWarning(msg) {
        const el = document.getElementById('stockWarning');
        document.getElementById('stockWarningMsg').textContent = msg;
        el.classList.remove('d-none');
        setTimeout(() => el.classList.add('d-none'), 3000);
    }
</script>

<?php include('includes/footer.php'); ?>