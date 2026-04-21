<?php
include_once(__DIR__ . '/../../app/middleware/user.php');
include_once(__DIR__ . '/../../app/helpers/flashMessage.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickCart – Categories</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f4;
            color: #333;
        }

        /* ── ANNOUNCEMENT BAR ── */
        .announcement {
            background-color: #16a34a;
            color: #fff;
            text-align: center;
            padding: 8px 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* ── TRUST BAR ── */
        .trust-bar {
            background-color: #1f2937;
            display: flex;
            justify-content: center;
            gap: 3rem;
            padding: 10px 2rem;
        }

        .trust-bar span {
            color: #d1d5db;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .trust-bar span::before {
            content: '✓';
            color: #4ade80;
            font-weight: 700;
        }

        /* ── NAVBAR ── */
        nav {
            background-color: #fff;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 65px;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .nav-brand {
            color: #16a34a;
            font-size: 1.4rem;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.8rem;
            list-style: none;
        }

        .nav-links a {
            color: #374151;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover,
        .nav-links a.active { color: #16a34a; }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
            transition: border-color 0.2s;
        }

        .search-wrapper:focus-within { border-color: #16a34a; }

        .search-wrapper input {
            border: none;
            outline: none;
            padding: 7px 12px;
            font-size: 0.85rem;
            width: 200px;
            background: transparent;
            color: #333;
        }

        .search-wrapper button {
            background: #16a34a;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            color: #fff;
            font-size: 0.95rem;
            transition: background 0.2s;
        }

        .search-wrapper button:hover { background: #14532d; }

        .nav-icon-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            text-decoration: none;
            color: #374151;
            font-size: 0.7rem;
            font-weight: 500;
            transition: color 0.2s;
            position: relative;
        }

        .nav-icon-btn:hover { color: #16a34a; }

        .nav-icon-btn svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .cart-badge {
            position: absolute;
            top: -4px;
            right: -6px;
            background: #16a34a;
            color: #fff;
            font-size: 0.6rem;
            font-weight: 700;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-logout {
            background: transparent;
            border: 1.5px solid #16a34a;
            color: #16a34a;
            padding: 6px 14px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }

        .btn-logout:hover { background: #16a34a; color: #fff; }

        /* ── PAGE HEADER ── */
        .page-header {
            background: #14532d;
            padding: 2rem;
            text-align: center;
        }

        .page-header h1 {
            color: #fff;
            font-size: 1.6rem;
            margin-bottom: 0.3rem;
        }

        .page-header h1 span { color: #4ade80; }

        .page-header p {
            color: #bbf7d0;
            font-size: 0.88rem;
        }

        /* ── CONTAINER ── */
        .container {
            max-width: 1150px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* ── CATEGORY GRID ── */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.2rem;
        }

        .category-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            cursor: pointer;
            text-decoration: none;
        }

        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(22,163,74,0.13);
            border-color: #16a34a;
        }

        .card-header {
            padding: 1.2rem 1.2rem 0.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #f0fdf4;
        }

        .cat-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .card-header h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #14532d;
            line-height: 1.3;
        }

        .card-header .cat-count {
            font-size: 0.72rem;
            color: #6b7280;
            margin-top: 2px;
        }

        .card-body {
            padding: 0.8rem 1.2rem 1rem;
        }

        .card-body ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .card-body ul li {
            font-size: 0.78rem;
            color: #6b7280;
            padding-left: 10px;
            position: relative;
            line-height: 1.4;
        }

        .card-body ul li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 7px;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #16a34a;
        }

        .card-footer {
            padding: 0.6rem 1.2rem 0.9rem;
        }

        .btn-browse {
            display: block;
            width: 100%;
            background: #f0fdf4;
            color: #16a34a;
            border: 1.5px solid #bbf7d0;
            border-radius: 7px;
            padding: 7px;
            font-size: 0.8rem;
            font-weight: 700;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .btn-browse:hover {
            background: #16a34a;
            color: #fff;
            border-color: #16a34a;
        }

        /* ── RESTRICTED BADGE ── */
        .badge-restricted {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 6px;
            vertical-align: middle;
        }

        /* ── FOOTER ── */
        footer {
            text-align: center;
            padding: 1.5rem;
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>

    <!-- ANNOUNCEMENT BAR -->
    <div class="announcement">
        Free delivery for all orders above ₱4,000. &nbsp;|&nbsp; Next-day delivery cut-off is at 7:30PM.
    </div>

    <!-- TRUST BAR -->
    <div class="trust-bar">
        <span>Guaranteed Fresh Products</span>
        <span>Free Delivery Above ₱4,000</span>
        <span>Trusted by 1,000+ Customers</span>
    </div>

    <!-- NAVBAR -->
    <nav>
        <a href="/WST-QuickCart/public/user/index.php" class="nav-brand">QuickCart</a>

        <ul class="nav-links">
            <li><a href="/WST-QuickCart/public/user/index.php">Home</a></li>
            <li><a href="#">Products</a></li>
            <li><a href="/WST-QuickCart/public/user/categories.php" class="active">Categories</a></li>
            <li><a href="#">Deals</a></li>
        </ul>

        <div class="nav-right">
            <div class="search-wrapper">
                <input type="text" placeholder="Search products...">
                <button>&#128269;</button>
            </div>

            <a href="#" class="nav-icon-btn">
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?php echo htmlspecialchars($_SESSION['authUser']['username']); ?>
            </a>

            <a href="#" class="nav-icon-btn">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <span class="cart-badge">0</span>
                Cart
            </a>

            <form action="/WST-QuickCart/app/controllers/userController.php" method="POST">
                <button type="submit" name="logoutButton" class="btn-logout">Logout</button>
            </form>
        </div>
    </nav>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <h1>Browse by <span>Category</span></h1>
        <p>Find exactly what you need from our wide selection of products.</p>
    </div>

    <!-- CATEGORIES -->
    <div class="container">
        <div class="category-grid">

            <!-- 1. Beverages -->
            <a href="#" class="category-card">
                <div class="card-header">
                    <div class="cat-icon" style="background:#dbeafe;">🥤</div>
                    <div>
                        <h3>Beverages</h3>
                        <div class="cat-count">6 item types</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Soft drinks, bottled water, energy drinks</li>
                        <li>Coffee, tea, milk, juices</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <span class="btn-browse">Browse Category</span>
                </div>
            </a>

            <!-- 2. Snacks -->
            <a href="#" class="category-card">
                <div class="card-header">
                    <div class="cat-icon" style="background:#fef9c3;">🍿</div>
                    <div>
                        <h3>Snacks</h3>
                        <div class="cat-count">5 item types</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Chips, crackers, cookies</li>
                        <li>Candy, chocolates, nuts</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <span class="btn-browse">Browse Category</span>
                </div>
            </a>

            <!-- 3. Ready-to-Eat -->
            <a href="#" class="category-card">
                <div class="card-header">
                    <div class="cat-icon" style="background:#fce7f3;">🍱</div>
                    <div>
                        <h3>Ready-to-Eat</h3>
                        <div class="cat-count">6 item types</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Sandwiches, burgers, hotdogs</li>
                        <li>Instant noodles, cup meals</li>
                        <li>Rice meals</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <span class="btn-browse">Browse Category</span>
                </div>
            </a>

            <!-- 4. Frozen & Refrigerated -->
            <a href="#" class="category-card">
                <div class="card-header">
                    <div class="cat-icon" style="background:#e0f2fe;">🧊</div>
                    <div>
                        <h3>Frozen & Refrigerated</h3>
                        <div class="cat-count">5 item types</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Ice cream, frozen meals</li>
                        <li>Yogurt, cheese, processed meat</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <span class="btn-browse">Browse Category</span>
                </div>
            </a>

            <!-- 5. Pantry Essentials -->
            <a href="#" class="category-card">
                <div class="card-header">
                    <div class="cat-icon" style="background:#dcfce7;">🥫</div>
                    <div>
                        <h3>Pantry Essentials</h3>
                        <div class="cat-count">5 item types</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Bread, canned goods, instant coffee</li>
                        <li>Condiments (ketchup, soy sauce, etc.)</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <span class="btn-browse">Browse Category</span>
                </div>
            </a>

            <!-- 6. Personal Care -->
            <a href="#" class="category-card">
                <div class="card-header">
                    <div class="cat-icon" style="background:#fae8ff;">🧴</div>
                    <div>
                        <h3>Personal Care</h3>
                        <div class="cat-count">5 item types</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Shampoo, soap, toothpaste</li>
                        <li>Deodorant, sanitary products</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <span class="btn-browse">Browse Category</span>
                </div>
            </a>

            <!-- 7. Household Items -->
            <a href="#" class="category-card">
                <div class="card-header">
                    <div class="cat-icon" style="background:#f1f5f9;">🧹</div>
                    <div>
                        <h3>Household Items</h3>
                        <div class="cat-count">4 item types</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Tissue, cleaning supplies</li>
                        <li>Batteries, light bulbs</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <span class="btn-browse">Browse Category</span>
                </div>
            </a>

            <!-- 8. Tobacco & Alcohol -->
            <a href="#" class="category-card">
                <div class="card-header">
                    <div class="cat-icon" style="background:#fee2e2;">🍺</div>
                    <div>
                        <h3>Tobacco & Alcohol <span class="badge-restricted">18+</span></h3>
                        <div class="cat-count">4 item types</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Cigarettes, vapes</li>
                        <li>Beer, liquor</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <span class="btn-browse">Browse Category</span>
                </div>
            </a>

            <!-- 9. Over-the-Counter Medicine -->
            <a href="#" class="category-card">
                <div class="card-header">
                    <div class="cat-icon" style="background:#d1fae5;">💊</div>
                    <div>
                        <h3>OTC Medicine</h3>
                        <div class="cat-count">4 item types</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Pain relievers, vitamins</li>
                        <li>Basic first aid items</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <span class="btn-browse">Browse Category</span>
                </div>
            </a>

            <!-- 10. Miscellaneous / Services -->
            <a href="#" class="category-card">
                <div class="card-header">
                    <div class="cat-icon" style="background:#ede9fe;">📱</div>
                    <div>
                        <h3>Miscellaneous & Services</h3>
                        <div class="cat-count">4 item types</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Phone load / top-up</li>
                        <li>Bills payment, remittance</li>
                        <li>SIM cards and accessories</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <span class="btn-browse">Browse Category</span>
                </div>
            </a>

        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date('Y'); ?> QuickCart. All rights reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php flashMessage(); ?>

</body>
</html>