<?php
include_once(__DIR__ . '/../../app/middleware/user.php');
include_once(__DIR__ . '/../../app/helpers/flashMessage.php');

// Fetch cart items with product details
$userId = $_SESSION['authUser']['user_id'];
$stmt = $conn->prepare("
    SELECT c.cartId, c.quantity, c.addedAt,
           p.productId, p.name, p.price, p.imageUrl, p.stock
    FROM cart c
    JOIN products p ON c.productId = p.productId
    WHERE c.userId = ?  
    ORDER BY c.addedAt DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Cart count for badge
$cartCount = count($cartItems);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickCart – My Cart</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f4; color: #333; }
        .announcement { background: #16a34a; color: #fff; text-align: center; padding: 8px 1rem; font-size: 0.85rem; font-weight: 500; }
        .trust-bar { background: #1f2937; display: flex; justify-content: center; gap: 3rem; padding: 10px 2rem; }
        .trust-bar span { color: #d1d5db; font-size: 0.8rem; display: flex; align-items: center; gap: 6px; }
        .trust-bar span::before { content: '✓'; color: #4ade80; font-weight: 700; }
        nav { background: #fff; padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 65px; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #e5e7eb; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .nav-brand { color: #16a34a; font-size: 1.4rem; font-weight: 700; text-decoration: none; letter-spacing: 1px; }
        .nav-links { display: flex; align-items: center; gap: 1.8rem; list-style: none; }
        .nav-links a { color: #374151; text-decoration: none; font-size: 0.9rem; font-weight: 500; }
        .nav-links a.active { color: #16a34a; font-weight: 700; }
        .nav-right { display: flex; align-items: center; gap: 1.2rem; }
        .search-wrapper { display: flex; align-items: center; border: 1.5px solid #d1d5db; border-radius: 8px; overflow: hidden; }
        .search-wrapper input { border: none; outline: none; padding: 7px 12px; font-size: 0.85rem; width: 200px; background: transparent; }
        .search-wrapper button { background: #16a34a; border: none; padding: 8px 12px; cursor: pointer; color: #fff; font-size: 0.95rem; }
        .nav-icon-btn { display: flex; flex-direction: column; align-items: center; gap: 2px; text-decoration: none; color: #374151; font-size: 0.7rem; font-weight: 500; position: relative; }
        .nav-icon-btn:hover { color: #16a34a; }
        .nav-icon-btn svg { width: 22px; height: 22px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .cart-badge { position: absolute; top: -4px; right: -6px; background: #16a34a; color: #fff; font-size: 0.6rem; font-weight: 700; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; }
        .btn-logout { background: transparent; border: 1.5px solid #16a34a; color: #16a34a; padding: 6px 14px; border-radius: 7px; cursor: pointer; font-size: 0.8rem; font-weight: 600; }
        .btn-logout:hover { background: #16a34a; color: #fff; }

        /* PAGE */
        .page-header { background: #14532d; padding: 1.8rem 2rem; text-align: center; }
        .page-header h1 { color: #fff; font-size: 1.5rem; }
        .page-header h1 span { color: #4ade80; }

        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1.5rem; }

        /* EMPTY CART */
        .empty-cart { text-align: center; background: #fff; border-radius: 12px; padding: 4rem 2rem; border: 1px solid #e5e7eb; }
        .empty-cart .icon { font-size: 4rem; margin-bottom: 1rem; }
        .empty-cart h2 { color: #14532d; margin-bottom: 0.5rem; }
        .empty-cart p { color: #6b7280; font-size: 0.9rem; margin-bottom: 1.5rem; }
        .btn-continue { background: #16a34a; color: #fff; border: none; padding: 10px 24px; border-radius: 7px; font-size: 0.9rem; font-weight: 700; cursor: pointer; text-decoration: none; }

        /* CART LAYOUT */
        .cart-layout { display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; align-items: flex-start; }

        /* CART ITEMS */
        .cart-items { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .cart-items-header { padding: 1rem 1.5rem; background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; font-weight: 700; color: #374151; display: flex; justify-content: space-between; }

        .cart-item { display: flex; align-items: center; gap: 1rem; padding: 1.2rem 1.5rem; border-bottom: 1px solid #f3f4f6; }
        .cart-item:last-child { border-bottom: none; }

        .item-img { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 2rem; flex-shrink: 0; overflow: hidden; }
        .item-img img { width: 100%; height: 100%; object-fit: cover; }

        .item-details { flex: 1; }
        .item-name { font-size: 0.95rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.2rem; }
        .item-price { font-size: 0.85rem; color: #16a34a; font-weight: 700; }

        .item-qty { display: flex; align-items: center; gap: 0.5rem; }
        .qty-btn { width: 28px; height: 28px; border: 1.5px solid #e5e7eb; background: #fff; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 700; color: #374151; display: flex; align-items: center; justify-content: center; }
        .qty-btn:hover { background: #f0fdf4; border-color: #16a34a; color: #16a34a; }
        .qty-input { width: 40px; text-align: center; border: 1.5px solid #e5e7eb; border-radius: 6px; padding: 4px; font-size: 0.85rem; }

        .item-subtotal { font-size: 0.95rem; font-weight: 700; color: #14532d; min-width: 70px; text-align: right; }

        .btn-remove { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.8rem; margin-top: 0.3rem; padding: 0; }
        .btn-remove:hover { text-decoration: underline; }

        /* ORDER SUMMARY */
        .order-summary { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 1.5rem; }
        .order-summary h3 { font-size: 1rem; font-weight: 700; color: #14532d; margin-bottom: 1.2rem; padding-bottom: 0.8rem; border-bottom: 1px solid #e5e7eb; }
        .summary-row { display: flex; justify-content: space-between; font-size: 0.88rem; color: #374151; margin-bottom: 0.7rem; }
        .summary-row.total { font-size: 1rem; font-weight: 700; color: #14532d; padding-top: 0.8rem; border-top: 1px solid #e5e7eb; margin-top: 0.5rem; }
        .summary-row span:last-child { color: #16a34a; font-weight: 700; }
        .btn-checkout { display: block; width: 100%; background: #16a34a; color: #fff; border: none; padding: 12px; border-radius: 8px; font-size: 0.95rem; font-weight: 700; cursor: pointer; margin-top: 1.2rem; text-align: center; text-decoration: none; }
        .btn-checkout:hover { background: #14532d; }
        .btn-continue-shopping { display: block; width: 100%; background: #f0fdf4; color: #16a34a; border: 1.5px solid #16a34a; padding: 10px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; margin-top: 0.8rem; text-align: center; text-decoration: none; }

        footer { text-align: center; padding: 1.5rem; font-size: 0.8rem; color: #9ca3af; margin-top: 2rem; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>






<nav>
    <a href="/WST-QuickCart/public/user/index.php" class="nav-brand">QuickCart</a>git 
    <div class="nav-right">
        <div class="search-wrapper">
            <input type="text" placeholder="Search products...">
            <button>&#128269;</button>
        </div>
        <a href="#" class="nav-icon-btn">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?php echo htmlspecialchars($_SESSION['authUser']['username']); ?>
        </a>
        <a href="/WST-QuickCart/public/user/cart.php" class="nav-icon-btn">
            <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span class="cart-badge"><?php echo $cartCount; ?></span>
            Cart
        </a>
        <form action="/WST-QuickCart/app/controllers/userController.php" method="POST">
            <button type="submit" name="logoutButton" class="btn-logout">Logout</button>
        </form>
    </div>
</nav>

<div class="page-header">
    <h1>My <span>Cart</span></h1>
</div>

<div class="container">

    <?php if (empty($cartItems)): ?>
    <div class="empty-cart">
        <div class="icon">🛒</div>
        <h2>Your cart is empty</h2>
        <p>Looks like you haven't added anything yet.</p>
        <a href="/WST-QuickCart/public/user/categories.php" class="btn-continue">Start Shopping</a>
    </div>

    <?php else: ?>
    <div class="cart-layout">

        <!-- CART ITEMS -->
        <div class="cart-items">
            <div class="cart-items-header">
                <span><?php echo $cartCount; ?> item(s) in your cart</span>
                <span>Subtotal</span>
            </div>

            <?php foreach ($cartItems as $item): ?>
            <div class="cart-item">
                <!-- Image -->
                <div class="item-img">
                    <?php if ($item['imageUrl']): ?>
                        <img src="<?php echo htmlspecialchars($item['imageUrl']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php else: ?>
                        🛍️
                    <?php endif; ?>
                </div>

                <!-- Details -->
                <div class="item-details">
                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="item-price">₱<?php echo number_format($item['price'], 2); ?> each</div>

                    <!-- Remove -->
                    <form action="/WST-QuickCart/app/controllers/cartController.php" method="POST">
                        <input type="hidden" name="cartId" value="<?php echo $item['cartId']; ?>">
                        <button type="submit" name="removeFromCart" class="btn-remove">✕ Remove</button>
                    </form>
                </div>

                <!-- Quantity -->
                <form action="/WST-QuickCart/app/controllers/cartController.php" method="POST" class="item-qty">
                    <input type="hidden" name="cartId" value="<?php echo $item['cartId']; ?>">
                    <button type="button" class="qty-btn" onclick="changeQty(this, -1)">−</button>
                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="qty-input" onchange="this.form.submit()">
                    <button type="button" class="qty-btn" onclick="changeQty(this, 1)">+</button>
                    <input type="hidden" name="updateQty">
                </form>

                <!-- Subtotal -->
                <div class="item-subtotal">
                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ORDER SUMMARY -->
        <div class="order-summary">
            <h3>Order Summary</h3>
            <div class="summary-row">
                <span>Subtotal (<?php echo $cartCount; ?> items)</span>
                <span>₱<?php echo number_format($total, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Delivery Fee</span>
                <span><?php echo $total >= 4000 ? 'FREE' : '₱50.00'; ?></span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span>₱<?php echo number_format($total + ($total >= 4000 ? 0 : 50), 2); ?></span>
            </div>
            <?php if ($total >= 4000): ?>
            <p style="font-size:0.75rem;color:#16a34a;margin-top:0.5rem;">🎉 You qualify for free delivery!</p>
            <?php else: ?>
            <p style="font-size:0.75rem;color:#6b7280;margin-top:0.5rem;">Add ₱<?php echo number_format(4000 - $total, 2); ?> more for free delivery.</p>
            <?php endif; ?>
            <a href="#" class="btn-checkout">Proceed to Checkout</a>
            <a href="/WST-QuickCart/public/user/categories.php" class="btn-continue-shopping">← Continue Shopping</a>
        </div>

    </div>
    <?php endif; ?>

</div>

<footer>
    &copy; <?php echo date('Y'); ?> QuickCart. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php flashMessage(); ?>

<script>
function changeQty(btn, delta) {
    var form = btn.closest('form');
    var input = form.querySelector('.qty-input');
    var newVal = parseInt(input.value) + delta;
    if (newVal >= 1) {
        input.value = newVal;
        form.submit();
    }
}
</script>

</body>
</html>