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

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        /* ── SEARCH BAR ── */
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

        /* ── HERO ── */
        .hero {
            position: relative;
            height: 320px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero img.hero-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.45), rgba(20,83,45,0.78));
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 2rem;
        }

        .hero-content h1 {
            font-size: 2rem;
            color: #fff;
            margin-bottom: 0.6rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.6);
        }

        .hero-content h1 span { color: #4ade80; }

        .hero-content p {
            color: #e5e7eb;
            font-size: 0.95rem;
            margin-bottom: 1.2rem;
            text-shadow: 0 1px 4px rgba(0,0,0,0.5);
        }

        .btn-shop {
            background: #16a34a;
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 7px;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-shop:hover { background: #14532d; }

        /* ── MAIN CONTAINER ── */
        .container {
            max-width: 1150px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* ── SECTION HEADER ── */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }

        .section-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #14532d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .see-all {
            font-size: 0.85rem;
            color: #16a34a;
            text-decoration: none;
            font-weight: 600;
        }

        .see-all:hover { text-decoration: underline; }

        /* ── PRODUCT GRID ── */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 1.1rem;
        }

        .product-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(22,163,74,0.13);
            border-color: #16a34a;
        }

        .product-img-placeholder {
            width: 100%;
            height: 150px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .product-img-placeholder svg {
            width: 40px;
            height: 40px;
            stroke: #d1d5db;
            fill: none;
            stroke-width: 1.5;
        }

        .product-info {
            padding: 0.75rem;
        }

        .product-name {
            height: 14px;
            background: #f3f4f6;
            border-radius: 4px;
            margin-bottom: 6px;
            width: 80%;
        }

        .product-price {
            height: 12px;
            background: #dcfce7;
            border-radius: 4px;
            width: 50%;
        }

        .product-btn {
            display: block;
            margin: 0.5rem 0.75rem 0.75rem;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 7px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: background 0.2s;
        }

        .product-btn:hover { background: #14532d; }

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

    

    <!-- NAVBAR -->
    <nav>
        <a href="#" class="nav-brand">QuickCart</a>

        <ul class="nav-links">
            <li><a href="#">Home</a></li>
            <li><a href="#">Products</a></li>
            <li><a href="#">Categories</a></li>
            <li><a href="#">Deals</a></li>
        </ul>

        <div class="nav-right">
            <!-- Search Bar -->
            <div class="search-wrapper">
                <input type="text" placeholder="Search products...">
                <button>&#128269;</button>
            </div>

            <!-- Account -->
            <a href="#" class="nav-icon-btn">
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?php echo htmlspecialchars($_SESSION['authUser']['username']); ?>
            </a>

            <!-- Cart -->
            <a href="#" class="nav-icon-btn">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <span class="cart-badge">0</span>
                Cart
            </a>

            <!-- Logout -->
            <form action="/WST-QuickCart/app/controllers/userController.php" method="POST">
                <button type="submit" name="logoutButton" class="btn-logout">Logout</button>
            </form>
        </div>
    </nav>

    <!-- HERO -->
    <div class="hero">
        <img class="hero-bg" src="/WST-QuickCart/public/user/assets/img/hero-bg.jpg" alt="Hero Background">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Shop Smart, Shot Fast, <span>Shop QuickCart</span></h1>
            <p>Everything you need in just a few clicks.</p>
            <a href="#" class="btn-shop">SHOP ALL PRODUCTS</a>
        </div>
    </div>

    <!-- PRODUCTS SECTION -->
    <div class="container">
        <div class="section-header">
            <h2>Featured Products</h2>
            <a href="#" class="see-all">See All &rarr;</a>
        </div>

        <div class="product-grid">
            <?php for ($i = 0; $i < 12; $i++): ?>
            <div class="product-card">
                <div class="product-img-placeholder">
                    <!-- Product image will go here -->
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
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

    <!-- FOOTER -->
    <footer>
        &copy; <?php echo date('Y'); ?> QuickCart. All rights reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php flashMessage(); ?>

</body>
</html>