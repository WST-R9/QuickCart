<?php
include_once(__DIR__ . '/../../app/middleware/user.php');
include_once(__DIR__ . '/../../app/helpers/flashMessage.php');

$result = $conn->query("SELECT * FROM products WHERE status = 'active' ORDER BY categoryId, name");
$allProducts = [];
while ($row = $result->fetch_assoc()) {
    $allProducts[$row['categoryId']][] = $row;
}

$userId = $_SESSION['authUser']['user_id'] 
       ?? $_SESSION['authUser']['userId'] 
       ?? $_SESSION['authUser']['id'] 
       ?? 0;
$cartResult = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE userId = " . intval($userId));
$cartCount = $cartResult->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickCart – Categories</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f4; color: #333; }
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
        .nav-icon-btn svg { width: 22px; height: 22px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .cart-badge { position: absolute; top: -4px; right: -6px; background: #16a34a; color: #fff; font-size: 0.6rem; font-weight: 700; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; }
        .btn-logout { background: transparent; border: 1.5px solid #16a34a; color: #16a34a; padding: 6px 14px; border-radius: 7px; cursor: pointer; font-size: 0.8rem; font-weight: 600; }
        .btn-logout:hover { background: #16a34a; color: #fff; }
        .page-wrapper { display: flex; max-width: 100%; margin: 1.5rem 0; padding: 0 2rem; gap: 1.5rem; align-items: flex-start; }
        .sidebar { width: 320px; flex-shrink: 0; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; position: sticky; top: 80px; }
        .sidebar-title { background: #14532d; color: #fff; padding: 1.3rem 1.4rem; font-size: 1.15rem; font-weight: 700; }
        .sidebar-list { list-style: none; }
        .sidebar-list li a { display: flex; align-items: center; gap: 14px; padding: 1.1rem 1.4rem; text-decoration: none; color: #374151; font-size: 1rem; font-weight: 500; border-bottom: 1px solid #f3f4f6; cursor: pointer; border-left: 3px solid transparent; }
        .sidebar-list li a:hover { background: #f0fdf4; color: #16a34a; }
        .sidebar-list li a.active { background: #f0fdf4; color: #16a34a; font-weight: 700; border-left: 3px solid #16a34a; }
        .sidebar-list li:last-child a { border-bottom: none; }
        .cat-icon-sm { width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .badge-18 { margin-left: auto; background: #fef3c7; color: #92400e; font-size: 0.6rem; font-weight: 700; padding: 2px 6px; border-radius: 20px; }
        .main-content { flex: 1; min-width: 0; }
        .hero { position: relative; height: 220px; border-radius: 12px; overflow: hidden; margin-bottom: 1.4rem; }
        .hero img { width: 100%; height: 100%; object-fit: cover; object-position: center; }
        .hero-overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.35), rgba(20,83,45,0.78)); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 2rem; }
        .hero-overlay h1 { font-size: 1.6rem; color: #fff; margin-bottom: 0.4rem; text-shadow: 0 2px 10px rgba(0,0,0,0.6); }
        .hero-overlay h1 span { color: #4ade80; }
        .hero-overlay p { color: #e5e7eb; font-size: 0.88rem; margin-bottom: 1rem; }
        .btn-shop { background: #16a34a; color: #fff; border: none; padding: 10px 24px; border-radius: 7px; font-size: 0.88rem; font-weight: 700; cursor: pointer; text-decoration: none; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .section-header h2 { font-size: 1rem; font-weight: 700; color: #14532d; text-transform: uppercase; }
        .section-desc { font-size: 0.8rem; color: #6b7280; margin-bottom: 1rem; }
        .see-all { font-size: 0.83rem; color: #16a34a; text-decoration: none; font-weight: 600; }
        .subcategory-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.2rem; }
        .sub-tab { background: #fff; border: 1.5px solid #e5e7eb; color: #374151; padding: 5px 14px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; cursor: pointer; }
        .sub-tab.active { background: #16a34a; color: #fff; border-color: #16a34a; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)); gap: 1rem; }
        .product-card { background: #fff; border-radius: 10px; border: 1px solid #e5e7eb; overflow: hidden; cursor: pointer; }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(22,163,74,0.13); border-color: #16a34a; }
        .product-img-placeholder { width: 100%; height: 130px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; }
        .product-info { padding: 0.65rem; }
        .product-name-text { font-size: 0.82rem; font-weight: 600; color: #1a1a1a; margin-bottom: 4px; line-height: 1.3; }
        .product-price-text { font-size: 0.85rem; color: #16a34a; font-weight: 700; }
        .product-btn { display: block; margin: 0 0.65rem 0.65rem; background: #16a34a; color: #fff; border: none; border-radius: 6px; padding: 7px; font-size: 0.76rem; font-weight: 600; cursor: pointer; width: calc(100% - 1.3rem); }
        .product-btn:hover { background: #14532d; }
        .cat-panel { display: none; }
        .cat-panel.active { display: block; }
        footer { text-align: center; padding: 1.5rem; font-size: 0.8rem; color: #9ca3af; margin-top: 1rem; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>

<nav>
    <a href="/WST-QuickCart/public/user/index.php" class="nav-brand">QuickCart</a>
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

<div class="page-wrapper">

    <aside class="sidebar">
        <div class="sidebar-title">Categories</div>
        <ul class="sidebar-list">
            <li><a href="#" class="cat-link active" data-cat="beverages"><div class="cat-icon-sm" style="background:#dbeafe;">🥤</div>Beverages</a></li>
            <li><a href="#" class="cat-link" data-cat="snacks"><div class="cat-icon-sm" style="background:#fef9c3;">🍿</div>Snacks</a></li>
            <li><a href="#" class="cat-link" data-cat="readytoeat"><div class="cat-icon-sm" style="background:#fce7f3;">🍱</div>Ready-to-Eat</a></li>
            <li><a href="#" class="cat-link" data-cat="frozen"><div class="cat-icon-sm" style="background:#e0f2fe;">🧊</div>Frozen & Refrigerated</a></li>
            <li><a href="#" class="cat-link" data-cat="pantry"><div class="cat-icon-sm" style="background:#dcfce7;">🥫</div>Pantry Essentials</a></li>
            <li><a href="#" class="cat-link" data-cat="personalcare"><div class="cat-icon-sm" style="background:#fae8ff;">🧴</div>Personal Care</a></li>
            <li><a href="#" class="cat-link" data-cat="household"><div class="cat-icon-sm" style="background:#f1f5f9;">🧹</div>Household Items</a></li>
            <li><a href="#" class="cat-link" data-cat="tobacco"><div class="cat-icon-sm" style="background:#fee2e2;">🍺</div>Tobacco & Alcohol<span class="badge-18">18+</span></a></li>
            <li><a href="#" class="cat-link" data-cat="medicine"><div class="cat-icon-sm" style="background:#d1fae5;">💊</div>OTC Medicine</a></li>
            <li><a href="#" class="cat-link" data-cat="misc"><div class="cat-icon-sm" style="background:#ede9fe;">📱</div>Misc & Services</a></li>
        </ul>
    </aside>

    <div class="main-content">

        <div class="hero">
            <img src="/WST-QuickCart/public/user/assets/img/hero-bg.jpg" alt="Hero">
            <div class="hero-overlay">
                <h1>Shop Everything, <span>At Your Fingertips</span></h1>
                <p>Everything you need in just a few clicks.</p>
                <a href="#" class="btn-shop">SHOP ALL PRODUCTS</a>
            </div>
        </div>

        <?php
        $panels = [
            'beverages'   => ['id' => 1, 'emoji' => '🥤', 'label' => 'Beverages',            'desc' => 'Soft drinks, water, energy drinks, coffee, tea, milk & juices.',         'tabs' => ['All','Soft Drinks','Bottled Water','Energy Drinks','Coffee & Tea','Milk & Juices']],
            'snacks'      => ['id' => 2, 'emoji' => '🍿', 'label' => 'Snacks',               'desc' => 'Chips, crackers, cookies, candy, chocolates & nuts.',                    'tabs' => ['All','Chips & Crackers','Cookies','Candy & Chocolates','Nuts']],
            'readytoeat'  => ['id' => 3, 'emoji' => '🍱', 'label' => 'Ready-to-Eat',         'desc' => 'Sandwiches, burgers, hotdogs, instant noodles, cup meals & rice meals.', 'tabs' => ['All','Sandwiches','Hotdogs','Instant Noodles','Cup Meals','Rice Meals']],
            'frozen'      => ['id' => 4, 'emoji' => '🧊', 'label' => 'Frozen & Refrigerated','desc' => 'Ice cream, frozen meals, yogurt, cheese & processed meats.',             'tabs' => ['All','Ice Cream','Frozen Meals','Yogurt & Cheese','Processed Meat']],
            'pantry'      => ['id' => 5, 'emoji' => '🥫', 'label' => 'Pantry Essentials',    'desc' => 'Bread, canned goods, instant coffee & condiments.',                      'tabs' => ['All','Bread','Canned Goods','Instant Coffee','Condiments']],
            'personalcare'=> ['id' => 6, 'emoji' => '🧴', 'label' => 'Personal Care',        'desc' => 'Shampoo, soap, toothpaste, deodorant & sanitary products.',              'tabs' => ['All','Shampoo','Soap','Toothpaste','Deodorant','Sanitary']],
            'household'   => ['id' => 7, 'emoji' => '🧹', 'label' => 'Household Items',      'desc' => 'Tissue, cleaning supplies, batteries & light bulbs.',                    'tabs' => ['All','Tissue & Paper','Cleaning Supplies','Batteries','Light Bulbs']],
            'tobacco'     => ['id' => 8, 'emoji' => '🍺', 'label' => 'Tobacco & Alcohol',    'desc' => 'Cigarettes, vapes, beer & liquor. Must be 18+ to purchase.',             'tabs' => ['All','Cigarettes','Vapes','Beer','Liquor']],
            'medicine'    => ['id' => 9, 'emoji' => '💊', 'label' => 'OTC Medicine',         'desc' => 'Pain relievers, vitamins & basic first aid items.',                      'tabs' => ['All','Pain Relievers','Vitamins','First Aid']],
            'misc'        => ['id' => 10,'emoji' => '📱', 'label' => 'Misc & Services',      'desc' => 'Phone load, bills payment, remittance & SIM cards.',                     'tabs' => ['All','Phone Load','Bills Payment','Remittance','SIM Cards']],
        ];

        foreach ($panels as $key => $cat):
            $isActive = $key === 'beverages' ? 'active' : '';
        ?>
        <div id="panel-<?php echo $key; ?>" class="cat-panel <?php echo $isActive; ?>">
            <div class="section-header">
                <h2><?php echo $cat['emoji'] . ' ' . $cat['label']; ?></h2>
                <a href="#" class="see-all">See All →</a>
            </div>
            <p class="section-desc"><?php echo $cat['desc']; ?></p>
            <div class="subcategory-tabs">
                <?php foreach ($cat['tabs'] as $i => $tab): ?>
                <button class="sub-tab <?php echo $i === 0 ? 'active' : ''; ?>"><?php echo $tab; ?></button>
                <?php endforeach; ?>
            </div>
            <div class="product-grid">
                <?php if (!empty($allProducts[$cat['id']])): ?>
                    <?php foreach ($allProducts[$cat['id']] as $product): ?>
                    <div class="product-card">
                        <div class="product-img-placeholder"><?php echo $cat['emoji']; ?></div>
                        <div class="product-info">
                            <div class="product-name-text"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-price-text">₱<?php echo number_format($product['price'], 2); ?></div>
                        </div>
                        <form action="/WST-QuickCart/app/controllers/cartController.php" method="POST">
                            <input type="hidden" name="productId" value="<?php echo $product['productId']; ?>">
                            <button type="submit" name="addToCart" class="product-btn">Add to Cart</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#6b7280;font-size:0.85rem;">No products yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> QuickCart. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php flashMessage(); ?>

<script>
document.querySelectorAll('.cat-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        var id = this.getAttribute('data-cat');
        document.querySelectorAll('.cat-panel').forEach(function(p) { p.classList.remove('active'); });
        document.querySelectorAll('.cat-link').forEach(function(a) { a.classList.remove('active'); });
        document.getElementById('panel-' + id).classList.add('active');
        this.classList.add('active');
    });
});

document.querySelectorAll('.subcategory-tabs').forEach(function(group) {
    group.querySelectorAll('.sub-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            group.querySelectorAll('.sub-tab').forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
        });
    });
});
</script>

</body>
</html>