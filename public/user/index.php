<?php
include_once(__DIR__ . '/../../app/middleware/user.php');
include_once(__DIR__ . '/../../app/helpers/flashMessage.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickCart – Shop</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f4;
            color: #333;
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

        .nav-links a:hover { color: #16a34a; }
        .nav-links a.active { color: #16a34a; font-weight: 700; }

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
            color: #333;
            background: transparent;
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

        /* ── PAGE LAYOUT ── */
        .page-wrapper {
            display: flex;
            /* REMOVED: max-width: 1200px; */
            margin: 1.5rem auto;
            padding: 0 2rem;        /* slightly more horizontal breathing room */
            gap: 2rem;              /* increased gap for wider sidebar */
            align-items: flex-start;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 300px;           /* increased from 240px */
            flex-shrink: 0;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            position: sticky;
            top: 80px;
        }

        .sidebar-title {
            background: #14532d;
            color: #fff;
            padding: 1.1rem 1.4rem; /* slightly larger padding */
            font-size: 1.05rem;     /* slightly larger text */
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .sidebar-list { list-style: none; }

        .sidebar-list li a {
            display: flex;
            align-items: center;
            gap: 14px;              /* increased from 12px */
            padding: 0.9rem 1.4rem; /* increased from 0.75rem 1.2rem */
            text-decoration: none;
            color: #374151;
            font-size: 0.92rem;     /* increased from 0.88rem */
            font-weight: 500;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.15s, color 0.15s;
        }

        .sidebar-list li a:hover,
        .sidebar-list li a.active {
            background: #f0fdf4;
            color: #16a34a;
        }

        .sidebar-list li a.active { border-left: 3px solid #16a34a; }
        .sidebar-list li:last-child a { border-bottom: none; }

        .cat-icon-sm {
            width: 38px;            /* increased from 34px */
            height: 38px;           /* increased from 34px */
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;      /* increased from 1.1rem */
            flex-shrink: 0;
        }

        .badge-18 {
            margin-left: auto;
            background: #fef3c7;
            color: #92400e;
            font-size: 0.65rem;     /* slightly larger */
            font-weight: 700;
            padding: 2px 8px;       /* slightly wider */
            border-radius: 20px;
        }

        /* ── MAIN CONTENT ── */
        .main-content { flex: 1; min-width: 0; }

        /* ── HERO ── */
        .hero {
            position: relative;
            height: 260px;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .hero img.hero-bg {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.45), rgba(20,83,45,0.78));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }

        .hero-overlay h1 {
            font-size: 1.7rem;
            color: #fff;
            margin-bottom: 0.4rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.6);
        }

        .hero-overlay h1 span { color: #4ade80; }

        .hero-overlay p {
            color: #e5e7eb;
            font-size: 0.88rem;
            margin-bottom: 1rem;
            text-shadow: 0 1px 4px rgba(0,0,0,0.5);
        }

        .btn-shop {
            background: #16a34a;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 7px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            letter-spacing: 0.5px;
            transition: background 0.2s;
        }

        .btn-shop:hover { background: #14532d; }

        /* ── SECTION HEADER ── */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .section-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #14532d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .see-all {
            font-size: 0.83rem;
            color: #16a34a;
            text-decoration: none;
            font-weight: 600;
        }

        .see-all:hover { text-decoration: underline; }

        /* ── PRODUCT GRID ── */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .product-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(22,163,74,0.13);
            border-color: #16a34a;
        }

        .product-img-placeholder {
            width: 100%;
            height: 140px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .product-img-placeholder svg {
            width: 38px;
            height: 38px;
            stroke: #d1d5db;
            fill: none;
            stroke-width: 1.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .product-info { padding: 0.7rem; }

        .product-name {
            height: 12px;
            background: #f3f4f6;
            border-radius: 4px;
            margin-bottom: 6px;
            width: 80%;
        }

        .product-price {
            height: 11px;
            background: #dcfce7;
            border-radius: 4px;
            width: 50%;
        }

        .product-btn {
            display: block;
            margin: 0 0.7rem 0.7rem;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 7px;
            font-size: 0.76rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            width: calc(100% - 1.4rem);
            transition: background 0.2s;
        }

        .product-btn:hover { background: #14532d; }

        /* ── FOOTER ── */
        footer {
            text-align: center;
            padding: 1.5rem;
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav>
        <a href="/WST-QuickCart/public/user/index.php" class="nav-brand">QuickCart</a>

        <ul class="nav-links">
            <li><a href="/WST-QuickCart/public/user/index.php" class="active">Home</a></li>
            <li><a href="#">Products</a></li>
            <!-- REMOVED: Categories link -->
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

    <!-- PAGE WRAPPER -->
    <div class="page-wrapper">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-title">Categories</div>
            <ul class="sidebar-list">
                <li>
                    <a href="#">
                        <div class="cat-icon-sm" style="background:#dbeafe;">🥤</div>
                        Beverages
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="cat-icon-sm" style="background:#fef9c3;">🍿</div>
                        Snacks
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="cat-icon-sm" style="background:#fce7f3;">🍱</div>
                        Ready-to-Eat
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="cat-icon-sm" style="background:#e0f2fe;">🧊</div>
                        Frozen & Refrigerated
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="cat-icon-sm" style="background:#dcfce7;">🥫</div>
                        Pantry Essentials
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="cat-icon-sm" style="background:#fae8ff;">🧴</div>
                        Personal Care
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="cat-icon-sm" style="background:#f1f5f9;">🧹</div>
                        Household Items
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="cat-icon-sm" style="background:#fee2e2;">🍺</div>
                        Tobacco & Alcohol
                        <span class="badge-18">18+</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="cat-icon-sm" style="background:#d1fae5;">💊</div>
                        OTC Medicine
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="cat-icon-sm" style="background:#ede9fe;">📱</div>
                        Misc & Services
                    </a>
                </li>
            </ul>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="main-content">

            <!-- HERO -->
            <div class="hero">
                <img class="hero-bg" src="/WST-QuickCart/public/user/assets/img/hero-bg.jpg" alt="Hero Background">
                <div class="hero-overlay">
                    <h1>Shop Smart, Shop Fast, <span>Shop QuickCart</span></h1>
                    <p>Everything you need in just a few clicks.</p>
                    <a href="#" class="btn-shop">SHOP ALL PRODUCTS</a>
                </div>
            </div>

            <!-- FEATURED PRODUCTS -->
            <div class="section-header">
                <h2>Featured Products</h2>
                <a href="#" class="see-all">See All &rarr;</a>
            </div>

            <div class="product-grid">
                <?php for ($i = 0; $i < 12; $i++): ?>
                <div class="product-card">
                    <div class="product-img-placeholder">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                    <div class="product-info">
                        <div class="product-name"></div>
                        <div class="product-price"></div>
                    </div>
                    <button class="product-btn">Add to Cart</button>
                </div>
                <?php endfor; ?>
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date('Y'); ?> QuickCart. All rights reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11 "></script>
    <?php flashMessage(); ?>

</body>
</html>